<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $staffRole = Role::findByName('staff', 'web');

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
        $staffRole = Role::findByName('staff', 'web');

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