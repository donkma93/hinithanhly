<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Permission;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $permissions = PermissionCatalog::names();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $adminRole = Role::findOrCreate('admin');
        $superAdminRole = Role::findOrCreate('super-admin');
        $staffRole = Role::findOrCreate('staff');

        $adminRole->syncPermissions($permissions);
        $superAdminRole->syncPermissions($permissions);
        $staffRole->syncPermissions([
            'dashboard.view',
            'categories.view',
            'categories.create',
            'categories.update',
            'categories.manage',
            'suppliers.view',
            'suppliers.create',
            'suppliers.update',
            'suppliers.manage',
            'consignments.view',
            'consignments.create',
            'consignments.update',
            'consignments.manage',
            'products.view',
            'products.create',
            'products.update',
            'products.manage',
            'sales.records.view',
            'sales.revenue.view',
        ]);

        $admin = User::updateOrCreate(
            ['email' => 'admin@kygui.local'],
            ['name' => 'Administrator', 'password' => Hash::make('password')]
        );
        $admin->syncRoles(['admin']);

        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@kygui.local'],
            ['name' => 'Super Admin', 'password' => Hash::make('password')]
        );
        $superAdmin->syncRoles(['super-admin']);

        $staff = User::updateOrCreate(
            ['email' => 'staff@kygui.local'],
            ['name' => 'Staff User', 'password' => Hash::make('password')]
        );
        $staff->syncRoles(['staff']);
    }
}
