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
use Illuminate\Support\Carbon;
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

    public function test_supplier_payment_index_shows_monthly_overview_for_paid_and_unpaid_amounts(): void
    {
        $admin = $this->actingAsAdmin();
        $category = $this->createCategory();
        $month = Carbon::create(2026, 5, 1);

        $paid = $this->createSupplierLedgerFixture($admin, $category, 'Alpha Supplier', '111111111', 120000, $month, true);
        $unpaid = $this->createSupplierLedgerFixture($admin, $category, 'Beta Supplier', '222222222', 240000, $month, false);

        $response = $this->get(route('supplier-payments.index', [
            'month' => $month->format('Y-m'),
        ]));

        $response->assertOk();
        $response->assertSee($month->format('Y-m'));
        $response->assertSee('120.000 đ');
        $response->assertSee('240.000 đ');
        $response->assertSee('360.000 đ');
        $response->assertSee($paid['supplier']->name);
        $response->assertSee($unpaid['supplier']->name);
        $response->assertSee('Đã thanh toán');
        $response->assertSee('Chưa thanh toán');
    }

    public function test_supplier_payment_index_filters_to_the_selected_supplier_for_the_month(): void
    {
        $admin = $this->actingAsAdmin();
        $category = $this->createCategory();
        $month = Carbon::create(2026, 5, 1);

        $first = $this->createSupplierLedgerFixture($admin, $category, 'Alpha Supplier', '111111111', 120000, $month, true);
        $second = $this->createSupplierLedgerFixture($admin, $category, 'Beta Supplier', '222222222', 240000, $month, false);

        $response = $this->get(route('supplier-payments.index', [
            'month' => $month->format('Y-m'),
            'supplier_id' => $second['supplier']->id,
        ]));

        $response->assertOk();
        $response->assertSee($second['supplier']->bank_account_number);
        $response->assertSee($second['supplier']->name);
        $response->assertDontSee($first['supplier']->bank_account_number);
        $response->assertDontSee($first['payment']?->public_id_display ?? 'PAY-ALPHA-SUPPLIER');
    }

    public function test_supplier_payment_index_filters_by_unpaid_status(): void
    {
        $admin = $this->actingAsAdmin();
        $category = $this->createCategory();
        $month = Carbon::create(2026, 5, 1);

        $paid = $this->createSupplierLedgerFixture($admin, $category, 'Alpha Supplier', '111111111', 120000, $month, true);
        $unpaid = $this->createSupplierLedgerFixture($admin, $category, 'Beta Supplier', '222222222', 240000, $month, false);

        $response = $this->get(route('supplier-payments.index', [
            'month' => $month->format('Y-m'),
            'status' => 'unpaid',
        ]));

        $response->assertOk();
        $response->assertSee($unpaid['supplier']->name);
        $response->assertSee('Chưa thanh toán');
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

    private function createSupplierLedgerFixture(
        User $user,
        Category $category,
        string $name,
        string $bankAccountNumber,
        int $lineTotal,
        Carbon $month,
        bool $markAsPaid
    ): array {
        $supplier = Supplier::create([
            'responsible_name' => 'Nguoi phu trach '.$name,
            'type' => 'cho_tang',
            'name' => $name,
            'phone' => '0900000000',
            'bank_name' => 'VCB',
            'bank_account_name' => $name,
            'bank_account_number' => $bankAccountNumber,
            'notes' => null,
        ]);

        $consignment = ConsignmentNote::create([
            'responsible_user_id' => $user->id,
            'supplier_id' => $supplier->id,
            'sent_date' => $month->copy()->day(5)->toDateString(),
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
            'completed_at' => $month->copy()->day(12),
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

        $payment = null;

        if ($markAsPaid) {
            $payment = SupplierPayment::create([
                'public_id' => 'PAY-'.Str::upper(Str::slug($name)),
                'supplier_id' => $supplier->id,
                'user_id' => $user->id,
                'payment_reference' => 'payment-'.$supplier->id.'-'.$month->format('Ym'),
                'period_from' => $month->copy()->startOfMonth()->toDateString(),
                'period_to' => $month->copy()->endOfMonth()->toDateString(),
                'gross_amount' => $lineTotal,
                'discount_rate' => 0,
                'discount_amount' => 0,
                'payable_amount' => $lineTotal,
                'bank_name' => 'VCB',
                'bank_account_name' => $supplier->bank_account_name,
                'bank_account_number' => $supplier->bank_account_number,
                'qr_url' => 'https://example.test/qr/'.$supplier->id,
                'payload' => 'test payload for '.$name,
                'paid_at' => $month->copy()->day(20),
            ]);
            $payment->refresh();
        }

        return [
            'supplier' => $supplier,
            'product' => $product,
            'sale' => $sale,
            'payment' => $payment,
        ];
    }
}
