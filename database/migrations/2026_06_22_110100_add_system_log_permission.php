<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::findOrCreate('system-logs.view', 'web');

        foreach (['admin', 'super-admin'] as $roleName) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();

            if ($role) {
                $role->givePermissionTo($permission);
            }
        }
    }

    public function down(): void
    {
        if ($permission = Permission::where('name', 'system-logs.view')->first()) {
            foreach (['admin', 'super-admin'] as $roleName) {
                $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();

                if ($role) {
                    $role->revokePermissionTo($permission);
                }
            }

            $permission->delete();
        }
    }
};
