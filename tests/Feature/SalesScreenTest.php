<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ConsignmentNote;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_users_see_the_sales_screen_on_the_home_page(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('sales');
    }

    public function test_sales_lookup_returns_product_payload_for_scanned_code(): void
    {
        $user = User::create([
            'name' => 'Nhân viên bán hàng',
            'email' => 'sales@example.com',
            'password' => 'password',
        ]);
        $category = Category::create([
            'name' => 'Quần áo',
            'description' => null,
            'is_active' => true,
        ]);
        $supplier = Supplier::create([
            'responsible_user_id' => $user->id,
            'type' => 'cho_tang',
            'name' => 'Nhà cung cấp A',
            'phone' => null,
            'bank_name' => null,
            'bank_account_name' => null,
            'bank_account_number' => null,
            'notes' => null,
        ]);
        $consignment = ConsignmentNote::create([
            'responsible_user_id' => $user->id,
            'supplier_id' => $supplier->id,
            'sent_date' => now()->toDateString(),
            'quantity' => 1,
            'notes' => null,
        ]);
        $product = Product::create([
            'consignment_note_id' => $consignment->id,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
            'created_by_id' => $user->id,
            'name' => 'Áo thun QR',
            'sale_price' => 125000,
            'quantity' => 3,
            'image_path' => null,
            'description' => 'Sản phẩm test',
        ]);

        $response = $this->getJson('/ban-hang/products/'.$product->public_id);

        $response->assertOk();
        $response->assertJsonPath('id', $product->id);
        $response->assertJsonPath('public_id', $product->public_id);
        $response->assertJsonPath('name', 'Áo thun QR');
        $response->assertJsonPath('sale_price', 125000);
    }

    public function test_sales_lookup_accepts_the_printed_label_code_format(): void
    {
        $user = User::create([
            'name' => 'Nhân viên bán hàng',
            'email' => 'sales-label@example.com',
            'password' => 'password',
        ]);
        $category = Category::create([
            'name' => 'Phụ kiện',
            'description' => null,
            'is_active' => true,
        ]);
        $supplier = Supplier::create([
            'responsible_user_id' => $user->id,
            'type' => 'cho_tang',
            'name' => 'Nhà cung cấp B',
            'phone' => null,
            'bank_name' => null,
            'bank_account_name' => null,
            'bank_account_number' => null,
            'notes' => null,
        ]);
        $consignment = ConsignmentNote::create([
            'responsible_user_id' => $user->id,
            'supplier_id' => $supplier->id,
            'sent_date' => now()->toDateString(),
            'quantity' => 1,
            'notes' => null,
        ]);
        $product = Product::create([
            'consignment_note_id' => $consignment->id,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
            'created_by_id' => $user->id,
            'name' => 'Túi đeo chéo',
            'sale_price' => 99000,
            'quantity' => 2,
            'image_path' => null,
            'description' => null,
        ]);

        $labelCode = $product->id.'-'.$product->supplier_id.'-1';

        $response = $this->getJson('/ban-hang/products/'.$labelCode);

        $response->assertOk();
        $response->assertJsonPath('id', $product->id);
        $response->assertJsonPath('public_id', $product->public_id);
        $response->assertJsonPath('name', 'Túi đeo chéo');
    }
}