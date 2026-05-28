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

        return view('settings.payment', compact('bankName', 'accountNumber', 'accountName', 'supplierDiscountRates'));
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
        ]);

        Setting::set('bank_name', Setting::resolveBankCode((string) ($data['bank_name'] ?? '')));
        Setting::set('bank_account', $data['bank_account'] ?? '');
        Setting::set('bank_account_name', $data['bank_account_name'] ?? '');

        foreach (Setting::SUPPLIER_DISCOUNT_KEYS as $type) {
            Setting::set("supplier_discount_{$type}", $data["supplier_discount_{$type}"] ?? 0);
        }

        return redirect()->route('settings.payment.edit')->with('status', 'Cập nhật thông tin ngân hàng thành công.');
    }
}
