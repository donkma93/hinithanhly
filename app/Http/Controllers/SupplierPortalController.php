<?php

namespace App\Http\Controllers;

use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierPortalController extends Controller
{
    public function index(Request $request): View
    {
        $supplierCode = $this->normalizeSupplierCode((string) $request->input('supplier_code', ''));
        $phone = $this->normalizePhone((string) $request->input('phone', ''));
        $searchPerformed = $supplierCode !== '' || $phone !== '';

        $selectedMonth = trim((string) $request->string('month'));
        $monthDate = $selectedMonth !== ''
            ? Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth()
            : now()->startOfMonth();

        $selectedMonth = $monthDate->format('Y-m');
        $startDate = $monthDate->copy()->startOfMonth()->startOfDay();
        $endDate = $monthDate->copy()->endOfMonth()->endOfDay();

        $supplier = $searchPerformed
            ? $this->resolveSupplier($supplierCode, $phone)
            : null;

        $paymentSummary = null;
        if ($supplier) {
            $paymentSummary = $this->buildPaymentSummary($supplier, $startDate, $endDate);
        }

        return view('welcome', [
            'supplier' => $supplier,
            'supplierCode' => $supplierCode,
            'phone' => $phone,
            'searchPerformed' => $searchPerformed,
            'searchError' => $searchPerformed && $supplier === null
                ? 'Không tìm thấy nhà cung cấp phù hợp. Hãy kiểm tra lại mã NCC hoặc số điện thoại.'
                : null,
            'selectedMonth' => $selectedMonth,
            'paymentSummary' => $paymentSummary,
            'portalAddress' => Setting::get('store_address', 'Địa chỉ cửa hàng đang được cập nhật'),
            'portalHotline' => Setting::get('store_hotline', 'Liên hệ trực tiếp cửa hàng để được hỗ trợ'),
            'portalHours' => Setting::get('store_hours', '08:30 - 21:00 mỗi ngày'),
            'portalMapUrl' => Setting::get('store_map_url', ''),
        ]);
    }

    private function resolveSupplier(string $supplierCode, string $phone): ?Supplier
    {
        if ($supplierCode !== '') {
            $supplier = Supplier::query()
                ->where('public_id', $supplierCode)
                ->first();

            if ($supplier !== null) {
                return $supplier;
            }
        }

        if ($phone !== '') {
            return Supplier::query()
                ->whereRaw(
                    "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone, ''), ' ', ''), '.', ''), '-', ''), '(', ''), ')', ''), '+', '') LIKE ?",
                    ['%'.$phone.'%']
                )
                ->first();
        }

        return null;
    }

    private function normalizeSupplierCode(string $value): string
    {
        $value = trim($value);
        $value = ltrim($value, '#');
        $value = preg_replace('/\s+/', '', $value) ?? '';
        $value = ltrim($value, '0');

        return $value === '' ? '' : $value;
    }

    private function normalizePhone(string $value): string
    {
        $value = preg_replace('/\D+/', '', $value) ?? '';

        return $value;
    }

    private function buildPaymentSummary(Supplier $supplier, Carbon $startDate, Carbon $endDate): array
    {
        $baseQuery = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('products.supplier_id', $supplier->id)
            ->whereNotNull('sales.completed_at')
            ->whereBetween('sales.completed_at', [$startDate, $endDate]);

        $grossAmount = (float) (clone $baseQuery)->sum('sale_items.line_total');
        $unitsSold = (int) (clone $baseQuery)->sum('sale_items.quantity');

        $discountRate = (float) Setting::supplierDiscountRate($supplier->type);
        $discountAmount = round($grossAmount * $discountRate / 100, 2);
        $payableAmount = max(0, round($grossAmount - $discountAmount, 2));

        $payment = SupplierPayment::query()
            ->where('supplier_id', $supplier->id)
            ->whereDate('period_from', '>=', $startDate->toDateString())
            ->whereDate('period_to', '<=', $endDate->toDateString())
            ->whereNotNull('paid_at')
            ->first();

        $isPaid = $payment !== null;

        return [
            'gross_amount' => $grossAmount,
            'discount_rate' => $discountRate,
            'discount_amount' => $discountAmount,
            'payable_amount' => $payableAmount,
            'units_sold' => $unitsSold,
            'status' => $isPaid ? 'paid' : 'unpaid',
            'status_label' => $isPaid ? 'Đã thanh toán' : 'Chưa thanh toán',
            'payment' => $payment,
            'period_label' => $startDate->format('m/Y'),
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }
}
