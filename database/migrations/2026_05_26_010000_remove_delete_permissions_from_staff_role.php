<?php

use App\Models\Permission;
use App\Support\PermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        foreach (PermissionCatalog::names() as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $staffRole = Role::findOrCreate('staff', 'web');

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
        ]);
    }

    public function down(): void
    {
        foreach (PermissionCatalog::names() as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $staffRole = Role::query()
            ->where('name', 'staff')
            ->where('guard_name', 'web')
            ->first();

        if (! $staffRole) {
            return;
        }

        $staffRole->syncPermissions([
            'dashboard.view',
            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',
            'categories.manage',
            'suppliers.view',
            'suppliers.create',
            'suppliers.update',
            'suppliers.delete',
            'suppliers.manage',
            'consignments.view',
            'consignments.create',
            'consignments.update',
            'consignments.delete',
            'consignments.manage',
            'products.view',
            'products.create',
            'products.update',
            'products.delete',
            'products.manage',
        ]);
    }
};