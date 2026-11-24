<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ConsignmentNote;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SupplierPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_supplier_portal_searches_by_phone_and_shows_revenue_expiry_and_storage_days(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 20, 10, 0, 0, 'Asia/Ho_Chi_Minh'));

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
            'sent_date' => Carbon::create(2026, 5, 1)->toDateString(),
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

        $shirt = Product::create([
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
            'name' => 'Váy đã hết chưa bán',
            'sale_price' => 120000,
            'quantity' => 0,
            'image_path' => null,
            'description' => null,
        ]);

        $pants = Product::create([
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

        $sale = Sale::create([
            'user_id' => $user->id,
            'payment_method' => 'cash',
            'total_amount' => 610000,
            'items_count' => 2,
            'completed_at' => Carbon::now(),
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $shirt->id,
            'product_public_id' => (string) $shirt->public_id,
            'product_name' => $shirt->name,
            'quantity' => 2,
            'unit_price' => 180000,
            'line_total' => 360000,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $pants->id,
            'product_public_id' => (string) $pants->public_id,
            'product_name' => $pants->name,
            'quantity' => 1,
            'unit_price' => 250000,
            'line_total' => 250000,
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
        $response->assertSee('01/05/2026');
        $response->assertSee('05/06/2026 - 12/06/2026');
        // Aggregate metrics only: sold quantity, stock quantity, revenue, storage, expiry
        $response->assertSee('SL đã bán');
        $response->assertSee('SL tồn');
        $response->assertSee('610.000 ₫');
        $response->assertSee('15 ngày');
        $response->assertSee('50 ngày');
        $response->assertSee('20/07/2026');
        $response->assertSee('Còn 30 ngày');
        $response->assertSee('15/06/2026');
        $response->assertSee('Quá hạn 5 ngày');
        // Must NOT expose specific product names or per-product sales breakdown
        $response->assertDontSee('Áo còn tồn');
        $response->assertDontSee('Quần còn tồn');
        $response->assertDontSee('Váy đã hết chưa bán');
        $response->assertDontSee('360.000 ₫');
        $response->assertDontSee('250.000 ₫');
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
