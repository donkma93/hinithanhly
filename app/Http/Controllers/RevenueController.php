<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RevenueController extends Controller
{
    private const REPORT_TIMEZONE = 'Asia/Bangkok';

    public function __construct()
    {
        $this->middleware('permission:sales.revenue.view')->only('index');
    }

    public function index(Request $request): View
    {
        $perPage = $this->resolvePerPage($request);
        $startDateLocal = $request->filled('from')
            ? Carbon::parse((string) $request->input('from'), self::REPORT_TIMEZONE)->startOfDay()
            : now(self::REPORT_TIMEZONE)->startOfMonth();
        $endDateLocal = $request->filled('to')
            ? Carbon::parse((string) $request->input('to'), self::REPORT_TIMEZONE)->endOfDay()
            : now(self::REPORT_TIMEZONE)->endOfDay();
        $startDate = $startDateLocal->copy()->utc();
        $endDate = $endDateLocal->copy()->utc();
        $supplierType = trim((string) $request->input('supplier_type'));
        $allowedRevenueSupplierTypes = collect(Supplier::TYPES)
            ->except('ncc_nhieu_san_pham')
            ->keys()
            ->all();

        if (! in_array($supplierType, $allowedRevenueSupplierTypes, true)) {
            $supplierType = '';
        }

        $hasSupplierTypeFilter = $supplierType !== '';
        $requestedSupplierId = $request->integer('supplier_id');
        $selectedSupplier = null;

        if ($hasSupplierTypeFilter && $requestedSupplierId > 0) {
            $selectedSupplier = Supplier::query()
                ->where('type', $supplierType)
                ->find($requestedSupplierId);
        }

        $selectedSupplierId = $selectedSupplier?->id;
        $suppliersForType = $hasSupplierTypeFilter
            ? Supplier::query()
                ->where('type', $supplierType)
                ->orderBy('name')
                ->get(['id', 'public_id', 'name', 'type'])
            : collect();

        if ($hasSupplierTypeFilter) {
            $itemQuery = SaleItem::query()
                ->whereHas('sale', function ($query) use ($startDate, $endDate) {
                    $query->where(function ($saleQuery) use ($startDate, $endDate): void {
                        $saleQuery->whereBetween('completed_at', [$startDate, $endDate])
                            ->orWhere(function ($fallbackQuery) use ($startDate, $endDate): void {
                                $fallbackQuery->whereNull('completed_at')
                                    ->whereBetween('created_at', [$startDate, $endDate]);
                            });
                    });
                })
                ->whereHas('product.supplier', function ($query) use ($supplierType, $selectedSupplierId) {
                    $query->where('type', $supplierType);

                    if ($selectedSupplierId !== null) {
                        $query->whereKey($selectedSupplierId);
                    }
                });

            $summary = [
                'total_sales' => (clone $itemQuery)->distinct('sale_id')->count('sale_id'),
                'total_revenue' => (clone $itemQuery)->sum('line_total'),
                'cash_revenue' => (clone $itemQuery)->whereHas('sale', fn ($q) => $q->where('payment_method', 'cash'))->sum('line_total'),
                'transfer_revenue' => (clone $itemQuery)->whereHas('sale', fn ($q) => $q->where('payment_method', 'transfer'))->sum('line_total'),
                'items_count' => (clone $itemQuery)->sum('quantity'),
            ];

            $sales = Sale::query()
                ->where(function ($query) use ($startDate, $endDate): void {
                    $query->whereBetween('completed_at', [$startDate, $endDate])
                        ->orWhere(function ($fallbackQuery) use ($startDate, $endDate): void {
                            $fallbackQuery->whereNull('completed_at')
                                ->whereBetween('created_at', [$startDate, $endDate]);
                        });
                })
                ->whereHas('items.product.supplier', function ($query) use ($supplierType, $selectedSupplierId) {
                    $query->where('type', $supplierType);

                    if ($selectedSupplierId !== null) {
                        $query->whereKey($selectedSupplierId);
                    }
                })
                ->select(['id', 'public_id', 'user_id', 'payment_method', 'payment_reference', 'total_amount', 'items_count', 'completed_at', 'created_at'])
                ->with([
                    'cashier:id,public_id,name',
                    'items' => function ($query) use ($supplierType, $selectedSupplierId) {
                        $query->whereHas('product.supplier', function ($q) use ($supplierType, $selectedSupplierId) {
                            $q->where('type', $supplierType);

                            if ($selectedSupplierId !== null) {
                                $q->whereKey($selectedSupplierId);
                            }
                        });
                    },
                    'items.product.supplier:id,public_id,name,type'
                ])
                ->orderByRaw('COALESCE(completed_at, created_at) DESC')
                ->paginate($perPage)
                ->withQueryString();
        } else {
            $baseQuery = Sale::query()
                ->where(function ($query) use ($startDate, $endDate): void {
                    $query->whereBetween('completed_at', [$startDate, $endDate])
                        ->orWhere(function ($fallbackQuery) use ($startDate, $endDate): void {
                            $fallbackQuery->whereNull('completed_at')
                                ->whereBetween('created_at', [$startDate, $endDate]);
                        });
                });

            $summary = [
                'total_sales' => (clone $baseQuery)->count(),
                'total_revenue' => (clone $baseQuery)->sum('total_amount'),
                'cash_revenue' => (clone $baseQuery)->where('payment_method', 'cash')->sum('total_amount'),
                'transfer_revenue' => (clone $baseQuery)->where('payment_method', 'transfer')->sum('total_amount'),
                'items_count' => (clone $baseQuery)->sum('items_count'),
            ];

            $sales = (clone $baseQuery)
                ->select(['id', 'public_id', 'user_id', 'payment_method', 'payment_reference', 'total_amount', 'items_count', 'completed_at', 'created_at'])
                ->with([
                    'cashier:id,public_id,name',
                    'items.product.supplier:id,public_id,name,type'
                ])
                ->orderByRaw('COALESCE(completed_at, created_at) DESC')
                ->paginate($perPage)
                ->withQueryString();
        }

        $supplierBreakdownRows = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('suppliers', 'products.supplier_id', '=', 'suppliers.id')
            ->where(function ($query) use ($startDate, $endDate): void {
                $query->whereBetween('sales.completed_at', [$startDate, $endDate])
                    ->orWhere(function ($fallbackQuery) use ($startDate, $endDate): void {
                        $fallbackQuery->whereNull('sales.completed_at')
                            ->whereBetween('sales.created_at', [$startDate, $endDate]);
                    });
            })
            ->when($hasSupplierTypeFilter, fn ($query) => $query->where('suppliers.type', $supplierType))
            ->when($selectedSupplierId !== null, fn ($query) => $query->where('suppliers.id', $selectedSupplierId))
            ->groupBy('suppliers.type', 'suppliers.id', 'suppliers.public_id', 'suppliers.name')
            ->orderBy('suppliers.type')
            ->orderBy('suppliers.name')
            ->selectRaw('
                suppliers.type as supplier_type,
                suppliers.id as supplier_id,
                suppliers.public_id as supplier_public_id,
                suppliers.name as supplier_name,
                COUNT(DISTINCT sales.id) as total_sales,
                SUM(sale_items.line_total) as total_revenue,
                SUM(CASE WHEN sales.payment_method = "cash" THEN sale_items.line_total ELSE 0 END) as cash_revenue,
                SUM(CASE WHEN sales.payment_method = "transfer" THEN sale_items.line_total ELSE 0 END) as transfer_revenue,
                SUM(sale_items.quantity) as items_count
            ')
            ->get();

        $supplierTypeSummaries = $supplierBreakdownRows
            ->groupBy(fn ($row) => $row->supplier_type ?: 'unknown')
            ->map(function ($rows, $typeKey) {
                return [
                    'type' => $typeKey,
                    'label' => Supplier::labelForType($typeKey === 'unknown' ? null : $typeKey),
                    'supplier_count' => $rows->count(),
                    'total_sales' => (int) $rows->sum('total_sales'),
                    'total_revenue' => (float) $rows->sum('total_revenue'),
                    'cash_revenue' => (float) $rows->sum('cash_revenue'),
                    'transfer_revenue' => (float) $rows->sum('transfer_revenue'),
                    'items_count' => (int) $rows->sum('items_count'),
                    'suppliers' => $rows->map(function ($row) {
                        return [
                            'id' => (int) $row->supplier_id,
                            'public_id' => (string) $row->supplier_public_id,
                            'name' => (string) $row->supplier_name,
                            'total_sales' => (int) $row->total_sales,
                            'total_revenue' => (float) $row->total_revenue,
                            'cash_revenue' => (float) $row->cash_revenue,
                            'transfer_revenue' => (float) $row->transfer_revenue,
                            'items_count' => (int) $row->items_count,
                        ];
                    })->values(),
                ];
            })
            ->sortByDesc('total_revenue')
            ->values();

        return view('revenue.index', [
            'summary' => $summary,
            'sales' => $sales,
            'startDate' => $startDateLocal,
            'endDate' => $endDateLocal,
            'reportTimezone' => self::REPORT_TIMEZONE,
            'supplierType' => $hasSupplierTypeFilter ? $supplierType : null,
            'suppliersForType' => $suppliersForType,
            'selectedSupplierId' => $selectedSupplierId,
            'supplierTypeSummaries' => $supplierTypeSummaries,
        ]);
    }
}
