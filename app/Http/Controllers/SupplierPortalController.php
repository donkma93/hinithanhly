<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierPortalController extends Controller
{
    private const STATUS_FILTERS = ['all', 'active', 'expiring_soon', 'expired', 'returned'];

    public function index(Request $request): View
    {
        $supplierCode = $this->normalizeSupplierCode((string) $request->input('supplier_code', ''));
        $phone = $this->normalizePhone((string) $request->input('phone', ''));
        $statusFilter = $this->normalizeStatusFilter($request->string('status', 'all')->toString());
        $searchPerformed = $supplierCode !== '' || $phone !== '';

        $supplier = $searchPerformed
            ? $this->resolveSupplier($supplierCode, $phone)
            : null;

        $statusSummary = $supplier
            ? $this->buildStatusSummary((int) $supplier->id)
            : null;

        $products = $supplier
            ? $this->buildProductsQuery((int) $supplier->id, $statusFilter)
                ->paginate(8)
                ->withQueryString()
            : null;

        return view('welcome', [
            'supplier' => $supplier,
            'supplierCode' => $supplierCode,
            'phone' => $phone,
            'searchPerformed' => $searchPerformed,
            'searchError' => $searchPerformed && $supplier === null
                ? 'Không tìm thấy nhà cung cấp phù hợp. Hãy kiểm tra lại mã NCC hoặc số điện thoại.'
                : null,
            'statusFilter' => $statusFilter,
            'statusOptions' => $this->statusOptions(),
            'statusSummary' => $statusSummary,
            'products' => $products,
            'consignmentTermDays' => Product::CONSIGNMENT_TERM_DAYS,
            'consignmentWarningDays' => Product::CONSIGNMENT_WARNING_DAYS,
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

    private function normalizeStatusFilter(string $status): string
    {
        return in_array($status, self::STATUS_FILTERS, true) ? $status : 'all';
    }

    /**
     * @return array<string, array{label:string,description:string}>
     */
    private function statusOptions(): array
    {
        return [
            'all' => [
                'label' => 'Tất cả',
                'description' => 'Xem toàn bộ sản phẩm của NCC',
            ],
            'active' => [
                'label' => 'Đang hiệu lực',
                'description' => 'Còn hơn 7 ngày trước hạn',
            ],
            'expiring_soon' => [
                'label' => 'Sắp hết hạn',
                'description' => 'Còn tối đa 7 ngày',
            ],
            'expired' => [
                'label' => 'Quá hạn',
                'description' => 'Đã vượt mốc 45 ngày',
            ],
            'returned' => [
                'label' => 'Đã trả',
                'description' => 'Đã đánh dấu trả cho người gửi',
            ],
        ];
    }

    /**
     * @return array{0:string,1:string}
     */
    private function consignmentWindows(): array
    {
        return [
            now()->subDays(Product::CONSIGNMENT_TERM_DAYS)->toDateString(),
            now()->subDays(Product::CONSIGNMENT_TERM_DAYS - Product::CONSIGNMENT_WARNING_DAYS)->toDateString(),
        ];
    }

    /**
     * @return array{all:int,active:int,expiring_soon:int,expired:int,returned:int}
     */
    private function buildStatusSummary(int $supplierId): array
    {
        [$warningWindowStart, $warningWindowEnd] = $this->consignmentWindows();

        $baseQuery = Product::query()
            ->where('supplier_id', $supplierId);

        return [
            'all' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)
                ->whereNull('returned_at')
                ->where('quantity', '>', 0)
                ->whereHas('consignmentNote', function (Builder $query) use ($warningWindowEnd): void {
                    $query->whereDate('sent_date', '>', $warningWindowEnd);
                })
                ->count(),
            'expiring_soon' => (clone $baseQuery)
                ->whereNull('returned_at')
                ->where('quantity', '>', 0)
                ->whereHas('consignmentNote', function (Builder $query) use ($warningWindowStart, $warningWindowEnd): void {
                    $query->whereBetween('sent_date', [$warningWindowStart, $warningWindowEnd]);
                })
                ->count(),
            'expired' => (clone $baseQuery)
                ->whereNull('returned_at')
                ->where('quantity', '>', 0)
                ->whereHas('consignmentNote', function (Builder $query) use ($warningWindowStart): void {
                    $query->whereDate('sent_date', '<', $warningWindowStart);
                })
                ->count(),
            'returned' => (clone $baseQuery)
                ->whereNotNull('returned_at')
                ->count(),
        ];
    }

    private function buildProductsQuery(int $supplierId, string $statusFilter): Builder
    {
        [$warningWindowStart, $warningWindowEnd] = $this->consignmentWindows();

        $query = Product::query()
            ->select('products.*')
            ->leftJoin('consignment_notes', 'consignment_notes.id', '=', 'products.consignment_note_id')
            ->with([
                'category:id,public_id,name',
                'consignmentNote:id,public_id,supplier_id,sent_date',
                'returner:id,public_id,name',
            ])
            ->where('products.supplier_id', $supplierId);

        return match ($statusFilter) {
            'active' => $query
                ->whereNull('products.returned_at')
                ->where('products.quantity', '>', 0)
                ->whereDate('consignment_notes.sent_date', '>', $warningWindowEnd),
            'expiring_soon' => $query
                ->whereNull('products.returned_at')
                ->where('products.quantity', '>', 0)
                ->whereBetween('consignment_notes.sent_date', [$warningWindowStart, $warningWindowEnd]),
            'expired' => $query
                ->whereNull('products.returned_at')
                ->where('products.quantity', '>', 0)
                ->whereDate('consignment_notes.sent_date', '<', $warningWindowStart),
            'returned' => $query
                ->whereNotNull('products.returned_at'),
            default => $query,
        };
    }
}
