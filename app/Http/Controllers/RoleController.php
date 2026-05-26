<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Support\PermissionCatalog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:permissions.view|permissions.manage')->only('index');
        $this->middleware('permission:permissions.update|permissions.manage')->only(['edit', 'update']);
    }

    public function index(Request $request): View
    {
        $perPage = $this->resolvePerPage($request);
        $permissions = Permission::query()->orderBy('name')->get(['id', 'name']);

        $roles = Role::query()
            ->with('permissions:id,name')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $roles->setCollection(
            $roles->getCollection()->map(function (Role $role): Role {
                $role->setAttribute('user_count', User::role($role->name)->count());

                return $role;
            })
        );

        return view('roles.index', [
            'roles' => $roles,
            'permissions' => $permissions,
            'permissionGroups' => PermissionCatalog::grouped($permissions),
        ]);
    }

    public function edit(Role $role): View
    {
        $permissions = Permission::query()->orderBy('name')->get(['id', 'name']);

        return view('roles.edit', [
            'role' => $role->load('permissions:id,name'),
            'permissions' => $permissions,
            'permissionGroups' => PermissionCatalog::grouped($permissions),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->update([
            'name' => $data['name'],
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        AuditLog::record([
            'user_id' => $request->user()?->id,
            'action' => 'roles.update',
            'method' => $request->method(),
            'route_name' => 'roles.update',
            'path' => $request->path(),
            'status_code' => 302,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => [
                'role_id' => $role->id,
                'name' => $role->name,
                'permissions' => $data['permissions'] ?? [],
            ],
        ]);

        return redirect()->route('roles.index')->with('status', 'Đã cập nhật vai trò.');
    }
}