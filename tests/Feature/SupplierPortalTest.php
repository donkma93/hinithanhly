<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ConsignmentNote;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SupplierPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_portal_searches_by_phone_and_shows_all_payment_summary_rows(): void
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

        $juneProduct = Product::create([
            'consignment_note_id' => $consignment->id,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
            'created_by_id' => $user->id,
            'name' => 'Áo đã bán',
            'sale_price' => 180000,
            'quantity' => 1,
            'image_path' => null,
            'description' => null,
        ]);

        $mayProduct = Product::create([
            'consignment_note_id' => $mayConsignment->id,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
            'created_by_id' => $user->id,
            'name' => 'Váy đã bán',
            'sale_price' => 120000,
            'quantity' => 1,
            'image_path' => null,
            'description' => null,
        ]);

        $juneSale = Sale::create([
            'user_id' => $user->id,
            'payment_method' => 'cash',
            'payment_reference' => null,
            'total_amount' => 180000,
            'items_count' => 1,
            'completed_at' => Carbon::create(2026, 6, 12),
        ]);

        $maySale = Sale::create([
            'user_id' => $user->id,
            'payment_method' => 'cash',
            'payment_reference' => null,
            'total_amount' => 120000,
            'items_count' => 1,
            'completed_at' => Carbon::create(2026, 5, 12),
        ]);

        SaleItem::create([
            'sale_id' => $juneSale->id,
            'product_id' => $juneProduct->id,
            'product_public_id' => $juneProduct->public_id,
            'product_name' => $juneProduct->name,
            'quantity' => 1,
            'unit_price' => 180000,
            'line_total' => 180000,
        ]);

        SaleItem::create([
            'sale_id' => $maySale->id,
            'product_id' => $mayProduct->id,
            'product_public_id' => $mayProduct->public_id,
            'product_name' => $mayProduct->name,
            'quantity' => 1,
            'unit_price' => 120000,
            'line_total' => 120000,
        ]);

        SupplierPayment::create([
            'supplier_id' => $supplier->id,
            'user_id' => $user->id,
            'payment_reference' => 'payment-'.$supplier->id.'-202606',
            'period_from' => Carbon::create(2026, 6, 1)->startOfMonth()->toDateString(),
            'period_to' => Carbon::create(2026, 6, 1)->endOfMonth()->toDateString(),
            'gross_amount' => 180000,
            'discount_rate' => 0,
            'discount_amount' => 0,
            'payable_amount' => 180000,
            'bank_name' => 'VCB',
            'bank_account_name' => $supplier->bank_account_name,
            'bank_account_number' => $supplier->bank_account_number,
            'qr_url' => 'https://example.test/qr/'.$supplier->id,
            'payload' => 'test payload',
            'paid_at' => Carbon::create(2026, 6, 20),
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
        $response->assertSee('Trạng thái thanh toán');
        $response->assertSee('Số tiền');
        $response->assertSee('Đã bán');
        $response->assertSee('Kỳ doanh số');
        $response->assertSee('Đã thanh toán');
        $response->assertSee('Chưa thanh toán');
        $response->assertSee('180.000 đ');
        $response->assertSee('120.000 đ');
        $response->assertSee('06/2026');
        $response->assertSee('05/2026');
        $response->assertSee('>1<', false);
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
