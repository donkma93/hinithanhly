<?php

namespace App\Http\Controllers;

use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupplierPortalController extends Controller
{
    public function index(Request $request): View
    {
        $phone = $this->normalizePhone((string) $request->input('phone', ''));
        $searchPerformed = $phone !== '';

        $supplier = $searchPerformed
            ? $this->resolveSupplier($phone)
            : null;

        $paymentSummaries = collect();
        if ($supplier) {
            $paymentSummaries = $this->buildPaymentSummaries($supplier);
        }

        return view('welcome', [
            'supplier' => $supplier,
            'phone' => $phone,
            'searchPerformed' => $searchPerformed,
            'searchError' => $searchPerformed && $supplier === null
                ? 'Không tìm thấy nhà cung cấp phù hợp. Hãy kiểm tra lại số điện thoại đã đăng ký.'
                : null,
            'paymentSummaries' => $paymentSummaries,
            'portalHeroBadge' => Setting::get('portal_hero_badge', 'Tra cứu nhà cung cấp'),
            'portalHeroTitle' => Setting::get('portal_hero_title', 'Tra cứu nhanh doanh số, thanh toán và thông tin cần thiết'),
            'portalHeroDescription' => Setting::get('portal_hero_description', 'Nhập số điện thoại đã đăng ký để xem ngay tình trạng thanh toán, số tiền và các kỳ doanh số của nhà cung cấp.'),
            'portalInfoSectionTitle' => Setting::get('portal_info_section_title', 'Thông tin từ cửa hàng'),
            'portalInfoSectionIntro' => Setting::get('portal_info_section_intro', 'Cập nhật những thông tin quan trọng để nhà cung cấp nắm nhanh ngay ngoài trang chủ.'),
            'portalCards' => collect(Setting::getJson('portal_cards'))
                ->map(function (array $card): array {
                    return [
                        'eyebrow' => trim((string) ($card['eyebrow'] ?? '')),
                        'title' => trim((string) ($card['title'] ?? '')),
                        'description' => trim((string) ($card['description'] ?? '')),
                    ];
                })
                ->filter(fn (array $card): bool => $card['eyebrow'] !== '' || $card['title'] !== '' || $card['description'] !== '')
                ->values(),
            'portalAddress' => Setting::get('store_address', 'Địa chỉ cửa hàng đang được cập nhật'),
            'portalHotline' => Setting::get('store_hotline', 'Liên hệ trực tiếp cửa hàng để được hỗ trợ'),
            'portalHours' => Setting::get('store_hours', '08:30 - 21:00 mỗi ngày'),
            'portalMapUrl' => Setting::get('store_map_url', ''),
        ]);
    }

    private function resolveSupplier(string $phone): ?Supplier
    {
        return Supplier::query()
            ->whereRaw(
                "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone, ''), ' ', ''), '.', ''), '-', ''), '(', ''), ')', ''), '+', '') LIKE ?",
                ['%'.$phone.'%']
            )
            ->first();
    }

    private function normalizePhone(string $value): string
    {
        $value = preg_replace('/\D+/', '', $value) ?? '';

        return $value;
    }

    private function buildPaymentSummaries(Supplier $supplier): Collection
    {
        $salesByMonth = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('products.supplier_id', $supplier->id)
            ->groupBy(DB::raw("DATE_FORMAT(COALESCE(sales.completed_at, sales.created_at), '%Y-%m')"))
            ->orderByDesc(DB::raw("DATE_FORMAT(COALESCE(sales.completed_at, sales.created_at), '%Y-%m')"))
            ->get([
                DB::raw("DATE_FORMAT(COALESCE(sales.completed_at, sales.created_at), '%Y-%m') as period_key"),
                DB::raw('SUM(sale_items.line_total) as gross_amount'),
                DB::raw('SUM(sale_items.quantity) as units_sold'),
            ])
            ->keyBy('period_key');

        $paymentsByMonth = SupplierPayment::query()
            ->where('supplier_id', $supplier->id)
            ->whereNotNull('paid_at')
            ->get()
            ->keyBy(fn (SupplierPayment $payment) => $payment->period_from?->format('Y-m'));

        $periodKeys = $salesByMonth->keys()
            ->merge($paymentsByMonth->keys())
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        $discountRate = (float) Setting::supplierDiscountRate($supplier->type);

        return $periodKeys->map(function (string $periodKey) use ($salesByMonth, $paymentsByMonth, $discountRate) {
            $sales = $salesByMonth->get($periodKey);
            $payment = $paymentsByMonth->get($periodKey);
            $grossAmount = (float) ($sales->gross_amount ?? $payment?->gross_amount ?? 0);
            $unitsSold = (int) ($sales->units_sold ?? 0);
            $discountAmount = $payment
                ? (float) $payment->discount_amount
                : round($grossAmount * $discountRate / 100, 2);
            $payableAmount = $payment
                ? (float) $payment->payable_amount
                : max(0, round($grossAmount - $discountAmount, 2));
            $isPaid = $payment !== null;

            return [
                'status' => $isPaid ? 'paid' : 'unpaid',
                'status_label' => $isPaid ? 'Đã thanh toán' : 'Chưa thanh toán',
                'payable_amount' => $payableAmount,
                'units_sold' => $unitsSold,
                'period_label' => substr($periodKey, 5, 2).'/'.substr($periodKey, 0, 4),
            ];
        });
    }
}
