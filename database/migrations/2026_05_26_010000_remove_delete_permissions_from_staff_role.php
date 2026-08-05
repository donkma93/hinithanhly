<?php

use App\Support\PermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');

        if (empty($tableNames)) {
            throw new \Exception('Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        }

        $permissionsTable = $tableNames['permissions'];
        $rolePermissionsTable = $tableNames['role_has_permissions'];

        $now = now();
        $permissionRows = collect(PermissionCatalog::names())
            ->map(fn (string $permissionName) => [
                'name' => $permissionName,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        DB::table($permissionsTable)->upsert($permissionRows, ['name', 'guard_name'], ['updated_at']);

        $staffRole = Role::findOrCreate('staff', 'web');

        $activePermissionNames = [
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
        ];

        $permissionIds = DB::table($permissionsTable)
            ->whereIn('name', $activePermissionNames)
            ->pluck('id', 'name');

        DB::table($rolePermissionsTable)
            ->where('role_id', $staffRole->id)
            ->delete();

        DB::table($rolePermissionsTable)->insert(
            collect($activePermissionNames)
                ->map(fn (string $permissionName) => [
                    'permission_id' => $permissionIds[$permissionName],
                    'role_id' => $staffRole->id,
                ])
                ->all()
        );
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');

        if (empty($tableNames)) {
            throw new \Exception('Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        }

        $permissionsTable = $tableNames['permissions'];
        $rolePermissionsTable = $tableNames['role_has_permissions'];

        $now = now();
        $permissionRows = collect(PermissionCatalog::names())
            ->map(fn (string $permissionName) => [
                'name' => $permissionName,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        DB::table($permissionsTable)->upsert($permissionRows, ['name', 'guard_name'], ['updated_at']);

        $staffRole = Role::query()
            ->where('name', 'staff')
            ->where('guard_name', 'web')
            ->first();

        if (! $staffRole) {
            return;
        }

        $permissionNames = [
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
        ];

        $permissionIds = DB::table($permissionsTable)
            ->whereIn('name', $permissionNames)
            ->pluck('id', 'name');

        DB::table($rolePermissionsTable)
            ->where('role_id', $staffRole->id)
            ->delete();

        DB::table($rolePermissionsTable)->insert(
            collect($permissionNames)
                ->map(fn (string $permissionName) => [
                    'permission_id' => $permissionIds[$permissionName],
                    'role_id' => $staffRole->id,
                ])
                ->all()
        );
    }
};
