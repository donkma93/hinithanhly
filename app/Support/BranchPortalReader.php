<?php

namespace App\Support;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BranchPortalReader
{
    public function __construct(private readonly BranchManager $branchManager)
    {
    }

    /**
     * Return all valid branches configured for read-only portal search.
     *
     * @return array<int, array{key:string,name:string,prefix:string,url:string,is_current:bool}>
     */
    public function branches(): array
    {
        return $this->branchManager->branches();
    }

    /**
     * Read store contact & address settings for all available branches.
     *
     * @return array<int, array{key:string,name:string,is_current:bool,address:string,hotline:string,hours:string,map_url:string}>
     */
    public function storeLocations(): array
    {
        $locations = [];

        foreach ($this->branches() as $branch) {
            $connectionName = $this->resolveReadConnectionName($branch);

            if ($connectionName === null) {
                continue;
            }

            $storeInfo = $this->readStoreInfo($connectionName);

            $locations[] = [
                'key' => $branch['key'],
                'name' => $branch['name'],
                'is_current' => $branch['is_current'],
                ...$storeInfo,
            ];
        }

        return $locations;
    }

    /**
     * Search a normalized phone number across all configured branches (read-only).
     *
     * @return array<int, array{
     *     branch_key:string,
     *     branch_name:string,
     *     is_current:bool,
     *     available:bool,
     *     supplier:object|null,
     *     inventory_summaries:Collection<int, array<string, mixed>>,
     *     total_products:int,
     *     total_stock:int,
     *     total_sold:int,
     *     total_revenue:float,
     *     store_info:array{address:string,hotline:string,hours:string,map_url:string}
     * }>
     */
    public function searchByPhone(string $normalizedPhone): array
    {
        $results = [];

        foreach ($this->branches() as $branch) {
            $connectionName = $this->resolveReadConnectionName($branch);

            if ($connectionName === null) {
                $results[] = [
                    'branch_key' => $branch['key'],
                    'branch_name' => $branch['name'],
                    'is_current' => $branch['is_current'],
                    'available' => false,
                    'supplier' => null,
                    'inventory_summaries' => collect(),
                    'total_products' => 0,
                    'total_stock' => 0,
                    'total_sold' => 0,
                    'total_revenue' => 0.0,
                    'store_info' => $this->defaultStoreInfo(),
                ];
                continue;
            }

            $connection = DB::connection($connectionName);
            $supplier = $this->resolveSupplier($connection, $normalizedPhone);
            $inventorySummaries = $supplier !== null
                ? $this->buildInventorySummaries($connection, $connectionName, $supplier)
                : collect();

            $results[] = [
                'branch_key' => $branch['key'],
                'branch_name' => $branch['name'],
                'is_current' => $branch['is_current'],
                'available' => true,
                'supplier' => $supplier,
                'inventory_summaries' => $inventorySummaries,
                'total_products' => (int) $inventorySummaries->sum('product_count'),
                'total_stock' => (int) $inventorySummaries->sum('stock_quantity'),
                'total_sold' => (int) $inventorySummaries->sum('sold_quantity'),
                'total_revenue' => (float) $inventorySummaries->sum('total_revenue'),
                'store_info' => $this->readStoreInfo($connectionName),
            ];
        }

        return $results;
    }

    /**
     * Resolve a read-only connection name pointing to the same physical database
     * with the target branch table prefix, verifying required tables exist.
     *
     * @param  array{key:string,name:string,prefix:string,url?:string,is_current:bool}  $branch
     */
    private function resolveReadConnectionName(array $branch): ?string
    {
        return $this->branchManager->resolveConnectionName($branch, ['suppliers', 'consignment_notes', 'products']);
    }

    private function resolveSupplier(ConnectionInterface $connection, string $normalizedPhone): ?object
    {
        return $connection->table('suppliers')
            ->whereNull('deleted_at')
            ->whereRaw(
                "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone, ''), ' ', ''), '.', ''), '-', ''), '(', ''), ')', ''), '+', '') = ?",
                [$normalizedPhone]
            )
            ->first(['id', 'public_id', 'name', 'phone', 'type']);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function buildInventorySummaries(ConnectionInterface $connection, string $connectionName, object $supplier): Collection
    {
        $supplierId = (int) $supplier->id;
        $tracksExpiry = ! in_array((string) ($supplier->type ?? ''), Supplier::NO_CONSIGNMENT_EXPIRY_TYPES, true);
        $today = Carbon::now()->startOfDay();

        $consignments = $connection->table('consignment_notes')
            ->whereNull('deleted_at')
            ->where('supplier_id', $supplierId)
            ->orderBy('sent_date')
            ->orderBy('id')
            ->get(['id', 'sent_date']);

        if ($consignments->isEmpty()) {
            return collect();
        }

        $roundByConsignment = [];
        $sentDateByConsignment = [];
        $rounds = collect();
        $currentRound = 0;
        $previousSentDate = null;

        foreach ($consignments as $consignment) {
            $sentDate = Carbon::parse($consignment->sent_date)->startOfDay();
            $sentDateByConsignment[$consignment->id] = $sentDate;

            if ($previousSentDate === null || $previousSentDate->diffInDays($sentDate) > 15) {
                $currentRound++;
            }

            $roundByConsignment[$consignment->id] = $currentRound;
            $round = $rounds->get($currentRound, [
                'round' => $currentRound,
                'first_sent_date' => $sentDate,
                'last_sent_date' => $sentDate,
                'products' => collect(),
            ]);
            $round['last_sent_date'] = $sentDate;
            $rounds->put($currentRound, $round);
            $previousSentDate = $sentDate;
        }

        $rawProducts = $connection->table('products')
            ->whereNull('deleted_at')
            ->where('supplier_id', $supplierId)
            ->whereIn('consignment_note_id', $consignments->pluck('id')->all())
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'public_id', 'consignment_note_id', 'name', 'sale_price', 'quantity', 'returned_at']);

        $salesByProduct = collect();
        if ($rawProducts->isNotEmpty() && Schema::connection($connectionName)->hasTable('sale_items')) {
            $salesByProduct = $connection->table('sale_items')
                ->whereIn('product_id', $rawProducts->pluck('id')->all())
                ->groupBy('product_id')
                ->selectRaw('product_id, SUM(quantity) as sold_quantity, SUM(line_total) as total_revenue')
                ->get()
                ->keyBy('product_id');
        }

        $products = $rawProducts
            ->map(function (object $product) use ($salesByProduct, $sentDateByConsignment, $tracksExpiry, $today): object {
                $publicId = (string) ($product->public_id ?? '');
                $product->public_id_display = ltrim($publicId, '0') ?: '0';

                $salesRow = $salesByProduct->get($product->id);
                $product->sold_quantity = (int) ($salesRow->sold_quantity ?? 0);
                $product->revenue = (float) ($salesRow->total_revenue ?? 0);
                $product->is_returned = $product->returned_at !== null;
                $product->stock_quantity = $product->is_returned ? 0 : (int) $product->quantity;

                $sentDate = $sentDateByConsignment[$product->consignment_note_id] ?? $today;
                $storageDays = $today->greaterThanOrEqualTo($sentDate) ? $sentDate->diffInDays($today) : 0;
                $product->storage_days = $storageDays;
                $product->storage_label = $storageDays.' ngày';

                $expiry = $this->resolveExpiryMeta($sentDate, $today, $tracksExpiry, $product->is_returned);
                $product->due_date_label = $expiry['due_date_label'];
                $product->expiry_label = $expiry['expiry_label'];
                $product->expiry_tone = $expiry['expiry_tone'];

                return $product;
            })
            ->filter(fn (object $product): bool => $product->stock_quantity > 0 || $product->sold_quantity > 0)
            ->values();

        foreach ($products as $product) {
            $roundNumber = $roundByConsignment[$product->consignment_note_id] ?? null;

            if ($roundNumber === null || ! $rounds->has($roundNumber)) {
                continue;
            }

            $round = $rounds->get($roundNumber);
            $round['products']->push($product);
            $rounds->put($roundNumber, $round);
        }

        return $rounds
            ->map(function (array $round) use ($today, $tracksExpiry): array {
                $firstSentDate = $round['first_sent_date'];
                $lastSentDate = $round['last_sent_date'];
                $storageDays = $today->greaterThanOrEqualTo($firstSentDate) ? $firstSentDate->diffInDays($today) : 0;
                $expiry = $this->resolveExpiryMeta($firstSentDate, $today, $tracksExpiry, false);

                return [
                    'round' => $round['round'],
                    'sent_date_label' => $firstSentDate->isSameDay($lastSentDate)
                        ? $firstSentDate->format('d/m/Y')
                        : $firstSentDate->format('d/m/Y').' - '.$lastSentDate->format('d/m/Y'),
                    'storage_days' => $storageDays,
                    'storage_label' => $storageDays.' ngày',
                    'due_date_label' => $expiry['due_date_label'],
                    'expiry_label' => $expiry['expiry_label'],
                    'expiry_tone' => $expiry['expiry_tone'],
                    'product_count' => $round['products']->count(),
                    'stock_quantity' => (int) $round['products']->sum('stock_quantity'),
                    'sold_quantity' => (int) $round['products']->sum('sold_quantity'),
                    'total_revenue' => (float) $round['products']->sum('revenue'),
                ];
            })
            ->sortByDesc('round')
            ->values();
    }

    /**
     * @return array{due_date_label:?string,expiry_label:string,expiry_tone:string}
     */
    private function resolveExpiryMeta(Carbon $sentDate, Carbon $today, bool $tracksExpiry, bool $isReturned): array
    {
        if ($isReturned) {
            return [
                'due_date_label' => null,
                'expiry_label' => 'Đã trả',
                'expiry_tone' => 'gray',
            ];
        }

        if (! $tracksExpiry) {
            return [
                'due_date_label' => null,
                'expiry_label' => 'Không có hạn',
                'expiry_tone' => 'gray',
            ];
        }

        $dueDate = $sentDate->copy()->addDays(Product::CONSIGNMENT_TERM_DAYS);
        $dueDateLabel = $dueDate->format('d/m/Y');
        $diff = $today->diffInDays($dueDate);

        if ($today->greaterThan($dueDate)) {
            return [
                'due_date_label' => $dueDateLabel,
                'expiry_label' => 'Quá hạn '.$diff.' ngày',
                'expiry_tone' => 'danger',
            ];
        }

        if ($diff === 0) {
            return [
                'due_date_label' => $dueDateLabel,
                'expiry_label' => 'Hết hạn hôm nay',
                'expiry_tone' => 'warning',
            ];
        }

        if ($diff <= Product::CONSIGNMENT_WARNING_DAYS) {
            return [
                'due_date_label' => $dueDateLabel,
                'expiry_label' => 'Còn '.$diff.' ngày',
                'expiry_tone' => 'warning',
            ];
        }

        return [
            'due_date_label' => $dueDateLabel,
            'expiry_label' => 'Còn '.$diff.' ngày',
            'expiry_tone' => 'success',
        ];
    }

    /**
     * @return array{address:string,hotline:string,hours:string,map_url:string}
     */
    private function readStoreInfo(string $connectionName): array
    {
        $defaults = $this->defaultStoreInfo();

        try {
            if (! Schema::connection($connectionName)->hasTable('settings')) {
                return $defaults;
            }

            $rows = DB::connection($connectionName)
                ->table('settings')
                ->whereIn('key', ['store_address', 'store_hotline', 'store_hours', 'store_map_url'])
                ->pluck('value', 'key');

            return [
                'address' => filled($rows->get('store_address')) ? (string) $rows->get('store_address') : $defaults['address'],
                'hotline' => filled($rows->get('store_hotline')) ? (string) $rows->get('store_hotline') : $defaults['hotline'],
                'hours' => filled($rows->get('store_hours')) ? (string) $rows->get('store_hours') : $defaults['hours'],
                'map_url' => filled($rows->get('store_map_url')) ? (string) $rows->get('store_map_url') : '',
            ];
        } catch (Throwable) {
            return $defaults;
        }
    }

    /**
     * @return array{address:string,hotline:string,hours:string,map_url:string}
     */
    private function defaultStoreInfo(): array
    {
        return [
            'address' => 'Địa chỉ cửa hàng đang được cập nhật',
            'hotline' => 'Liên hệ trực tiếp cửa hàng để được hỗ trợ',
            'hours' => '08:30 - 21:00 mỗi ngày',
            'map_url' => '',
        ];
    }
}
