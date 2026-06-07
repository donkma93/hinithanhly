<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ConsignmentNote;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_portal_search_returns_supplier_products_and_status_counts(): void
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
            'phone' => '0901234567',
            'bank_name' => null,
            'bank_account_name' => null,
            'bank_account_number' => null,
            'notes' => null,
        ]);

        $activeNote = ConsignmentNote::create([
            'responsible_user_id' => $user->id,
            'supplier_id' => $supplier->id,
            'sent_date' => now()->subDays(10)->toDateString(),
            'quantity' => 1,
            'notes' => null,
        ]);

        $expiringSoonNote = ConsignmentNote::create([
            'responsible_user_id' => $user->id,
            'supplier_id' => $supplier->id,
            'sent_date' => now()->subDays(41)->toDateString(),
            'quantity' => 1,
            'notes' => null,
        ]);

        $returnedNote = ConsignmentNote::create([
            'responsible_user_id' => $user->id,
            'supplier_id' => $supplier->id,
            'sent_date' => now()->subDays(20)->toDateString(),
            'quantity' => 1,
            'notes' => null,
        ]);

        $activeProduct = Product::create([
            'consignment_note_id' => $activeNote->id,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
            'created_by_id' => $user->id,
            'name' => 'Áo còn hiệu lực',
            'sale_price' => 120000,
            'quantity' => 1,
            'image_path' => null,
            'description' => null,
        ]);

        $expiringSoonProduct = Product::create([
            'consignment_note_id' => $expiringSoonNote->id,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
            'created_by_id' => $user->id,
            'name' => 'Váy sắp hết hạn',
            'sale_price' => 150000,
            'quantity' => 1,
            'image_path' => null,
            'description' => null,
        ]);

        $returnedProduct = Product::create([
            'consignment_note_id' => $returnedNote->id,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
            'created_by_id' => $user->id,
            'name' => 'Quần đã trả',
            'sale_price' => 90000,
            'quantity' => 0,
            'image_path' => null,
            'description' => null,
            'returned_at' => now()->subDay(),
            'returned_by_id' => $user->id,
        ]);

        $response = $this->get(route('home', [
            'supplier_code' => '#'.$supplier->public_id_display,
        ]));

        $response->assertOk();
        $response->assertViewIs('welcome');
        $response->assertSee('Nhà cung cấp Portal');
        $response->assertSee('Tổng 3 sản phẩm');
        $response->assertSee('Đang hiệu lực');
        $response->assertSee('Sắp hết hạn');
        $response->assertSee('Quá hạn');
        $response->assertSee('Đã trả');
        $response->assertSee($activeProduct->name);
        $response->assertSee($expiringSoonProduct->name);
        $response->assertSee($returnedProduct->name);
        $response->assertSee('Còn 4 ngày');
        $response->assertSee('Đã trả cho người gửi');
    }

    public function test_supplier_portal_shows_helpful_message_when_supplier_is_missing(): void
    {
        $response = $this->get('/?supplier_code=999999');

        $response->assertOk();
        $response->assertViewIs('welcome');
        $response->assertSee('Không tìm thấy nhà cung cấp phù hợp');
    }
}
