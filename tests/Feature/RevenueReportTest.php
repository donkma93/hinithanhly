<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ConsignmentNote;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RevenueReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_revenue_report_groups_results_by_supplier_type_and_lists_supplier_details(): void
    {
        $admin = $this->signInAsAdmin();
        $category = $this->createCategory();
        $month = Carbon::create(2026, 6, 1);

        $this->createSaleFixture($admin, $category, 'cho_tang', 'NCC cho tang', 120000, $month->copy()->day(10));
        $this->createSaleFixture($admin, $category, 'ncc_it_san_pham', 'NCC it san pham', 260000, $month->copy()->day(11));

        $response = $this->get(route('revenue.index', [
            'from' => $month->copy()->startOfMonth()->format('Y-m-d'),
            'to' => $month->copy()->endOfMonth()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertSee('Thống kê theo phân loại NCC');
        $response->assertSee('Cho tặng');
        $response->assertSee('NCC cho tang');
        $response->assertSee('NCC ít sản phẩm');
        $response->assertSee('NCC it san pham');
        $response->assertSee('120.000 đ');
        $response->assertSee('260.000 đ');
    }

    public function test_revenue_report_uses_sale_creation_time_when_completed_time_is_missing(): void
    {
        $admin = $this->signInAsAdmin();
        $category = $this->createCategory();
        $actualTime = Carbon::create(2026, 6, 15, 14, 35, 0);

        $sale = $this->createSaleFixture($admin, $category, 'cho_tang', 'NCC fallback time', 190000, $actualTime, false);

        Sale::query()->whereKey($sale->id)->update([
            'completed_at' => null,
            'created_at' => $actualTime,
            'updated_at' => $actualTime,
        ]);

        $response = $this->get(route('revenue.index', [
            'from' => $actualTime->copy()->startOfDay()->format('Y-m-d'),
            'to' => $actualTime->copy()->endOfDay()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertSee('190.000 đ');
        $response->assertSee($actualTime->format('d/m/Y H:i'));
        $response->assertSee('NCC fallback time');
    }

    public function test_revenue_report_shows_only_suppliers_belonging_to_the_selected_type(): void
    {
        $admin = $this->signInAsAdmin();
        $category = $this->createCategory();
        $month = Carbon::create(2026, 6, 1);

        $this->createSaleFixture($admin, $category, 'cho_tang', 'NCC cho tang A', 120000, $month->copy()->day(10));
        $this->createSaleFixture($admin, $category, 'ncc_it_san_pham', 'NCC it san pham B', 260000, $month->copy()->day(11));

        $response = $this->get(route('revenue.index', [
            'supplier_type' => 'cho_tang',
            'from' => $month->copy()->startOfMonth()->format('Y-m-d'),
            'to' => $month->copy()->endOfMonth()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertSee('NCC cho tang A');
        $response->assertDontSee('NCC it san pham B');
    }

    public function test_revenue_report_can_filter_to_a_specific_supplier_within_the_selected_type(): void
    {
        $admin = $this->signInAsAdmin();
        $category = $this->createCategory();
        $month = Carbon::create(2026, 6, 1);

        $this->createSaleFixture($admin, $category, 'cho_tang', 'NCC cho tang A', 120000, $month->copy()->day(10));
        $secondSale = $this->createSaleFixture($admin, $category, 'cho_tang', 'NCC cho tang B', 180000, $month->copy()->day(11));
        $selectedSupplierId = Product::query()->findOrFail($secondSale->items()->firstOrFail()->product_id)->supplier_id;

        $response = $this->get(route('revenue.index', [
            'supplier_type' => 'cho_tang',
            'supplier_id' => $selectedSupplierId,
            'from' => $month->copy()->startOfMonth()->format('Y-m-d'),
            'to' => $month->copy()->endOfMonth()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertSee('NCC cho tang B');
        $response->assertDontSee('NCC cho tang A Product');
        $response->assertSee('180.000 đ');
        $response->assertDontSee('120.000 đ');
    }

    public function test_user_with_revenue_permission_can_open_revenue_report_without_admin_role(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);
        $user->assignRole('staff');
        $user->givePermissionTo(Permission::findByName('sales.revenue.view', 'web'));

        $this->actingAs($user);

        $response = $this->get(route('revenue.index'));

        $response->assertOk();
        $response->assertSee('Doanh thu');
    }

    public function test_revenue_filter_does_not_show_or_apply_ncc_nhieu_san_pham_type(): void
    {
        $admin = $this->signInAsAdmin();
        $category = $this->createCategory();
        $month = Carbon::create(2026, 6, 1);

        $this->createSaleFixture($admin, $category, 'ncc_nhieu_san_pham', 'NCC nhieu san pham', 210000, $month->copy()->day(12));
        $this->createSaleFixture($admin, $category, 'cho_tang', 'NCC cho tang', 120000, $month->copy()->day(13));

        $response = $this->get(route('revenue.index', [
            'supplier_type' => 'ncc_nhieu_san_pham',
            'from' => $month->copy()->startOfMonth()->format('Y-m-d'),
            'to' => $month->copy()->endOfMonth()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertDontSee('value="ncc_nhieu_san_pham"', false);
        $response->assertSee('NCC nhieu san pham');
        $response->assertSee('NCC cho tang');
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
            'name' => 'Danh muc doanh thu',
            'description' => null,
            'is_active' => true,
        ]);
    }

    private function createSaleFixture(
        User $user,
        Category $category,
        string $supplierType,
        string $supplierName,
        int $lineTotal,
        Carbon $completedAt,
        bool $withCompletedAt = true
    ): Sale {
        $supplier = Supplier::create([
            'responsible_name' => 'Nguoi phu trach '.$supplierName,
            'type' => $supplierType,
            'name' => $supplierName,
            'phone' => '0900000000',
            'bank_name' => null,
            'bank_account_name' => null,
            'bank_account_number' => null,
            'notes' => null,
        ]);

        $consignment = ConsignmentNote::create([
            'responsible_user_id' => $user->id,
            'responsible_name' => $user->name,
            'supplier_id' => $supplier->id,
            'sent_date' => $completedAt->copy()->subDays(3)->toDateString(),
            'quantity' => 1,
            'notes' => ConsignmentNote::AUTO_GENERATED_NOTE_MARKER,
        ]);

        $product = Product::create([
            'consignment_note_id' => $consignment->id,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
            'created_by_id' => $user->id,
            'name' => $supplierName.' Product',
            'sale_price' => $lineTotal,
            'quantity' => 1,
            'image_path' => null,
            'description' => null,
        ]);

        $sale = Sale::create([
            'user_id' => $user->id,
            'payment_method' => 'cash',
            'payment_reference' => null,
            'total_amount' => $lineTotal,
            'items_count' => 1,
            'completed_at' => $withCompletedAt ? $completedAt : null,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_public_id' => $product->public_id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => $lineTotal,
            'line_total' => $lineTotal,
        ]);

        return $sale;
    }
}
