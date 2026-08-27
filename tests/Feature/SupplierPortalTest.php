<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ConsignmentNote;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SupplierPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_portal_searches_by_phone_and_shows_inventory_by_consignment_round_without_prices(): void
    {
        $user = User::create([
            'name' => 'Nhân viên tra cứu',
            'email' => 'portal@example.com',
            'password' => 'password',
        ]);

        $category = Category::create([
            'name' => 'Áo nữ',
            'description' => null,
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'responsible_user_id' => $user->id,
            'type' => 'ncc_it_san_pham',
            'name' => 'Nhà cung cấp Portal',
            'phone' => '0901 234 567',
            'bank_name' => 'VCB',
            'bank_account_name' => 'Nhà cung cấp Portal',
            'bank_account_number' => '123456789',
            'notes' => null,
        ]);

        $consignment = ConsignmentNote::create([
            'responsible_user_id' => $user->id,
            'supplier_id' => $supplier->id,
            'sent_date' => Carbon::create(2026, 6, 5)->toDateString(),
            'quantity' => 1,
            'notes' => null,
        ]);

        $mayConsignment = ConsignmentNote::create([
            'responsible_user_id' => $user->id,
            'supplier_id' => $supplier->id,
            'sent_date' => Carbon::create(2026, 5, 5)->toDateString(),
            'quantity' => 1,
            'notes' => null,
        ]);

        $secondJuneConsignment = ConsignmentNote::create([
            'responsible_user_id' => $user->id,
            'supplier_id' => $supplier->id,
            'sent_date' => Carbon::create(2026, 6, 12)->toDateString(),
            'quantity' => 1,
            'notes' => null,
        ]);

        Product::create([
            'consignment_note_id' => $consignment->id,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
            'created_by_id' => $user->id,
            'name' => 'Áo còn tồn',
            'sale_price' => 180000,
            'quantity' => 3,
            'image_path' => null,
            'description' => null,
        ]);

        Product::create([
            'consignment_note_id' => $mayConsignment->id,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
            'created_by_id' => $user->id,
            'name' => 'Váy đã hết',
            'sale_price' => 120000,
            'quantity' => 0,
            'image_path' => null,
            'description' => null,
        ]);

        Product::create([
            'consignment_note_id' => $secondJuneConsignment->id,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
            'created_by_id' => $user->id,
            'name' => 'Quần còn tồn',
            'sale_price' => 250000,
            'quantity' => 2,
            'image_path' => null,
            'description' => null,
        ]);

        $response = $this->get(route('home', [
            'phone' => '0901234567',
        ]));

        $response->assertOk();
        $response->assertViewIs('welcome');
        $response->assertSee('Nhà cung cấp Portal');
        $response->assertSee('0901 234 567');
        $response->assertSee('SĐT: 0901 234 567');
        $response->assertSee('href="tel:0901234567"', false);
        $response->assertSee('Gọi ngay');
        $response->assertSee('Lần ký gửi 2');
        $response->assertSee('Lần ký gửi 1');
        $response->assertSee('05/05/2026');
        $response->assertSee('05/06/2026 - 12/06/2026');
        $response->assertSee('Áo còn tồn');
        $response->assertSee('Quần còn tồn');
        $response->assertSee('2 mặt hàng');
        $response->assertSee('Tồn kho: 5');
        $response->assertSee('Lần ký gửi này hiện không còn sản phẩm tồn kho.');
        $response->assertDontSee('Váy đã hết');
        $response->assertDontSee('180.000');
        $response->assertDontSee('120.000');
        $response->assertDontSee('250.000');
        $response->assertDontSee('Trạng thái thanh toán');
        $response->assertDontSee('Số tiền');
    }

    public function test_supplier_portal_shows_helpful_message_when_phone_is_missing(): void
    {
        $response = $this->get('/?phone=0999999999');

        $response->assertOk();
        $response->assertViewIs('welcome');
        $response->assertSee('Không tìm thấy nhà cung cấp phù hợp');
        $response->assertSee('số điện thoại đã đăng ký');
    }
}
