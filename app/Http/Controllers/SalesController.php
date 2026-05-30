<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\View\View;

class SalesController extends Controller
{
    public function index(): View|RedirectResponse
    {
        return view('sales');
    }

    public function search(Request $request): JsonResponse
    {
        $queryText = trim($request->string('query')->toString());

        if ($queryText === '') {
            return response()->json([
                'items' => [],
            ]);
        }

        $normalizedQuery = preg_replace('/\s+/', '', $queryText) ?? $queryText;
        $trimmedDigits = ltrim($normalizedQuery, '0');

        $products = Product::query()
            ->select(['id', 'public_id', 'category_id', 'supplier_id', 'name', 'sale_price', 'quantity', 'image_path'])
            ->with([
                'supplier:id,public_id,name',
                'category:id,public_id,name',
            ])
            ->where(function ($query) use ($normalizedQuery, $trimmedDigits): void {
                $query->where('name', 'like', '%'.$normalizedQuery.'%')
                    ->orWhere('public_id', 'like', '%'.$normalizedQuery.'%')
                    ->orWhere('id', 'like', '%'.$normalizedQuery.'%');

                if ($trimmedDigits !== '' && $trimmedDigits !== $normalizedQuery) {
                    $query->orWhere('public_id', 'like', $trimmedDigits.'%')
                        ->orWhereRaw("TRIM(LEADING '0' FROM public_id) LIKE ?", [$trimmedDigits.'%']);
                }
            })
            ->orderByRaw('CASE WHEN public_id = ? THEN 0 WHEN public_id LIKE ? THEN 1 ELSE 2 END', [
                $normalizedQuery,
                $normalizedQuery.'%',
            ])
            ->limit(8)
            ->get()
            ->map(function (Product $product): array {
                return [
                    'id' => $product->id,
                    'public_id' => $product->public_id,
                    'public_id_display' => $product->public_id_display,
                    'name' => $product->name,
                    'sale_price' => (float) $product->sale_price,
                    'sale_price_text' => number_format((float) $product->sale_price, 0, ',', '.') . ' ₫',
                    'quantity' => (int) $product->quantity,
                    'image_url' => $this->resolveImageUrl($product->image_path),
                    'supplier' => $product->supplier ? [
                        'public_id_display' => $product->supplier->public_id_display,
                        'name' => $product->supplier->name,
                    ] : null,
                    'category' => $product->category ? [
                        'public_id_display' => $product->category->public_id_display,
                        'name' => $product->category->name,
                    ] : null,
                ];
            });

        return response()->json([
            'items' => $products,
        ]);
    }

    public function lookup(Request $request, string $code): JsonResponse
    {
        $product = $this->findProductByCode($code);

        if ($product === null) {
            return response()->json([
                'message' => 'Không tìm thấy sản phẩm phù hợp.',
            ], 404);
        }

        $product->loadMissing([
            'category:id,public_id,name',
            'supplier:id,public_id,name',
        ]);

        return response()->json([
            'id' => $product->id,
            'public_id' => $product->public_id,
            'public_id_display' => $product->public_id_display,
            'name' => $product->name,
            'sale_price' => (float) $product->sale_price,
            'sale_price_text' => number_format((float) $product->sale_price, 0, ',', '.') . ' ₫',
            'quantity' => (int) $product->quantity,
            'description' => $product->description,
            'image_url' => $this->resolveImageUrl($product->image_path),
            'category' => $product->category ? [
                'public_id' => $product->category->public_id,
                'public_id_display' => $product->category->public_id_display,
                'name' => $product->category->name,
            ] : null,
            'supplier' => $product->supplier ? [
                'public_id' => $product->supplier->public_id,
                'public_id_display' => $product->supplier->public_id_display,
                'name' => $product->supplier->name,
            ] : null,
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'payment_token' => ['nullable', 'string'],
        ]);

        $items = $data['items'];
        $paymentToken = $data['payment_token'] ?? null;

        // If a payment token is provided, verify it exists and matches
        if ($paymentToken) {
            $cached = Cache::pull("sales.payment.{$paymentToken}");
            if (!$cached || !isset($cached['items']) || $cached['items'] !== $items) {
                return response()->json(['message' => 'Mã thanh toán không hợp lệ hoặc đã hết hạn.'], 422);
            }
        }

        $payloadItems = [];
        $total = 0;

        DB::beginTransaction();
        try {
            foreach ($items as $item) {
                $product = Product::lockForUpdate()->find($item['id']);

                if (!$product) {
                    DB::rollBack();
                    return response()->json(['message' => "Sản phẩm ID {$item['id']} không tồn tại."], 422);
                }

                $qty = (int) $item['quantity'];

                if ($product->quantity < $qty) {
                    DB::rollBack();
                    return response()->json(['message' => "Sản phẩm {$product->name} không đủ tồn kho."], 422);
                }

                $product->quantity = $product->quantity - $qty;
                $product->save();

                $lineTotal = $product->sale_price * $qty;
                $total += $lineTotal;

                $payloadItems[] = [
                    'id' => $product->id,
                    'public_id' => $product->public_id,
                    'name' => $product->name,
                    'quantity' => $qty,
                    'unit_price' => (float) $product->sale_price,
                    'line_total' => (float) $lineTotal,
                ];
            }

            $audit = AuditLog::record([
                'user_id' => auth()->id(),
                'action' => 'sales.checkout',
                'method' => $request->method(),
                'route_name' => 'sales.checkout',
                'path' => $request->path(),
                'status_code' => 200,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'payload' => [
                    'items' => $payloadItems,
                    'total' => $total,
                ],
            ]);

            DB::commit();

            return response()->json(['message' => 'Chốt hoá đơn thành công.', 'audit_id' => $audit->id]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi khi chốt hoá đơn. Vui lòng thử lại.'], 500);
        }
    }

    /**
     * Create a payment QR for bank transfer and cache the payload until
     * the cashier confirms payment.
     */
    public function createPayment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $items = $data['items'];

        // compute total
        $total = 0;
        foreach ($items as $item) {
            $product = Product::find($item['id']);
            if (!$product) {
                return response()->json(['message' => "Sản phẩm ID {$item['id']} không tồn tại."], 422);
            }
            $total += $product->sale_price * (int)$item['quantity'];
        }

        // Build payment payload (prefer settings saved in DB, fallback to env)
        $bankCode = \App\Models\Setting::resolveBankCode(\App\Models\Setting::get('bank_name', env('APP_BANK_NAME', '')));
        $bankName = \App\Models\Setting::resolveBankLabel($bankCode);
        $accountNumber = \App\Models\Setting::get('bank_account', env('APP_BANK_ACCOUNT', '000000000'));
        $accountName = \App\Models\Setting::get('bank_account_name', env('APP_BANK_ACCOUNT_NAME', config('app.name')));

        if ($bankCode === '' || $accountNumber === '' || $accountName === '') {
            return response()->json([
                'message' => 'Vui lòng cấu hình đầy đủ ngân hàng, số tài khoản và tên chủ tài khoản trong phần Cài đặt.',
            ], 422);
        }

        $paymentReference = Str::uuid()->toString();

        $paymentContent = sprintf('Thanh toán %s', $paymentReference);
        $qrUrl = sprintf(
            'https://img.vietqr.io/image/%s-%s-compact2.png?amount=%s&addInfo=%s&accountName=%s',
            rawurlencode($bankCode),
            rawurlencode($accountNumber),
            rawurlencode((string) round($total)),
            rawurlencode($paymentContent),
            rawurlencode($accountName)
        );

        $payload = "Ngân hàng: {$bankName}\nSố tài khoản: {$accountNumber}\nChủ tài khoản: {$accountName}\nSố tiền: " . number_format($total, 0, ',', '.') . " ₫\nNội dung: {$paymentContent}";

        $token = Str::random(24);

        Cache::put("sales.payment.{$token}", [
            'items' => $items,
            'total' => $total,
            'reference' => $paymentReference,
        ], now()->addMinutes(30));

        return response()->json([
            'qr_url' => $qrUrl,
            'payload' => $payload,
            'payment_token' => $token,
            'total' => (float)$total,
        ]);
    }

    private function findProductByCode(string $code): ?Product
    {
        $code = trim($code);

        if ($code === '') {
            return null;
        }

        $code = preg_replace('/\s+/', '', $code) ?? $code;

        if (str_contains($code, '-')) {
            $segments = array_values(array_filter(explode('-', $code), fn (string $segment): bool => $segment !== ''));
            $code = $segments[0] ?? $code;
        }

        $query = Product::query()
            ->select(['id', 'public_id', 'category_id', 'supplier_id', 'name', 'sale_price', 'quantity', 'image_path', 'description']);

        if (ctype_digit($code)) {
            $product = (clone $query)->find((int) $code);

            if ($product !== null) {
                return $product;
            }
        }

        // direct public_id match
        $product = $query->where('public_id', $code)->first();
        if ($product !== null) {
            return $product;
        }

        // try matching after stripping leading zeros from scanned code
        $trimmed = ltrim($code, '0');
        if ($trimmed !== '' && $trimmed !== $code) {
            $product = (clone $query)->where('public_id', $trimmed)->first();

            if ($product !== null) {
                return $product;
            }

            // fallback: compare DB-side by trimming leading zeros from stored public_id
            try {
                $product = (clone $query)->whereRaw("TRIM(LEADING '0' FROM public_id) = ?", [$trimmed])->first();

                if ($product !== null) {
                    return $product;
                }
            } catch (\Throwable $e) {
                // ignore DB-specific errors and continue
            }
        }

        return null;
    }

    private function resolveImageUrl(?string $imagePath): ?string
    {
        if (! $imagePath) {
            return null;
        }

        if (! Storage::disk('public')->exists($imagePath)) {
            return null;
        }

        return asset('storage/'.$imagePath);
    }
}