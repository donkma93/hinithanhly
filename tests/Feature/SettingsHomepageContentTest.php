<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SettingsHomepageContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_homepage_only_displays_store_contact_settings(): void
    {
        $admin = User::query()->where('email', 'admin@kygui.local')->firstOrFail();
        $this->actingAs($admin);

        $response = $this->post(route('settings.payment.update'), [
            'bank_name' => '',
            'bank_account' => '',
            'bank_account_name' => '',
            'supplier_discount_cho_tang' => 0,
            'supplier_discount_khach_si' => 0,
            'supplier_discount_ncc_it_san_pham' => 0,
            'supplier_discount_ncc_nhieu_san_pham' => 0,
            'supplier_discount_hang_thu_mua' => 0,
            'store_address' => '123 Duong ABC, Quan 1, TP HCM',
            'store_hotline' => '0909 000 111',
            'store_hours' => '08:00 - 21:30',
            'store_map_url' => 'https://example.com/map',
            'portal_hero_badge' => 'TRA CUU NCC',
            'portal_hero_title' => 'Tra cuu ton kho theo lan ky gui',
            'portal_hero_description' => 'Nhap so dien thoai de xem san pham va so luong con trong kho.',
            'portal_info_section_title' => 'Thong tin noi bat',
            'portal_info_section_intro' => 'Noi dung do admin tu cau hinh.',
            'portal_cards' => [
                [
                    'eyebrow' => 'THONG BAO',
                    'title' => 'Nhan hang truoc 17h',
                    'description' => 'Cua hang nhan hang ky gui moi ngay den 17h.',
                ],
                [
                    'eyebrow' => 'HO TRO',
                    'title' => 'Bao cao hang tuan',
                    'description' => 'Ket qua doanh so duoc cap nhat theo tung ky trong bang tra cuu.',
                ],
            ],
        ]);

        $response->assertRedirect(route('settings.payment.edit'));

        $home = $this->get(route('home'));

        $home->assertOk();
        $home->assertSee('Tra cứu tồn kho nhà cung cấp');
        $home->assertSee('Số điện thoại');
        $home->assertDontSee('TRA CUU NCC');
        $home->assertDontSee('Tra cuu ton kho theo lan ky gui');
        $home->assertDontSee('Thong tin noi bat');
        $home->assertDontSee('Nhan hang truoc 17h');
        $home->assertDontSee('Bao cao hang tuan');
        $home->assertDontSee('HƯỚNG DẪN NHANH');
        $home->assertDontSee('NỘI DUNG TRANG CHỦ');
        $home->assertSee('123 Duong ABC, Quan 1, TP HCM');
        $home->assertSee('0909 000 111');
        $home->assertSee('08:00 - 21:30');
        $home->assertSee('href="https://example.com/map"', false);
    }
}
