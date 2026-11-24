<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\BranchManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SalesController extends Controller
{
    public function __construct(private readonly BranchManager $branchManager)
    {
    }

    public function index(): View|RedirectResponse
    {
        $availableBranches = $this->branchManager->availableBranches(['products', 'sales', 'sale_items']);

        $salesBranches = array_map(function (array $branch): array {
            $bank = $this->branchManager->readBankSettings($branch['connection'], $branch['is_current']);

            return [
                'key' => $branch['key'],
                'name' => $branch['name'],
                'is_current' => $branch['is_current'],
                'bank_configured' => $bank['configured'],
                'bank_name' => $bank['bank_name'],
                'account_number' => $bank['account_number'],
            ];
        }, $availableBranches);

        $currentBranch = $this->branchManager->currentBranch();

        return view('sales', [
            'salesBranches' => $salesBranches,
            'isMultiBranch' => count($salesBranches) > 1,
            'currentBranchKey' => $currentBranch['key'],
            'currentBranchName' => $currentBranch['name'],
            'canManageSettings' => auth()->user()?->can('settings.manage') ?? false,
        ]);
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
        $requestedBranch = strtolower(trim((string) $request->input('branch', 'all')));

        [$branchFromCode, $cleanCode] = $this->extractBranchPrefixFromCode($normalizedQuery);
        $targetBranches = $this->resolveTargetBranches($branchFromCode ?? $requestedBranch);

        if ($cleanCode === '' || $targetBranches === []) {
            return response()->json([
                'items' => [],
            ]);
        }

        $items = [];

        foreach ($targetBranches as $branch) {
            $products = Product::on($branch['connection'])
                ->select(['id', 'public_id', 'category_id', 'supplier_id', 'name', 'sale_price', 'quantity', 'image_path', 'returned_at'])
                ->with([
                    'supplier:id,public_id,name',
                    'category:id,public_id,name',
                ])
                ->sellable()
                ->where(function ($query) use ($cleanCode): void {
                    $query->where('public_id', $cleanCode);

                    if ($this->isExactIntegerKey($cleanCode)) {
                        $query->orWhere('id', (int) $cleanCode);
                    }
                })
                ->orderByDesc('id')
                ->limit(8)
                ->get();

            foreach ($products as $product) {
                $items[] = $this->formatSearchProduct($product, $branch);
            }
        }

        return response()->json([
            'items' => array_slice($items, 0, 12),
        ]);
    }

    public function lookup(Request $request, string $code): JsonResponse
    {
        $requestedBranch = strtolower(trim((string) $request->input('branch', 'all')));
        [$branchFromCode, $cleanCode] = $this->extractBranchPrefixFromCode($code);

        $targetBranches = $this->resolveTargetBranches($branchFromCode ?? $requestedBranch);

        if ($targetBranches === []) {
            return response()->json([
                'message' => 'Không tìm thấy cơ sở phù hợp.',
            ], 404);
        }

        $matches = [];
        foreach ($targetBranches as $branch) {
            $product = $this->findProductByCodeOnConnection($branch['connection'], $cleanCode);
            if ($product !== null) {
                $matches[] = [
                    'branch' => $branch,
                    'product' => $product,
                ];
            }
        }

        if ($matches === []) {
            return response()->json([
                'message' => 'Không tìm thấy sản phẩm phù hợp.',
            ], 404);
        }

        if (count($matches) === 1) {
            return $this->buildSingleLookupResponse($matches[0]['product'], $matches[0]['branch']);
        }

        // Multiple branches have a product matching this exact code.
        // Prefer active sellable in-stock products; if only one is sellable with stock, pick it automatically.
        $sellableInStock = array_values(array_filter(
            $matches,
            fn (array $match): bool => $match['product']->isSellable() && (int) $match['product']->quantity > 0
        ));

        if (count($sellableInStock) === 1) {
            return $this->buildSingleLookupResponse($sellableInStock[0]['product'], $sellableInStock[0]['branch']);
        }

        $candidates = $sellableInStock !== [] ? $sellableInStock : $matches;
        $formattedMatches = array_map(function (array $match): array {
            /** @var Product $product */
            $product = $match['product'];
            $product->loadMissing([
                'category:id,public_id,name',
                'supplier:id,public_id,name',
            ]);

            return $this->formatLookupProduct($product, $match['branch']);
        }, $candidates);

        return response()->json([
            'ambiguous' => true,
            'message' => 'Mã sản phẩm này tồn tại ở nhiều cơ sở. Vui lòng chọn đúng cơ sở bên dưới.',
            'matches' => $formattedMatches,
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.branch_key' => ['nullable', 'string', 'max:50'],
            'payment_method' => ['required', 'in:cash,transfer'],
            'payment_token' => ['nullable', 'string'],
        ]);

        $normalizedItems = $this->normalizeCheckoutItems($data['items']);
        if ($normalizedItems instanceof JsonResponse) {
            return $normalizedItems;
        }

        $items = $normalizedItems;
        $paymentMethod = $data['payment_method'];
        $paymentToken = $data['payment_token'] ?? null;
        $branchReferences = [];

        // If a payment token is provided, verify it exists and matches the cart items
        if ($paymentToken) {
            $cached = Cache::pull("sales.payment.{$paymentToken}");
            if (! $cached || ! isset($cached['items']) || $cached['items'] !== $items) {
                return response()->json(['message' => 'Mã thanh toán không hợp lệ hoặc đã hết hạn.'], 422);
            }

            if (isset($cached['branch_references']) && is_array($cached['branch_references'])) {
                $branchReferences = $cached['branch_references'];
            } elseif (isset($cached['reference'])) {
                $defaultKey = $this->branchManager->currentBranch()['key'];
                $branchReferences[$defaultKey] = $cached['reference'];
            }
        }

        $groupedByBranch = $this->groupItemsByBranch($items);
        $availableMap = collect($this->branchManager->availableBranches(['products', 'sales', 'sale_items']))
            ->keyBy('key');

        foreach (array_keys($groupedByBranch) as $branchKey) {
            if (! $availableMap->has($branchKey)) {
                return response()->json(['message' => "Cơ sở '{$branchKey}' không khả dụng hoặc chưa khởi tạo dữ liệu."], 422);
            }
        }

        // Start transactions across unique underlying PDOs in deterministic branch order
        $startedConnections = [];
        $seenPdoIds = [];

        try {
            foreach (array_keys($groupedByBranch) as $branchKey) {
                $conn = DB::connection($availableMap->get($branchKey)['connection']);
                $pdoId = spl_object_id($conn->getPdo());

                if (! isset($seenPdoIds[$pdoId])) {
                    $conn->beginTransaction();
                    $seenPdoIds[$pdoId] = true;
                    $startedConnections[] = $conn;
                }
            }

            $createdSales = [];
            $authUser = auth()->user();

            foreach ($groupedByBranch as $branchKey => $branchItems) {
                $branch = $availableMap->get($branchKey);
                $connName = $branch['connection'];
                $conn = DB::connection($connName);

                $payloadItems = [];
                $total = 0;

                foreach ($branchItems as $item) {
                    $product = Product::on($connName)->lockForUpdate()->find($item['id']);

                    if (! $product) {
                        $this->rollbackConnections($startedConnections);

                        return response()->json(['message' => "Sản phẩm ID {$item['id']} ({$branch['name']}) không tồn tại."], 422);
                    }

                    if ($product->isReturned() || $product->isConsignmentExpired()) {
                        $this->rollbackConnections($startedConnections);

                        return response()->json(['message' => "Sản phẩm {$product->name} đã hết hạn ký gửi hoặc đã trả cho người gửi. Không thể bán trên hệ thống."], 422);
                    }

                    $qty = (int) $item['quantity'];

                    if ($product->quantity < $qty) {
                        $this->rollbackConnections($startedConnections);

                        return response()->json(['message' => "Sản phẩm {$product->name} không đủ tồn kho."], 422);
                    }

                    $product->quantity = $product->quantity - $qty;
                    $product->save();

                    $lineTotal = (float) $product->sale_price * $qty;
                    $total += $lineTotal;

                    $payloadItems[] = [
                        'id' => $product->id,
                        'public_id' => $product->public_id,
                        'public_id_display' => $product->public_id_display,
                        'name' => $product->name,
                        'quantity' => $qty,
                        'unit_price' => (float) $product->sale_price,
                        'line_total' => (float) $lineTotal,
                    ];
                }

                $branchUserId = $this->branchManager->resolveBranchUserId($conn, $authUser);
                $paymentReference = $paymentMethod === 'transfer' ? ($branchReferences[$branchKey] ?? null) : null;

                $sale = Sale::on($connName)->create([
                    'user_id' => $branchUserId,
                    'payment_method' => $paymentMethod,
                    'payment_reference' => $paymentReference,
                    'total_amount' => $total,
                    'items_count' => count($payloadItems),
                    'completed_at' => now(),
                ]);

                foreach ($payloadItems as $payloadItem) {
                    SaleItem::on($connName)->create([
                        'sale_id' => $sale->id,
                        'product_id' => $payloadItem['id'],
                        'product_public_id' => $payloadItem['public_id'],
                        'product_name' => $payloadItem['name'],
                        'quantity' => $payloadItem['quantity'],
                        'unit_price' => $payloadItem['unit_price'],
                        'line_total' => $payloadItem['line_total'],
                    ]);
                }

                $audit = AuditLog::on($connName)->create([
                    'user_id' => $branchUserId,
                    'action' => 'sales.checkout',
                    'method' => $request->method(),
                    'route_name' => 'sales.checkout',
                    'path' => $request->path(),
                    'status_code' => 200,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'payload' => [
                        'branch_key' => $branch['key'],
                        'branch_name' => $branch['name'],
                        'cashier_name' => $authUser?->name,
                        'cashier_email' => $authUser?->email,
                        'sale_id' => $sale->id,
                        'sale_public_id' => $sale->public_id,
                        'payment_method' => $paymentMethod,
                        'payment_reference' => $sale->payment_reference,
                        'items' => $payloadItems,
                        'total' => $total,
                    ],
                ]);

                $createdSales[] = [
                    'branch_key' => $branch['key'],
                    'branch_name' => $branch['name'],
                    'audit_id' => $audit->id,
                    'sale_id' => $sale->id,
                    'sale_public_id' => $sale->public_id,
                    'total_amount' => (float) $total,
                    'payment_method' => $sale->payment_method,
                    'payment_reference' => $sale->payment_reference,
                ];
            }

            foreach ($startedConnections as $conn) {
                $conn->commit();
            }

            $firstSale = $createdSales[0];
            $branchNames = array_column($createdSales, 'branch_name');
            $message = count($createdSales) > 1
                ? 'Đã chốt '.count($createdSales).' hóa đơn theo từng cơ sở ('.implode(', ', $branchNames).').'
                : 'Chốt hoá đơn thành công.';

            return response()->json([
                'message' => $message,
                'audit_id' => $firstSale['audit_id'],
                'sale_id' => $firstSale['sale_id'],
                'sale_public_id' => $firstSale['sale_public_id'],
                'payment_method' => $firstSale['payment_method'],
                'sales' => $createdSales,
            ]);
        } catch (\Throwable $e) {
            $this->rollbackConnections($startedConnections);
            Log::error('Sales checkout failed', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);

            return response()->json(['message' => 'Lỗi khi chốt hoá đơn. Vui lòng thử lại.'], 500);
        }
    }

    /**
     * Create VietQR payment payload(s) per branch for bank transfer and cache the payload
     * until the cashier confirms checkout.
     */
    public function createPayment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.branch_key' => ['nullable', 'string', 'max:50'],
        ]);

        $normalizedItems = $this->normalizeCheckoutItems($data['items']);
        if ($normalizedItems instanceof JsonResponse) {
            return $normalizedItems;
        }

        $items = $normalizedItems;
        $groupedByBranch = $this->groupItemsByBranch($items);
        $availableMap = collect($this->branchManager->availableBranches(['products', 'sales', 'sale_items']))
            ->keyBy('key');

        $payments = [];
        $branchReferences = [];
        $grandTotal = 0.0;

        foreach ($groupedByBranch as $branchKey => $branchItems) {
            if (! $availableMap->has($branchKey)) {
                return response()->json([
                    'message' => "Cơ sở '{$branchKey}' không khả dụng hoặc chưa khởi tạo dữ liệu.",
                ], 422);
            }

            $branch = $availableMap->get($branchKey);
            $connName = $branch['connection'];

            $branchTotal = 0.0;
            $branchUnits = 0;

            foreach ($branchItems as $item) {
                $product = Product::on($connName)->sellable()->find($item['id']);
                if (! $product) {
                    return response()->json([
                        'message' => "Sản phẩm ID {$item['id']} ({$branch['name']}) không tồn tại hoặc không còn bán được.",
                    ], 422);
                }

                $qty = (int) $item['quantity'];

                if ($product->quantity < $qty) {
                    return response()->json([
                        'message' => "Sản phẩm {$product->name} không đủ tồn kho.",
                    ], 422);
                }

                $branchTotal += (float) $product->sale_price * $qty;
                $branchUnits += $qty;
            }

            if ($branchTotal <= 0) {
                return response()->json([
                    'message' => "Số tiền thanh toán tại {$branch['name']} phải lớn hơn 0.",
                ], 422);
            }

            $bank = $this->branchManager->readBankSettings($connName, $branch['is_current']);

            if (! $bank['configured']) {
                return response()->json([
                    'message' => "Vui lòng cấu hình đầy đủ ngân hàng, số tài khoản và tên chủ tài khoản cho {$branch['name']} trong phần Cài đặt.",
                    'missing_bank_branch' => $branch['key'],
                    'missing_bank_branch_name' => $branch['name'],
                ], 422);
            }

            $shortCode = date('ymd').strtoupper(Str::random(5));
            $branchTag = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $branch['key']) ?: 'CS');
            $paymentReference = "{$branchTag}-{$shortCode}";
            $paymentContent = "COHANKYGUI {$branchTag} {$shortCode}";

            $qrUrl = sprintf(
                'https://img.vietqr.io/image/%s-%s-compact2.png?amount=%s&addInfo=%s&accountName=%s',
                rawurlencode($bank['bank_code']),
                rawurlencode($bank['account_number']),
                rawurlencode((string) (int) round($branchTotal)),
                rawurlencode($paymentContent),
                rawurlencode($bank['account_name'])
            );

            $payload = "Cơ sở: {$branch['name']}\nNgân hàng: {$bank['bank_name']}\nSố tài khoản: {$bank['account_number']}\nChủ tài khoản: {$bank['account_name']}\nSố tiền: ".number_format($branchTotal, 0, ',', '.')." ₫\nNội dung: {$paymentContent}";

            $branchReferences[$branchKey] = $paymentReference;
            $grandTotal += $branchTotal;

            $payments[] = [
                'branch_key' => $branch['key'],
                'branch_name' => $branch['name'],
                'bank_code' => $bank['bank_code'],
                'bank_name' => $bank['bank_name'],
                'account_number' => $bank['account_number'],
                'account_name' => $bank['account_name'],
                'amount' => (float) $branchTotal,
                'amount_text' => number_format($branchTotal, 0, ',', '.').' ₫',
                'items_count' => $branchUnits,
                'payment_reference' => $paymentReference,
                'payment_content' => $paymentContent,
                'qr_url' => $qrUrl,
                'payload' => $payload,
            ];
        }

        $token = Str::random(24);
        $firstPayment = $payments[0];

        Cache::put("sales.payment.{$token}", [
            'items' => $items,
            'total' => $grandTotal,
            'reference' => $firstPayment['payment_reference'],
            'branch_references' => $branchReferences,
        ], now()->addMinutes(30));

        return response()->json([
            'qr_url' => $firstPayment['qr_url'],
            'payload' => $firstPayment['payload'],
            'bank_name' => $firstPayment['bank_name'],
            'account_number' => $firstPayment['account_number'],
            'account_name' => $firstPayment['account_name'],
            'payment_content' => $firstPayment['payment_content'],
            'payment_reference' => $firstPayment['payment_reference'],
            'payment_token' => $token,
            'total' => (float) $grandTotal,
            'payments' => $payments,
        ]);
    }

    /**
     * @param  array{key:string,name:string,prefix:string,url:string,is_current:bool,connection:string}  $branch
     */
    private function buildSingleLookupResponse(Product $product, array $branch): JsonResponse
    {
        if ($product->isReturned()) {
            return response()->json([
                'message' => 'Sản phẩm này đã được trả cho người gửi nên không thể bán.',
            ], 422);
        }

        if ($product->isConsignmentExpired()) {
            return response()->json([
                'message' => 'Sản phẩm này đã quá hạn ký gửi nên không thể bán.',
            ], 422);
        }

        $product->loadMissing([
            'category:id,public_id,name',
            'supplier:id,public_id,name',
        ]);

        return response()->json($this->formatLookupProduct($product, $branch));
    }

    /**
     * @param  array{key:string,name:string,prefix:string,url:string,is_current:bool,connection:string}  $branch
     * @return array<string, mixed>
     */
    private function formatSearchProduct(Product $product, array $branch): array
    {
        return [
            'id' => $product->id,
            'branch_key' => $branch['key'],
            'branch_name' => $branch['name'],
            'public_id' => $product->public_id,
            'public_id_display' => $product->public_id_display,
            'name' => $product->name,
            'sale_price' => (float) $product->sale_price,
            'sale_price_text' => number_format((float) $product->sale_price, 0, ',', '.').' ₫',
            'quantity' => (int) $product->quantity,
            'image_url' => $this->resolveImageUrl($product->image_path, $branch),
            'supplier' => $product->supplier ? [
                'public_id_display' => $product->supplier->public_id_display,
                'name' => $product->supplier->name,
            ] : null,
            'category' => $product->category ? [
                'public_id_display' => $product->category->public_id_display,
                'name' => $product->category->name,
            ] : null,
        ];
    }

    /**
     * @param  array{key:string,name:string,prefix:string,url:string,is_current:bool,connection:string}  $branch
     * @return array<string, mixed>
     */
    private function formatLookupProduct(Product $product, array $branch): array
    {
        return [
            'id' => $product->id,
            'branch_key' => $branch['key'],
            'branch_name' => $branch['name'],
            'public_id' => $product->public_id,
            'public_id_display' => $product->public_id_display,
            'name' => $product->name,
            'sale_price' => (float) $product->sale_price,
            'sale_price_text' => number_format((float) $product->sale_price, 0, ',', '.').' ₫',
            'quantity' => (int) $product->quantity,
            'description' => $product->description,
            'image_url' => $this->resolveImageUrl($product->image_path, $branch),
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
        ];
    }

    /**
     * Extract optional branch prefix such as "cs2-15" or "cs2-15-3".
     *
     * @return array{0:string|null,1:string}
     */
    private function extractBranchPrefixFromCode(string $code): array
    {
        $clean = preg_replace('/\s+/', '', trim($code)) ?? trim($code);

        if ($clean === '' || ! str_contains($clean, '-')) {
            return [null, $clean];
        }

        [$prefix, $rest] = explode('-', $clean, 2);
        $branchKey = strtolower($prefix);

        if ($rest !== '' && $this->branchManager->findBranch($branchKey) !== null) {
            return [$branchKey, $rest];
        }

        return [null, $clean];
    }

    /**
     * @return array<int, array{key:string,name:string,prefix:string,url:string,is_current:bool,connection:string}>
     */
    private function resolveTargetBranches(string $requestedBranch): array
    {
        $available = $this->branchManager->availableBranches(['products', 'sales', 'sale_items']);

        if ($requestedBranch === '' || $requestedBranch === 'all') {
            return $available;
        }

        return array_values(array_filter(
            $available,
            fn (array $branch): bool => $branch['key'] === $requestedBranch
        ));
    }

    private function findProductByCodeOnConnection(string $connectionName, string $code): ?Product
    {
        $code = trim($code);

        if ($code === '') {
            return null;
        }

        $code = preg_replace('/\s+/', '', $code) ?? $code;

        if (str_contains($code, '-')) {
            if (! preg_match('/^\d+-\d+(?:-\d+)?$/', $code)) {
                return null;
            }

            $segments = array_values(array_filter(explode('-', $code), fn (string $segment): bool => $segment !== ''));
            $code = $segments[0] ?? $code;
        }

        $query = Product::on($connectionName)
            ->select(['id', 'public_id', 'consignment_note_id', 'category_id', 'supplier_id', 'name', 'sale_price', 'quantity', 'image_path', 'description', 'returned_at']);

        if (isset($segments)) {
            if (! $this->isExactIntegerKey($code)) {
                return null;
            }

            $product = (clone $query)->find((int) $code);

            if ($product === null || (int) ($segments[1] ?? 0) !== (int) $product->supplier_id) {
                return null;
            }

            return $product;
        }

        if ($this->isExactIntegerKey($code)) {
            $product = (clone $query)->find((int) $code);

            if ($product !== null) {
                return $product;
            }
        }

        return $query->where('public_id', $code)->first();
    }

    private function isExactIntegerKey(string $value): bool
    {
        return ctype_digit($value) && (string) (int) $value === $value;
    }

    /**
     * @return array<int, array{branch_key:string,id:int,quantity:int}>|JsonResponse
     */
    private function normalizeCheckoutItems(array $items): array|JsonResponse
    {
        $defaultBranchKey = $this->branchManager->currentBranch()['key'];
        $normalized = [];

        foreach ($items as $item) {
            $id = (int) ($item['id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            $branchKey = strtolower(trim((string) ($item['branch_key'] ?? '')));

            if ($branchKey === '') {
                $branchKey = $defaultBranchKey;
            }

            if ($id <= 0 || $quantity <= 0 || preg_match('/^[a-z0-9_-]+$/', $branchKey) !== 1) {
                return response()->json([
                    'message' => 'Số lượng sản phẩm bán hàng không hợp lệ.',
                ], 422);
            }

            $compositeKey = "{$branchKey}:{$id}";
            $normalized[$compositeKey] = [
                'branch_key' => $branchKey,
                'id' => $id,
                'quantity' => ($normalized[$compositeKey]['quantity'] ?? 0) + $quantity,
            ];
        }

        ksort($normalized);

        return array_values($normalized);
    }

    /**
     * @param  array<int, array{branch_key:string,id:int,quantity:int}>  $items
     * @return array<string, array<int, array{branch_key:string,id:int,quantity:int}>>
     */
    private function groupItemsByBranch(array $items): array
    {
        $grouped = [];

        foreach ($items as $item) {
            $grouped[$item['branch_key']][] = $item;
        }

        ksort($grouped);

        return $grouped;
    }

    /**
     * @param  array<int, \Illuminate\Database\ConnectionInterface>  $connections
     */
    private function rollbackConnections(array $connections): void
    {
        foreach ($connections as $conn) {
            try {
                if ($conn->transactionLevel() > 0) {
                    $conn->rollBack();
                }
            } catch (\Throwable) {
                // Ignore rollback error during failure cleanup
            }
        }
    }

    /**
     * @param  array{key:string,name:string,prefix:string,url:string,is_current:bool,connection:string}  $branch
     */
    private function resolveImageUrl(?string $imagePath, array $branch): ?string
    {
        if (! $imagePath) {
            return null;
        }

        if ($branch['is_current']) {
            if (! Storage::disk('public')->exists($imagePath)) {
                return null;
            }

            return asset('storage/'.$imagePath);
        }

        if ($branch['url'] !== '') {
            return rtrim($branch['url'], '/').'/storage/'.ltrim($imagePath, '/');
        }

        return null;
    }
}
