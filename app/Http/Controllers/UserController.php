<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:users.view|users.manage')->only('index');
        $this->middleware('permission:users.create|users.manage')->only('store');
        $this->middleware('permission:users.update|users.manage')->only('update');
        $this->middleware('permission:users.delete|users.manage')->only('destroy');
    }

    public function index(Request $request): View
    {
        $publicId = trim($request->string('public_id')->toString());
        $search = trim($request->string('search')->toString());
        $permissions = Permission::query()->orderBy('name')->get(['id', 'name']);

        return view('users.index', [
            'users' => User::query()
                ->with(['roles:id,name', 'permissions:id,name'])
                ->select(['id', 'public_id', 'name', 'email', 'created_at'])
                ->when($publicId !== '', fn ($query) => $query->where('public_id', $publicId))
                ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                }))
                ->latest()
                ->paginate(10)
                ->withQueryString(),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'permissions' => $permissions,
            'permissionGroups' => PermissionCatalog::grouped($permissions),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $roles = $data['roles'] ?? ['staff'];
        $user->syncRoles($roles);
        $user->syncPermissions($data['permissions'] ?? []);

        AuditLog::record([
            'user_id' => $request->user()?->id,
            'action' => 'users.store',
            'method' => $request->method(),
            'route_name' => 'users.store',
            'path' => $request->path(),
            'status_code' => 302,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $roles,
                'permissions' => $data['permissions'] ?? [],
            ],
        ]);

        return redirect()->route('users.index')->with('status', 'Đã thêm tài khoản.');
    }

    public function edit(User $user): View
    {
        $permissions = Permission::query()->orderBy('name')->get(['id', 'name']);

        return view('users.edit', [
            'user' => $user->load(['roles:id,name', 'permissions:id,name']),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'permissions' => $permissions,
            'permissionGroups' => PermissionCatalog::grouped($permissions),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();
        $roles = $data['roles'] ?? [];
        $user->syncRoles($roles);
        $user->syncPermissions($data['permissions'] ?? []);

        AuditLog::record([
            'user_id' => $request->user()?->id,
            'action' => 'users.update',
            'method' => $request->method(),
            'route_name' => 'users.update',
            'path' => $request->path(),
            'status_code' => 302,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $roles,
                'permissions' => $data['permissions'] ?? [],
            ],
        ]);

        return redirect()->route('users.index')->with('status', 'Đã cập nhật tài khoản.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->id === $user->id) {
            return redirect()->route('users.index')->with('status', 'Bạn không thể xoá chính tài khoản đang đăng nhập.');
        }

        $payload = [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];

        $user->delete();

        AuditLog::record([
            'user_id' => $request->user()?->id,
            'action' => 'users.destroy',
            'method' => $request->method(),
            'route_name' => 'users.destroy',
            'path' => $request->path(),
            'status_code' => 302,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => $payload,
        ]);

        return redirect()->route('users.index')->with('status', 'Đã xoá tài khoản.');
    }
}
