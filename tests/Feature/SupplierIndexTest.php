<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SupplierIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_supplier_index_shows_clickable_phone_number_in_list(): void
    {
        $user = User::query()->where('email', 'admin@kygui.local')->firstOrFail();
        $this->actingAs($user);

        Supplier::create([
            'responsible_name' => 'van don',
            'type' => 'cho_tang',
            'name' => 'CHECK',
            'phone' => '0901 234 567',
            'bank_name' => null,
            'bank_account_name' => null,
            'bank_account_number' => null,
            'notes' => null,
        ]);

        $response = $this->get(route('suppliers.index'));

        $response->assertOk();
        $response->assertSee('Số điện thoại');
        $response->assertSee('0901 234 567');
        $response->assertSee('href="tel:0901234567"', false);
    }

    public function test_supplier_index_filters_by_exact_phone_number(): void
    {
        $user = User::query()->where('email', 'admin@kygui.local')->firstOrFail();
        $this->actingAs($user);

        Supplier::create([
            'responsible_name' => 'phone exact a',
            'type' => 'cho_tang',
            'name' => 'NCC PHONE EXACT A',
            'phone' => '0901 234 567',
            'bank_name' => null,
            'bank_account_name' => null,
            'bank_account_number' => null,
            'notes' => null,
        ]);

        Supplier::create([
            'responsible_name' => 'phone exact b',
            'type' => 'cho_tang',
            'name' => 'NCC PHONE EXACT B',
            'phone' => '0901234567',
            'bank_name' => null,
            'bank_account_name' => null,
            'bank_account_number' => null,
            'notes' => null,
        ]);

        $response = $this->get(route('suppliers.index', ['phone' => '0901 234 567']));

        $response->assertOk();
        $response->assertSee('NCC PHONE EXACT A');
        $response->assertDontSee('NCC PHONE EXACT B');

        $partialResponse = $this->get(route('suppliers.index', ['phone' => '0901']));

        $partialResponse->assertOk();
        $partialResponse->assertDontSee('NCC PHONE EXACT A');
        $partialResponse->assertDontSee('NCC PHONE EXACT B');
    }

    public function test_supplier_index_filters_by_partial_name_and_ignores_responsible_name_filter(): void
    {
        $user = User::query()->where('email', 'admin@kygui.local')->firstOrFail();
        $this->actingAs($user);

        Supplier::create([
            'responsible_name' => 'nguoi phu trach khong loc',
            'type' => 'cho_tang',
            'name' => 'NCC HOA SEN',
            'phone' => null,
            'bank_name' => null,
            'bank_account_name' => null,
            'bank_account_number' => null,
            'notes' => null,
        ]);

        Supplier::create([
            'responsible_name' => 'nguoi phu trach khac',
            'type' => 'cho_tang',
            'name' => 'NCC MAI VANG',
            'phone' => null,
            'bank_name' => null,
            'bank_account_name' => null,
            'bank_account_number' => null,
            'notes' => null,
        ]);

        $response = $this->get(route('suppliers.index', ['name' => 'HOA']));

        $response->assertOk();
        $response->assertSee('NCC HOA SEN');
        $response->assertDontSee('NCC MAI VANG');

        $responsibleResponse = $this->get(route('suppliers.index', ['responsible_name' => 'nguoi phu trach khong loc']));

        $responsibleResponse->assertOk();
        $responsibleResponse->assertSee('NCC HOA SEN');
        $responsibleResponse->assertSee('NCC MAI VANG');
    }
}
