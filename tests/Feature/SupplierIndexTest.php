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
}
