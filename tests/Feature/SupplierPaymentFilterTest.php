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
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SupplierPaymentFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_supplier_payment_index_defaults_to_first_supplier_when_no_supplier_id_is_provided(): void
    {
        $admin = $this->actingAsAdmin();
        $category = $this->createCategory();

        $first = $this->createSupplierLedgerFixture($admin, $category, 'Alpha Supplier', '111111111', 120000);
        $second = $this->createSupplierLedgerFixture($admin, $category, 'Beta Supplier', '222222222', 240000);

        $response = $this->get(route('supplier-payments.index', [
            'from' => now()->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee($first['supplier']->bank_account_number);
        $response->assertSee($first['payment']->public_id_display);
        $response->assertDontSee($second['supplier']->bank_account_number);
        $response->assertDontSee($second['payment']->public_id_display);
    }

    public function test_supplier_payment_index_switches_context_when_supplier_id_is_provided(): void
    {
        $admin = $this->actingAsAdmin();
        $category = $this->createCategory();

        $first = $this->createSupplierLedgerFixture($admin, $category, 'Alpha Supplier', '111111111', 120000);
        $second = $this->createSupplierLedgerFixture($admin, $category, 'Beta Supplier', '222222222', 240000);

        $response = $this->get(route('supplier-payments.index', [
            'supplier_id' => $second['supplier']->id,
            'from' => now()->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee($second['supplier']->bank_account_number);
        $response->assertSee($second['payment']->public_id_display);
        $response->assertDontSee($first['supplier']->bank_account_number);
        $response->assertDontSee($first['payment']->public_id_display);
    }

    public function test_search_lists_matching_suppliers_even_when_they_have_no_payable_amount(): void
    {
        $admin = $this->actingAsAdmin();
        $category = $this->createCategory();

        $searchable = $this->createSupplierWithoutLedger('Gamma Supplier');
        $this->createSupplierLedgerFixture($admin, $category, 'Alpha Supplier', '111111111', 120000);

        $response = $this->get(route('supplier-payments.index', [
            'search' => 'Gamma Supplier',
            'from' => now()->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee($searchable->name);
        $response->assertSee('Không cần thanh toán');
    }

    private function actingAsAdmin(): User
    {
        $user = User::query()->where('email', 'admin@kygui.local')->firstOrFail();
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

    private function createSupplierLedgerFixture(User $user, Category $category, string $name, string $bankAccountNumber, int $lineTotal): array
    {
        $supplier = Supplier::create([
            'responsible_name' => 'Nguoi phu trach '.$name,
            'type' => 'cho_tang',
            'name' => $name,
            'phone' => null,
            'bank_name' => 'VCB',
            'bank_account_name' => $name,
            'bank_account_number' => $bankAccountNumber,
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
            'name' => $name.' Product',
            'sale_price' => $lineTotal,
            'quantity' => 1,
            'image_path' => null,
            'description' => null,
        ]);
        $product->refresh();

        $sale = Sale::create([
            'user_id' => $user->id,
            'payment_method' => 'cash',
            'payment_reference' => null,
            'total_amount' => $lineTotal,
            'items_count' => 1,
            'completed_at' => now(),
        ]);
        $sale->refresh();

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_public_id' => $product->public_id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => $lineTotal,
            'line_total' => $lineTotal,
        ]);

        $payment = SupplierPayment::create([
            'public_id' => 'PAY-'.Str::upper(Str::slug($name)),
            'supplier_id' => $supplier->id,
            'user_id' => $user->id,
            'payment_reference' => 'payment-'.$supplier->id,
            'period_from' => now()->toDateString(),
            'period_to' => now()->toDateString(),
            'gross_amount' => $lineTotal,
            'discount_rate' => 0,
            'discount_amount' => 0,
            'payable_amount' => $lineTotal,
            'bank_name' => 'VCB',
            'bank_account_name' => $supplier->bank_account_name,
            'bank_account_number' => $supplier->bank_account_number,
            'qr_url' => 'https://example.test/qr/'.$supplier->id,
            'payload' => 'test payload for '.$name,
            'paid_at' => now(),
        ]);
        $payment->refresh();

        return [
            'supplier' => $supplier,
            'product' => $product,
            'sale' => $sale,
            'payment' => $payment,
        ];
    }

    private function createSupplierWithoutLedger(string $name): Supplier
    {
        return Supplier::create([
            'responsible_name' => 'Nguoi phu trach '.$name,
            'type' => 'cho_tang',
            'name' => $name,
            'phone' => null,
            'bank_name' => 'VCB',
            'bank_account_name' => $name,
            'bank_account_number' => '000000000',
            'notes' => null,
        ]);
    }
}
