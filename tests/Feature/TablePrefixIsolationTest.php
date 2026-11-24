<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ConsignmentNote;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\TestCase;

class TablePrefixIsolationTest extends TestCase
{
    private string $sqlitePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sqlitePath = tempnam(sys_get_temp_dir(), 'hini_multi_branch_').'.sqlite';
        touch($this->sqlitePath);

        $this->switchPrefix('');
        Artisan::call('migrate', ['--force' => true]);
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        DB::purge('portal_branch_cs1');
        DB::purge('portal_branch_cs2');
        DB::purge('portal_branch_cs3');

        if (isset($this->sqlitePath) && is_file($this->sqlitePath)) {
            @unlink($this->sqlitePath);
        }

        parent::tearDown();
    }

    public function test_second_branch_prefix_migrates_cleanly_without_touching_first_branch_data_and_controllers_work(): void
    {
        // 1. Seed Branch 1 (prefix = '') data
        $this->switchPrefix('');
        $cs1 = $this->seedBranchSampleData(
            email: 'admin-cs1@example.com',
            categoryName: 'Danh mục Trùng Tên',
            supplierName: 'NCC Cơ sở 1',
            supplierPhone: '0901 111 222',
            productName: 'Áo khoác CS1',
            salePrice: 200000,
            stockQty: 4,
            soldQty: 2,
            storeAddress: '100 Đường CS1, Quận 1',
            storeHotline: '0901 000 001'
        );

        // Record original Branch 1 snapshot before Branch 2 migrations run
        $cs1UserPublicIdBefore = $cs1['user']->fresh()->public_id;
        $cs1ProductPublicIdBefore = $cs1['product']->fresh()->public_id;
        $cs1SupplierPublicIdBefore = $cs1['supplier']->fresh()->public_id;

        // 2. Switch to Branch 2 (prefix = 'cs2_') on the EXACT SAME physical SQLite database and run all migrations
        $this->switchPrefix('cs2_');
        Artisan::call('migrate', ['--force' => true]);

        // Verify Branch 2 tables exist alongside Branch 1 tables
        $this->assertTrue(Schema::hasTable('products'));
        $this->assertTrue(Schema::hasTable('sales'));
        $this->assertTrue(Schema::hasTable('migrations'));

        // Seed Branch 2 data (can reuse identical category name, email, phone without collision!)
        $cs2 = $this->seedBranchSampleData(
            email: 'admin-cs1@example.com',
            categoryName: 'Danh mục Trùng Tên',
            supplierName: 'NCC Cơ sở 2',
            supplierPhone: '0901 111 222',
            productName: 'Váy lụa CS2',
            salePrice: 350000,
            stockQty: 7,
            soldQty: 1,
            storeAddress: '200 Đường CS2, Quận 2',
            storeHotline: '0902 000 002'
        );

        // 3. Assert prefix-sensitive controllers work cleanly on Branch 2 and NEVER leak Branch 1 data
        $this->actingAs($cs2['user']);

        // ProductController::expiryIndex (orderByRaw on products & consignment_notes)
        $this->get(route('products.expiry'))
            ->assertOk()
            ->assertDontSee('Áo khoác CS1');

        // RevenueController::index (selectRaw + joins on sales, sale_items, products, suppliers)
        $this->get(route('revenue.index'))
            ->assertOk()
            ->assertSee('NCC Cơ sở 2')
            ->assertDontSee('NCC Cơ sở 1');

        // SupplierPaymentController::index (DB::raw + selectRaw + joins)
        $this->get(route('supplier-payments.index'))
            ->assertOk()
            ->assertSee('NCC Cơ sở 2')
            ->assertDontSee('NCC Cơ sở 1');

        // SalesController::search & lookup must only find Branch 2 stock
        $this->getJson(route('sales.search', ['query' => (string) $cs2['product']->public_id]))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Váy lụa CS2'])
            ->assertJsonMissing(['name' => 'Áo khoác CS1']);

        // 4. Switch back to Branch 1 and verify ZERO data mutation or public_id corruption
        $this->switchPrefix('');
        $this->assertSame($cs1UserPublicIdBefore, User::query()->findOrFail($cs1['user']->id)->public_id);
        $this->assertSame($cs1ProductPublicIdBefore, Product::query()->findOrFail($cs1['product']->id)->public_id);
        $this->assertSame($cs1SupplierPublicIdBefore, Supplier::query()->findOrFail($cs1['supplier']->id)->public_id);
        $this->assertSame(1, Product::query()->count());
        $this->assertSame('Áo khoác CS1', Product::query()->first()->name);
        $this->assertSame('100 Đường CS1, Quận 1', Setting::get('store_address'));
    }

    public function test_homepage_portal_searches_across_both_branches_separately_and_supports_branch_filtering(): void
    {
        // Seed Branch 1
        $this->switchPrefix('');
        $this->seedBranchSampleData(
            email: 'cs1@example.com',
            categoryName: 'Danh mục CS1',
            supplierName: 'Chị Lan (CS1)',
            supplierPhone: '0912 345 678',
            productName: 'Đầm công sở CS1',
            salePrice: 220000,
            stockQty: 3,
            soldQty: 1,
            storeAddress: '12 Nguyễn Huệ, CS1',
            storeHotline: '0900 111 111'
        );

        // Migrate & Seed Branch 2 on the same database
        $this->switchPrefix('cs2_');
        Artisan::call('migrate', ['--force' => true]);
        $this->seedBranchSampleData(
            email: 'cs2@example.com',
            categoryName: 'Danh mục CS2',
            supplierName: 'Chị Lan (CS2)',
            supplierPhone: '0912-345-678',
            productName: 'Túi xách CS2',
            salePrice: 480000,
            stockQty: 5,
            soldQty: 1,
            storeAddress: '88 Lê Lợi, CS2',
            storeHotline: '0900 222 222'
        );

        // Add another supplier ONLY in Branch 2
        $cs2User = User::query()->firstOrFail();
        $cs2Category = Category::query()->firstOrFail();
        $onlyCs2Supplier = Supplier::create([
            'responsible_user_id' => $cs2User->id,
            'type' => 'ncc_it_san_pham',
            'name' => 'Anh Minh Chỉ CS2',
            'phone' => '0988 777 666',
        ]);
        $onlyCs2Note = ConsignmentNote::create([
            'responsible_user_id' => $cs2User->id,
            'supplier_id' => $onlyCs2Supplier->id,
            'sent_date' => Carbon::now()->subDays(5)->toDateString(),
            'quantity' => 1,
        ]);
        Product::create([
            'consignment_note_id' => $onlyCs2Note->id,
            'supplier_id' => $onlyCs2Supplier->id,
            'category_id' => $cs2Category->id,
            'created_by_id' => $cs2User->id,
            'name' => 'Giày thể thao CS2',
            'sale_price' => 310000,
            'quantity' => 2,
        ]);

        // Visit homepage from Branch 1 with PORTAL_BRANCHES configured for CS1, CS2, and an unmigrated CS3
        $this->switchPrefix('');
        config()->set('branches.portal', [
            ['key' => 'cs1', 'name' => 'Cơ sở 1', 'prefix' => ''],
            ['key' => 'cs2', 'name' => 'Cơ sở 2', 'prefix' => 'cs2_'],
            ['key' => 'cs3', 'name' => 'Cơ sở 3', 'prefix' => 'cs3_'],
        ]);

        // 1. Searching shared phone shows BOTH Branch 1 and Branch 2 aggregate stats (revenue, sold, stock, storage & expiry)
        //    without revealing individual product names, and gracefully ignores unmigrated Branch 3.
        $response = $this->get(route('home', ['phone' => '0912345678']));
        $response->assertOk();
        $response->assertSee('Tất cả cơ sở');
        $response->assertSee('Cơ sở 1');
        $response->assertSee('Cơ sở 2');
        $response->assertSee('Chị Lan (CS1)');
        $response->assertSee('Chị Lan (CS2)');
        $response->assertDontSee('Đầm công sở CS1');
        $response->assertDontSee('Túi xách CS2');
        $response->assertSee('12 Nguyễn Huệ, CS1');
        $response->assertSee('88 Lê Lợi, CS2');
        $response->assertSee('220.000 ₫');
        $response->assertSee('480.000 ₫');
        $response->assertSee('10 ngày');
        $response->assertSee('Còn 35 ngày');

        // 2. Filtering to CS1 only shows CS1 result
        $this->get(route('home', ['phone' => '0912345678', 'branch' => 'cs1']))
            ->assertOk()
            ->assertSee('Chị Lan (CS1)')
            ->assertSee('220.000 ₫')
            ->assertDontSee('Chị Lan (CS2)')
            ->assertDontSee('480.000 ₫')
            ->assertDontSee('Đầm công sở CS1');

        // 3. Filtering to CS2 only shows CS2 result
        $this->get(route('home', ['phone' => '0912345678', 'branch' => 'cs2']))
            ->assertOk()
            ->assertSee('Chị Lan (CS2)')
            ->assertSee('480.000 ₫')
            ->assertDontSee('Chị Lan (CS1)')
            ->assertDontSee('220.000 ₫')
            ->assertDontSee('Túi xách CS2');

        // 4. Phone existing ONLY in CS2 is still found when searching from CS1 homepage
        $this->get(route('home', ['phone' => '0988777666']))
            ->assertOk()
            ->assertSee('Anh Minh Chỉ CS2')
            ->assertDontSee('Giày thể thao CS2')
            ->assertSee('Không tìm thấy dữ liệu ký gửi cho số điện thoại này tại:');
    }

    public function test_destructive_artisan_commands_are_blocked_when_shared_database_protection_is_active(): void
    {
        config()->set('branches.shared_database', true);
        $this->switchPrefix('cs2_');

        foreach (['db:wipe', 'migrate:fresh'] as $command) {
            $thrown = false;
            try {
                Event::dispatch(new CommandStarting($command, new ArrayInput([]), new NullOutput()));
            } catch (RuntimeException $e) {
                $thrown = str_contains($e->getMessage(), $command);
            }

            $this->assertTrue($thrown, "Expected [{$command}] to be blocked on shared database.");
        }
    }

    public function test_shared_sales_page_generates_per_branch_vietqr_and_splits_checkout_revenue_by_branch(): void
    {
        // 1. Seed Branch 1 (prefix = '')
        $this->switchPrefix('');
        $cs1 = $this->seedBranchSampleData(
            email: 'cashier-only-cs1@example.com',
            categoryName: 'Danh mục CS1',
            supplierName: 'NCC CS1',
            supplierPhone: '0901 111 111',
            productName: 'Áo sơ mi CS1',
            salePrice: 150000,
            stockQty: 5,
            soldQty: 0,
            storeAddress: 'Địa chỉ CS1',
            storeHotline: '0901 111 111'
        );
        $settingsPerm = Permission::findOrCreate('settings.manage', 'web');
        $cs1['user']->givePermissionTo($settingsPerm);

        // 2. Migrate & Seed Branch 2 (prefix = 'cs2_') on the same physical DB
        $this->switchPrefix('cs2_');
        Artisan::call('migrate', ['--force' => true]);
        $cs2 = $this->seedBranchSampleData(
            email: 'other-admin-cs2@example.com',
            categoryName: 'Danh mục CS2',
            supplierName: 'NCC CS2',
            supplierPhone: '0902 222 222',
            productName: 'Quần jean CS2',
            salePrice: 250000,
            stockQty: 4,
            soldQty: 0,
            storeAddress: 'Địa chỉ CS2',
            storeHotline: '0902 222 222'
        );

        // 3. Operate from Branch 1 (single shared /ban-hang & /settings/payment) with both branches configured
        $this->switchPrefix('');
        config()->set('branches.portal', [
            ['key' => 'cs1', 'name' => 'Cơ sở 1', 'prefix' => '', 'url' => 'https://cs1.example.com'],
            ['key' => 'cs2', 'name' => 'Cơ sở 2', 'prefix' => 'cs2_', 'url' => 'https://cs2.example.com'],
        ]);

        $this->actingAs($cs1['user']);

        // Configure different bank accounts for CS1 and CS2 in one place (/settings/payment)
        $this->post(route('settings.payment.update'), [
            'bank_name' => 'vietcombank',
            'bank_account' => '1111111111',
            'bank_account_name' => 'CHU TK CS1',
            'branch_banks' => [
                'cs2' => [
                    'bank_name' => 'mb',
                    'bank_account' => '2222222222',
                    'bank_account_name' => 'CHU TK CS2',
                ],
            ],
        ])->assertRedirect(route('settings.payment.edit'));

        // Verify shared /ban-hang page renders both branch filters
        $this->get(route('sales.index'))
            ->assertOk()
            ->assertSee('Tất cả cơ sở')
            ->assertSee('Cơ sở 1')
            ->assertSee('Cơ sở 2');

        // Lookup with branch-prefixed code (cs2-1) resolves directly to CS2
        $this->getJson('/ban-hang/products/cs2-'.$cs2['product']->id)
            ->assertOk()
            ->assertJsonPath('branch_key', 'cs2')
            ->assertJsonPath('name', 'Quần jean CS2');

        // Lookup with plain code '1' across all branches returns ambiguous matches for both CS1 & CS2
        $this->getJson('/ban-hang/products/'.$cs1['product']->id.'?branch=all')
            ->assertOk()
            ->assertJsonPath('ambiguous', true)
            ->assertJsonCount(2, 'matches');

        // Lookup filtered to CS2 resolves directly without ambiguity
        $this->getJson('/ban-hang/products/'.$cs2['product']->id.'?branch=cs2')
            ->assertOk()
            ->assertJsonPath('branch_key', 'cs2')
            ->assertJsonPath('name', 'Quần jean CS2');

        // Create VietQR payment for a combined cart (2x CS1 = 300,000 + 1x CS2 = 250,000)
        $paymentResponse = $this->postJson(route('sales.createPayment'), [
            'items' => [
                ['branch_key' => 'cs1', 'id' => $cs1['product']->id, 'quantity' => 2],
                ['branch_key' => 'cs2', 'id' => $cs2['product']->id, 'quantity' => 1],
            ],
        ]);

        $paymentResponse->assertOk()
            ->assertJsonPath('total', 550000)
            ->assertJsonCount(2, 'payments')
            ->assertJsonPath('payments.0.branch_key', 'cs1')
            ->assertJsonPath('payments.0.bank_code', 'vietcombank')
            ->assertJsonPath('payments.0.account_number', '1111111111')
            ->assertJsonPath('payments.0.amount', 300000)
            ->assertJsonPath('payments.1.branch_key', 'cs2')
            ->assertJsonPath('payments.1.bank_code', 'mb')
            ->assertJsonPath('payments.1.account_number', '2222222222')
            ->assertJsonPath('payments.1.amount', 250000);

        $cs1QrUrl = (string) $paymentResponse->json('payments.0.qr_url');
        $cs2QrUrl = (string) $paymentResponse->json('payments.1.qr_url');
        $this->assertStringContainsString('vietcombank-1111111111-compact2.png?amount=300000', $cs1QrUrl);
        $this->assertStringContainsString('mb-2222222222-compact2.png?amount=250000', $cs2QrUrl);

        $paymentToken = (string) $paymentResponse->json('payment_token');
        $cs1Ref = (string) $paymentResponse->json('payments.0.payment_reference');
        $cs2Ref = (string) $paymentResponse->json('payments.1.payment_reference');

        // Checkout with the payment token splits stock deduction & revenue cleanly into CS1 and CS2
        $checkoutResponse = $this->postJson(route('sales.checkout'), [
            'items' => [
                ['branch_key' => 'cs1', 'id' => $cs1['product']->id, 'quantity' => 2],
                ['branch_key' => 'cs2', 'id' => $cs2['product']->id, 'quantity' => 1],
            ],
            'payment_method' => 'transfer',
            'payment_token' => $paymentToken,
        ]);

        $checkoutResponse->assertOk()
            ->assertJsonCount(2, 'sales')
            ->assertJsonPath('sales.0.branch_key', 'cs1')
            ->assertJsonPath('sales.0.total_amount', 300000)
            ->assertJsonPath('sales.0.payment_reference', $cs1Ref)
            ->assertJsonPath('sales.1.branch_key', 'cs2')
            ->assertJsonPath('sales.1.total_amount', 250000)
            ->assertJsonPath('sales.1.payment_reference', $cs2Ref);

        // Verify Branch 1 stock & sales table
        $this->switchPrefix('');
        $this->assertSame(3, Product::query()->findOrFail($cs1['product']->id)->quantity);
        $cs1Sale = Sale::query()->where('payment_reference', $cs1Ref)->first();
        $this->assertNotNull($cs1Sale);
        $this->assertSame(300000.0, (float) $cs1Sale->total_amount);
        $this->assertSame($cs1['user']->id, $cs1Sale->user_id);

        // Verify Branch 2 stock & sales table (and bank setting saved in cs2_settings)
        $this->switchPrefix('cs2_');
        $this->assertSame('mb', Setting::get('bank_name'));
        $this->assertSame('2222222222', Setting::get('bank_account'));
        $this->assertSame(3, Product::query()->findOrFail($cs2['product']->id)->quantity);
        $cs2Sale = Sale::query()->where('payment_reference', $cs2Ref)->first();
        $this->assertNotNull($cs2Sale);
        $this->assertSame(250000.0, (float) $cs2Sale->total_amount);
        // Cashier email does not exist in cs2_users -> safely stored as null without FK violation
        $this->assertNull($cs2Sale->user_id);
        $this->assertNotEmpty($cs2Sale->public_id);
    }

    private function switchPrefix(string $prefix): void
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', $this->sqlitePath);
        config()->set('database.connections.sqlite.prefix', $prefix);
        config()->set('database.connections.sqlite.prefix_indexes', true);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return array{user:User,category:Category,supplier:Supplier,product:Product,sale:Sale}
     */
    private function seedBranchSampleData(
        string $email,
        string $categoryName,
        string $supplierName,
        string $supplierPhone,
        string $productName,
        float $salePrice,
        int $stockQty,
        int $soldQty,
        string $storeAddress,
        string $storeHotline
    ): array {
        $adminRole = Role::findOrCreate('admin', 'web');
        foreach (['products.view', 'sales.revenue.view'] as $permissionName) {
            $permission = Permission::findOrCreate($permissionName, 'web');
            $adminRole->givePermissionTo($permission);
        }

        $user = User::create([
            'name' => 'Admin '.$supplierName,
            'email' => $email,
            'password' => 'password',
        ]);
        $user->assignRole($adminRole);

        $category = Category::create([
            'name' => $categoryName,
            'description' => null,
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'responsible_user_id' => $user->id,
            'type' => 'ncc_it_san_pham',
            'name' => $supplierName,
            'phone' => $supplierPhone,
            'bank_name' => 'VCB',
            'bank_account_name' => $supplierName,
            'bank_account_number' => '123456789',
        ]);

        $consignment = ConsignmentNote::create([
            'responsible_user_id' => $user->id,
            'supplier_id' => $supplier->id,
            'sent_date' => Carbon::now()->subDays(10)->toDateString(),
            'quantity' => $stockQty + $soldQty,
        ]);

        $product = Product::create([
            'consignment_note_id' => $consignment->id,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
            'created_by_id' => $user->id,
            'name' => $productName,
            'sale_price' => $salePrice,
            'quantity' => $stockQty,
        ]);

        $sale = Sale::create([
            'user_id' => $user->id,
            'payment_method' => 'cash',
            'total_amount' => $salePrice * $soldQty,
            'items_count' => $soldQty,
            'completed_at' => Carbon::now(),
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_public_id' => (string) $product->public_id,
            'product_name' => $product->name,
            'quantity' => $soldQty,
            'unit_price' => $salePrice,
            'line_total' => $salePrice * $soldQty,
        ]);

        Setting::set('store_address', $storeAddress);
        Setting::set('store_hotline', $storeHotline);
        Setting::set('store_hours', '08:30 - 21:00');
        Setting::set('supplier_discount_ncc_it_san_pham', 20);

        return compact('user', 'category', 'supplier', 'product', 'sale');
    }
}
