<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ConsignmentNote;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TrashWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_delete_actions_are_removed_from_index_pages_and_moved_into_edit_forms(): void
    {
        $this->signInAsAdmin();

        $category = $this->createCategory('Danh muc trash test');
        $supplier = $this->createSupplier('cho_tang', 'NCC trash test');
        $consignmentSupplier = $this->createSupplier('ncc_it_san_pham', 'NCC ky gui trash test');
        $consignment = $this->createConsignmentNote($consignmentSupplier);
        $product = $this->createProduct($category, $consignmentSupplier, $consignment);
        $user = User::factory()->create();
        $permission = Permission::create([
            'name' => 'trash.workflow.test',
            'guard_name' => 'web',
        ]);

        $cases = [
            [
                'index' => route('categories.index'),
                'edit' => route('categories.edit', $category),
                'destroy' => route('categories.destroy', $category),
            ],
            [
                'index' => route('suppliers.index'),
                'edit' => route('suppliers.edit', $supplier),
                'destroy' => route('suppliers.destroy', $supplier),
            ],
            [
                'index' => route('consignments.index'),
                'edit' => route('consignments.edit', $consignment),
                'destroy' => route('consignments.destroy', $consignment),
            ],
            [
                'index' => route('products.index'),
                'edit' => route('products.edit', $product),
                'destroy' => route('products.destroy', $product),
            ],
            [
                'index' => route('users.index'),
                'edit' => route('users.edit', $user),
                'destroy' => route('users.destroy', $user),
            ],
            [
                'index' => route('permissions.index'),
                'edit' => route('permissions.edit', $permission),
                'destroy' => route('permissions.destroy', $permission),
            ],
        ];

        foreach ($cases as $case) {
            $this->get($case['index'])
                ->assertOk()
                ->assertDontSee('action="'.$case['destroy'].'"', false);

            $this->get($case['edit'])
                ->assertOk()
                ->assertSee('action="'.$case['destroy'].'"', false);
        }
    }

    public function test_core_delete_routes_move_records_into_the_trash(): void
    {
        $admin = $this->signInAsAdmin();

        $category = $this->createCategory('Danh muc trash delete');
        $supplier = $this->createSupplier('cho_tang', 'NCC trash delete');
        $permission = Permission::create([
            'name' => 'trash.workflow.delete.permission',
            'guard_name' => 'web',
        ]);
        $user = User::factory()->create();

        $this->delete(route('categories.destroy', $category))->assertRedirect(route('categories.index'));
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
        $this->get(route('categories.index'))->assertDontSee($category->name);

        $this->delete(route('suppliers.destroy', $supplier))->assertRedirect(route('suppliers.index'));
        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
        $this->get(route('suppliers.index'))->assertDontSee($supplier->name);

        $this->delete(route('permissions.destroy', $permission))
            ->assertRedirect(route('permissions.index'));
        $permissionTable = config('permission.table_names.permissions');
        $this->assertSoftDeleted($permissionTable, ['id' => $permission->id]);
        $this->get(route('permissions.index'))->assertDontSee($permission->name);

        $this->delete(route('users.destroy', $user))->assertRedirect(route('users.index'));
        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->get(route('users.index'))->assertDontSee($user->email);

        $this->assertAuthenticatedAs($admin);
    }

    public function test_deleting_a_consignment_note_keeps_related_products_available(): void
    {
        $this->signInAsAdmin();
        $category = $this->createCategory('Danh muc consignment trash');
        $supplier = $this->createSupplier('ncc_it_san_pham', 'NCC consignment trash');
        $consignment = $this->createConsignmentNote($supplier);
        $product = $this->createProduct($category, $supplier, $consignment, 'products/consignment-trash.jpg');

        $this->delete(route('consignments.destroy', $consignment))
            ->assertRedirect(route('consignments.index'));

        $this->assertSoftDeleted('consignment_notes', ['id' => $consignment->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->get(route('consignments.index'))->assertDontSee($consignment->responsible_name);
        $this->get(route('products.index'))->assertSee($product->name);
    }

    public function test_deleting_a_product_moves_it_to_the_trash_without_removing_its_image(): void
    {
        Storage::fake('public');

        $this->signInAsAdmin();
        $category = $this->createCategory('Danh muc product trash');
        $supplier = $this->createSupplier('ncc_it_san_pham', 'NCC product trash');
        $consignment = $this->createConsignmentNote($supplier);

        Storage::disk('public')->put('products/product-trash.jpg', 'image-data');

        $product = $this->createProduct($category, $supplier, $consignment, 'products/product-trash.jpg');

        $this->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'));

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        Storage::disk('public')->assertExists('products/product-trash.jpg');
        $this->get(route('products.index'))->assertDontSee($product->name);
    }

    public function test_trashed_products_can_be_restored_from_the_trash_page(): void
    {
        $this->signInAsAdmin();

        $category = $this->createCategory('Danh muc restore trash');
        $supplier = $this->createSupplier('ncc_it_san_pham', 'NCC restore trash');
        $consignment = $this->createConsignmentNote($supplier);
        $product = $this->createProduct($category, $supplier, $consignment);

        $this->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'));

        $this->assertSoftDeleted('products', ['id' => $product->id]);

        $this->get(route('trash.index'))
            ->assertOk()
            ->assertSee($product->name);

        $this->post(route('trash.restore', ['type' => 'products', 'id' => $product->id]))
            ->assertRedirect(route('trash.index'));

        $this->assertNotSoftDeleted('products', ['id' => $product->id]);
        $this->get(route('products.index'))->assertSee($product->name);
    }

    public function test_force_deleting_a_trashed_product_removes_it_and_its_image(): void
    {
        Storage::fake('public');

        $this->signInAsAdmin();
        $category = $this->createCategory('Danh muc force trash');
        $supplier = $this->createSupplier('ncc_it_san_pham', 'NCC force trash');
        $consignment = $this->createConsignmentNote($supplier);

        Storage::disk('public')->put('products/force-trash.jpg', 'image-data');

        $product = $this->createProduct($category, $supplier, $consignment, 'products/force-trash.jpg');

        $this->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'));

        $this->delete(route('trash.destroy', ['type' => 'products', 'id' => $product->id]))
            ->assertRedirect(route('trash.index'));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        Storage::disk('public')->assertMissing('products/force-trash.jpg');
    }

    public function test_force_deleting_a_category_is_blocked_when_products_still_reference_it(): void
    {
        $this->signInAsAdmin();

        $category = $this->createCategory('Danh muc blocked trash');
        $supplier = $this->createSupplier('ncc_it_san_pham', 'NCC blocked trash');
        $consignment = $this->createConsignmentNote($supplier);
        $product = $this->createProduct($category, $supplier, $consignment);

        $this->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'));

        $this->delete(route('trash.destroy', ['type' => 'categories', 'id' => $category->id]))
            ->assertRedirect(route('trash.index'));

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
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

    private function createCategory(string $name): Category
    {
        return Category::create([
            'name' => $name,
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

    private function createConsignmentNote(Supplier $supplier): ConsignmentNote
    {
        return ConsignmentNote::create([
            'responsible_user_id' => User::factory()->create()->id,
            'responsible_name' => 'Nguoi ki gui',
            'supplier_id' => $supplier->id,
            'sent_date' => now()->toDateString(),
            'quantity' => 1,
            'notes' => 'Consignment test',
        ]);
    }

    private function createProduct(
        Category $category,
        Supplier $supplier,
        ConsignmentNote $consignment,
        ?string $imagePath = null
    ): Product {
        return Product::create([
            'consignment_note_id' => $consignment->id,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
            'created_by_id' => User::factory()->create()->id,
            'name' => 'San pham trash test '.uniqid(),
            'sale_price' => 100000,
            'quantity' => 1,
            'image_path' => $imagePath,
            'description' => null,
        ]);
    }
}
