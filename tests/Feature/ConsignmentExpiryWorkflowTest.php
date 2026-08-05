<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ConsignmentNote;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConsignmentExpiryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_products_index_links_to_expiry_management_for_items_close_to_deadline(): void
    {
        $this->signInAsAdmin();

        $category = $this->createCategory();
        $supplier = $this->createSupplier('ncc_it_san_pham', 'NCC can tra');
        $consignment = $this->createConsignmentNote($supplier, now()->subDays(41));
        $product = $this->createProduct($category, $supplier, $consignment, 'Ao can tra');

        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('Mở màn quản lý hạn ký gửi');
        $response->assertSee($product->name);
        $response->assertDontSee('Trả hàng');
        $response->assertSee('Còn 4 ngày');
    }

    public function test_returning_a_product_marks_it_unsellable_on_the_sales_screen(): void
    {
        $admin = $this->signInAsAdmin();

        $category = $this->createCategory();
        $supplier = $this->createSupplier('ncc_it_san_pham', 'NCC tra hang');
        $consignment = $this->createConsignmentNote($supplier, now()->subDays(10));
        $product = $this->createProduct($category, $supplier, $consignment, 'Ao tra hang');

        $this->post(route('products.return', $product))
            ->assertRedirect(route('products.index'));

        $product->refresh();

        $this->assertNotNull($product->returned_at);
        $this->assertSame(0, $product->quantity);
        $this->assertSame($admin->id, $product->returned_by_id);

        $this->getJson(route('sales.lookup', $product->public_id))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Sản phẩm này đã được trả cho người gửi nên không thể bán.');

        $this->getJson(route('sales.search', ['query' => $product->public_id]))
            ->assertOk()
            ->assertJsonCount(0, 'items');
    }

    public function test_expired_products_cannot_be_sold_even_before_manual_return(): void
    {
        $this->signInAsAdmin();

        $category = $this->createCategory();
        $supplier = $this->createSupplier('ncc_it_san_pham', 'NCC qua han');
        $consignment = $this->createConsignmentNote($supplier, now()->subDays(46));
        $product = $this->createProduct($category, $supplier, $consignment, 'Ao qua han');

        $this->getJson(route('sales.lookup', $product->public_id))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Sản phẩm này đã quá hạn ký gửi nên không thể bán.');

        $this->actingAs(User::factory()->create())
            ->postJson(route('sales.checkout'), [
                'items' => [
                    ['id' => $product->id, 'quantity' => 1],
                ],
                'payment_method' => 'cash',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Sản phẩm Ao qua han đã hết hạn ký gửi hoặc đã trả cho người gửi. Không thể bán trên hệ thống.');
    }

    public function test_expiry_management_page_lists_and_filters_problem_products(): void
    {
        $this->signInAsAdmin();

        $category = $this->createCategory();
        $supplier = $this->createSupplier('ncc_it_san_pham', 'NCC quan ly');

        $soonConsignment = $this->createConsignmentNote($supplier, now()->subDays(41));
        $expiredConsignment = $this->createConsignmentNote($supplier, now()->subDays(46));
        $returnedConsignment = $this->createConsignmentNote($supplier, now()->subDays(46));

        $soonProduct = $this->createProduct($category, $supplier, $soonConsignment, 'Ao sap het han');
        $expiredProduct = $this->createProduct($category, $supplier, $expiredConsignment, 'Ao qua han');
        $returnedProduct = $this->createProduct($category, $supplier, $returnedConsignment, 'Ao da tra');

        $this->post(route('products.return', $returnedProduct))
            ->assertRedirect(route('products.index'));

        $returnedProduct->refresh();

        $response = $this->get(route('products.expiry'));

        $response->assertOk();
        $response->assertSee('Quản lý hạn ký gửi');
        $response->assertSee('Cần xử lý');
        $response->assertSee('Sắp hết hạn');
        $response->assertSee('Quá hạn');
        $response->assertSee('Đã trả');
        $response->assertSee($soonProduct->name);
        $response->assertSee($expiredProduct->name);
        $response->assertSee('Trả hàng');

        $this->get(route('products.expiry', ['status' => 'expired']))
            ->assertOk()
            ->assertSee($expiredProduct->name)
            ->assertDontSee($soonProduct->name);

        $this->get(route('products.expiry', ['status' => 'returned']))
            ->assertOk()
            ->assertSee($returnedProduct->name)
            ->assertDontSee($soonProduct->name)
            ->assertDontSee('Trả hàng');
    }

    private function signInAsAdmin(): User
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);
        $user->assignRole('admin');

        $this->actingAs($user);

        return $user;
    }

    private function createCategory(): Category
    {
        return Category::create([
            'name' => 'Danh muc test',
            'description' => null,
            'is_active' => true,
        ]);
    }

    private function createSupplier(string $type, string $name): Supplier
    {
        return Supplier::create([
            'responsible_name' => 'Nguoi phu trach',
            'type' => $type,
            'name' => $name,
            'phone' => null,
            'bank_name' => null,
            'bank_account_name' => null,
            'bank_account_number' => null,
            'notes' => null,
        ]);
    }

    private function createConsignmentNote(Supplier $supplier, ?\Illuminate\Support\Carbon $sentDate = null): ConsignmentNote
    {
        return ConsignmentNote::create([
            'responsible_user_id' => User::factory()->create()->id,
            'responsible_name' => 'Nguoi ki gui',
            'supplier_id' => $supplier->id,
            'sent_date' => ($sentDate ?? now())->toDateString(),
            'quantity' => 1,
            'notes' => 'Consignment test',
        ]);
    }

    private function createProduct(
        Category $category,
        Supplier $supplier,
        ConsignmentNote $consignment,
        string $name
    ): Product {
        return Product::create([
            'consignment_note_id' => $consignment->id,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
            'created_by_id' => User::factory()->create()->id,
            'name' => $name,
            'sale_price' => 100000,
            'quantity' => 2,
            'image_path' => null,
            'description' => null,
        ]);
    }
}
