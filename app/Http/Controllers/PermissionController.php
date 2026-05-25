<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Support\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:permissions.view|permissions.manage')->only('index');
        $this->middleware('permission:permissions.create|permissions.manage')->only('store');
        $this->middleware('permission:permissions.update|permissions.manage')->only('update');
        $this->middleware('permission:permissions.delete|permissions.manage')->only('destroy');
    }

    public function index(Request $request): View
    {
        $permissions = Permission::query()->orderBy('name')->get(['id', 'name', 'guard_name', 'created_at']);

        return view('permissions.index', [
            'permissions' => $permissions,
            'permissionGroups' => PermissionCatalog::grouped($permissions),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
        ]);

        $permission = Permission::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        AuditLog::record([
            'user_id' => $request->user()?->id,
            'action' => 'permissions.store',
            'method' => $request->method(),
            'route_name' => 'permissions.store',
            'path' => $request->path(),
            'status_code' => 302,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => [
                'permission_id' => $permission->id,
                'name' => $permission->name,
            ],
        ]);

        return redirect()->route('permissions.index')->with('status', 'Đã thêm quyền.');
    }

    public function edit(Permission $permission): View
    {
        return view('permissions.edit', compact('permission'));
    }

    public function update(Request $request, Permission $permission): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions', 'name')->ignore($permission->id)],
        ]);

        $permission->update([
            'name' => $data['name'],
        ]);

        AuditLog::record([
            'user_id' => $request->user()?->id,
            'action' => 'permissions.update',
            'method' => $request->method(),
            'route_name' => 'permissions.update',
            'path' => $request->path(),
            'status_code' => 302,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => [
                'permission_id' => $permission->id,
                'name' => $permission->name,
            ],
        ]);

        return redirect()->route('permissions.index')->with('status', 'Đã cập nhật quyền.');
    }

    public function destroy(Request $request, Permission $permission): RedirectResponse
    {
        $payload = [
            'permission_id' => $permission->id,
            'name' => $permission->name,
        ];

        $permission->delete();

        AuditLog::record([
            'user_id' => $request->user()?->id,
            'action' => 'permissions.destroy',
            'method' => $request->method(),
            'route_name' => 'permissions.destroy',
            'path' => $request->path(),
            'status_code' => 302,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => $payload,
        ]);

        return redirect()->route('permissions.index')->with('status', 'Đã xoá quyền.');
    }
}
