<?php

namespace App\Http\Controllers;

use App\Models\ConsignmentNote;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

        $inventorySummaries = collect();
        if ($supplier) {
            $inventorySummaries = $this->buildInventorySummaries($supplier);
        }

        return view('welcome', [
            'supplier' => $supplier,
            'phone' => $phone,
            'searchPerformed' => $searchPerformed,
            'searchError' => $searchPerformed && $supplier === null
                ? 'Không tìm thấy nhà cung cấp phù hợp. Hãy kiểm tra lại số điện thoại đã đăng ký.'
                : null,
            'inventorySummaries' => $inventorySummaries,
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
                "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone, ''), ' ', ''), '.', ''), '-', ''), '(', ''), ')', ''), '+', '') = ?",
                [$phone]
            )
            ->first();
    }

    private function normalizePhone(string $value): string
    {
        $value = preg_replace('/\D+/', '', $value) ?? '';

        return $value;
    }

    private function buildInventorySummaries(Supplier $supplier): Collection
    {
        $consignments = ConsignmentNote::query()
            ->withTrashed()
            ->where('supplier_id', $supplier->id)
            ->orderBy('sent_date')
            ->orderBy('id')
            ->get(['id', 'sent_date']);

        if ($consignments->isEmpty()) {
            return collect();
        }

        $roundByConsignment = [];
        $rounds = collect();
        $currentRound = 0;
        $previousSentDate = null;

        foreach ($consignments as $consignment) {
            if ($previousSentDate === null || $previousSentDate->diffInDays($consignment->sent_date) > 15) {
                $currentRound++;
            }

            $roundByConsignment[$consignment->id] = $currentRound;
            $round = $rounds->get($currentRound, [
                'round' => $currentRound,
                'first_sent_date' => $consignment->sent_date,
                'last_sent_date' => $consignment->sent_date,
                'products' => collect(),
            ]);
            $round['last_sent_date'] = $consignment->sent_date;
            $rounds->put($currentRound, $round);
            $previousSentDate = $consignment->sent_date;
        }

        $products = Product::query()
            ->where('supplier_id', $supplier->id)
            ->whereIn('consignment_note_id', $consignments->pluck('id'))
            ->whereNull('returned_at')
            ->where('quantity', '>', 0)
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'public_id', 'consignment_note_id', 'name', 'quantity']);

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
            ->map(function (array $round): array {
                $firstSentDate = $round['first_sent_date'];
                $lastSentDate = $round['last_sent_date'];

                return [
                    'round' => $round['round'],
                    'sent_date_label' => $firstSentDate->isSameDay($lastSentDate)
                        ? $firstSentDate->format('d/m/Y')
                        : $firstSentDate->format('d/m/Y').' - '.$lastSentDate->format('d/m/Y'),
                    'product_count' => $round['products']->count(),
                    'stock_quantity' => (int) $round['products']->sum('quantity'),
                    'products' => $round['products'],
                ];
            })
            ->sortByDesc('round')
            ->values();
    }
}
