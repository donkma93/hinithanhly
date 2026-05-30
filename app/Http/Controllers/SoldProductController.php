<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SoldProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:sales.records.view')->only('index');
    }

    public function index(Request $request): View
    {
        $publicId = trim($request->string('public_id')->toString());
        $paymentMethod = trim($request->string('payment_method')->toString());
        $perPage = $this->resolvePerPage($request);

        $sales = Sale::query()
            ->select(['id', 'public_id', 'user_id', 'payment_method', 'payment_reference', 'total_amount', 'items_count', 'completed_at', 'created_at'])
            ->with([
                'cashier:id,public_id,name',
                'items:id,public_id,sale_id,product_id,product_public_id,product_name,quantity,unit_price,line_total',
            ])
            ->when($publicId !== '', fn ($query) => $query->where('public_id', $publicId))
            ->when($paymentMethod !== '', fn ($query) => $query->where('payment_method', $paymentMethod))
            ->latest('completed_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('sold-products.index', [
            'sales' => $sales,
        ]);
    }
}