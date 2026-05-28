<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SalesController extends Controller
{
    public function index(): View|RedirectResponse
    {
        return view('sales');
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
            'name' => $product->name,
            'sale_price' => (float) $product->sale_price,
            'sale_price_text' => number_format((float) $product->sale_price, 0, ',', '.') . ' ₫',
            'quantity' => (int) $product->quantity,
            'description' => $product->description,
            'image_url' => $product->image_path ? Storage::disk('public')->url($product->image_path) : null,
            'category' => $product->category ? [
                'public_id' => $product->category->public_id,
                'name' => $product->category->name,
            ] : null,
            'supplier' => $product->supplier ? [
                'public_id' => $product->supplier->public_id,
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
        ]);

        $items = $data['items'];

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
}