<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RevenueController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin|super-admin')->only('index');
    }

    public function index(Request $request): View
    {
        $perPage = $this->resolvePerPage($request);
        $startDate = $request->filled('from') ? $request->date('from')->startOfDay() : now()->startOfMonth();
        $endDate = $request->filled('to') ? $request->date('to')->endOfDay() : now()->endOfDay();
        $supplierType = $request->input('supplier_type');

        if ($supplierType) {
            $itemQuery = \App\Models\SaleItem::query()
                ->whereHas('sale', function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('completed_at', [$startDate, $endDate]);
                })
                ->whereHas('product.supplier', function ($query) use ($supplierType) {
                    $query->where('type', $supplierType);
                });

            $summary = [
                'total_sales' => (clone $itemQuery)->distinct('sale_id')->count('sale_id'),
                'total_revenue' => (clone $itemQuery)->sum('line_total'),
                'cash_revenue' => (clone $itemQuery)->whereHas('sale', fn ($q) => $q->where('payment_method', 'cash'))->sum('line_total'),
                'transfer_revenue' => (clone $itemQuery)->whereHas('sale', fn ($q) => $q->where('payment_method', 'transfer'))->sum('line_total'),
                'items_count' => (clone $itemQuery)->sum('quantity'),
            ];

            $sales = Sale::query()
                ->whereBetween('completed_at', [$startDate, $endDate])
                ->whereHas('items.product.supplier', function ($query) use ($supplierType) {
                    $query->where('type', $supplierType);
                })
                ->select(['id', 'public_id', 'user_id', 'payment_method', 'payment_reference', 'total_amount', 'items_count', 'completed_at', 'created_at'])
                ->with([
                    'cashier:id,public_id,name',
                    'items' => function ($query) use ($supplierType) {
                        $query->whereHas('product.supplier', function ($q) use ($supplierType) {
                            $q->where('type', $supplierType);
                        });
                    },
                    'items.product.supplier:id,public_id,name,type'
                ])
                ->latest('completed_at')
                ->paginate($perPage)
                ->withQueryString();
        } else {
            $baseQuery = Sale::query()->whereBetween('completed_at', [$startDate, $endDate]);

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
                ->latest('completed_at')
                ->paginate($perPage)
                ->withQueryString();
        }

        return view('revenue.index', [
            'summary' => $summary,
            'sales' => $sales,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'supplierType' => $supplierType,
        ]);
    }
}