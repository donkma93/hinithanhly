<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function editPayment(): View
    {
        $bankName = Setting::resolveBankCode(Setting::get('bank_name', env('APP_BANK_NAME', '')));
        $accountNumber = Setting::get('bank_account', env('APP_BANK_ACCOUNT', ''));
        $accountName = Setting::get('bank_account_name', env('APP_BANK_ACCOUNT_NAME', ''));
        $supplierDiscountRates = Setting::supplierDiscountRates();
        $portalCards = Setting::getJson('portal_cards');
        $portalHeroBadge = Setting::get('portal_hero_badge', 'Tra cứu nhà cung cấp');
        $portalHeroTitle = Setting::get('portal_hero_title', 'Tra cứu nhanh doanh số, thanh toán và thông tin cần thiết');
        $portalHeroDescription = Setting::get('portal_hero_description', 'Nhập số điện thoại đã đăng ký để xem ngay tình trạng thanh toán, số tiền và các kỳ doanh số của nhà cung cấp.');

        return view('settings.payment', compact(
            'bankName',
            'accountNumber',
            'accountName',
            'supplierDiscountRates',
            'portalCards',
            'portalHeroBadge',
            'portalHeroTitle',
            'portalHeroDescription'
        ));
    }

    public function updatePayment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account' => ['nullable', 'string', 'max:255'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'supplier_discount_cho_tang' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'supplier_discount_khach_si' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'supplier_discount_ncc_it_san_pham' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'supplier_discount_ncc_nhieu_san_pham' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'supplier_discount_hang_thu_mua' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'store_address' => ['nullable', 'string', 'max:1000'],
            'store_hotline' => ['nullable', 'string', 'max:255'],
            'store_hours' => ['nullable', 'string', 'max:255'],
            'store_map_url' => ['nullable', 'url', 'max:1000'],
            'portal_info_section_title' => ['nullable', 'string', 'max:255'],
            'portal_info_section_intro' => ['nullable', 'string', 'max:1000'],
            'portal_hero_badge' => ['nullable', 'string', 'max:120'],
            'portal_hero_title' => ['nullable', 'string', 'max:255'],
            'portal_hero_description' => ['nullable', 'string', 'max:1000'],
            'portal_cards' => ['nullable', 'array'],
            'portal_cards.*.eyebrow' => ['nullable', 'string', 'max:60'],
            'portal_cards.*.title' => ['nullable', 'string', 'max:255'],
            'portal_cards.*.description' => ['nullable', 'string', 'max:1000'],
        ]);

        Setting::set('bank_name', Setting::resolveBankCode((string) ($data['bank_name'] ?? '')));
        Setting::set('bank_account', $data['bank_account'] ?? '');
        Setting::set('bank_account_name', $data['bank_account_name'] ?? '');

        foreach (Setting::SUPPLIER_DISCOUNT_KEYS as $type) {
            Setting::set("supplier_discount_{$type}", $data["supplier_discount_{$type}"] ?? 0);
        }

        Setting::set('store_address', $data['store_address'] ?? '');
        Setting::set('store_hotline', $data['store_hotline'] ?? '');
        Setting::set('store_hours', $data['store_hours'] ?? '');
        Setting::set('store_map_url', $data['store_map_url'] ?? '');
        Setting::set('portal_info_section_title', $data['portal_info_section_title'] ?? '');
        Setting::set('portal_info_section_intro', $data['portal_info_section_intro'] ?? '');
        Setting::set('portal_hero_badge', $data['portal_hero_badge'] ?? '');
        Setting::set('portal_hero_title', $data['portal_hero_title'] ?? '');
        Setting::set('portal_hero_description', $data['portal_hero_description'] ?? '');
        Setting::set('portal_cards', json_encode($this->normalizePortalCards($data['portal_cards'] ?? []), JSON_UNESCAPED_UNICODE));

        return redirect()->route('settings.payment.edit')->with('status', 'Cập nhật cài đặt hệ thống thành công.');
    }

    private function normalizePortalCards(array $cards): array
    {
        return collect($cards)
            ->map(function (array $card): array {
                return [
                    'eyebrow' => trim((string) ($card['eyebrow'] ?? '')),
                    'title' => trim((string) ($card['title'] ?? '')),
                    'description' => trim((string) ($card['description'] ?? '')),
                ];
            })
            ->filter(fn (array $card): bool => $card['eyebrow'] !== '' || $card['title'] !== '' || $card['description'] !== '')
            ->values()
            ->all();
    }
}
