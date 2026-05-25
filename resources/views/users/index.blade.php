<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-gray-900">Tài khoản</h2>
                <p class="text-sm text-gray-500">Quản lý tài khoản, vai trò và quyền trực tiếp.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200 lg:col-span-1">
                    <h3 class="text-lg font-semibold text-gray-900">Thêm tài khoản</h3>
                    <form method="POST" action="{{ route('users.store') }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Họ tên</label>
                            <input name="name" value="{{ old('name') }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Mật khẩu</label>
                                <input type="password" name="password" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Xác nhận</label>
                                <input type="password" name="password_confirmation" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                            </div>
                        </div>
                        <div>
                            <p class="block text-sm font-medium text-gray-700">Vai trò</p>
                            <div class="mt-2 grid gap-2">
                                @foreach ($roles as $role)
                                    <label class="flex items-center gap-2 rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-700">
                                        <input type="checkbox" name="roles[]" value="{{ $role->name }}" @checked(in_array($role->name, old('roles', ['staff']))) class="rounded border-gray-300 text-slate-900 focus:ring-slate-900">
                                        <span>{{ $role->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('roles') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <p class="block text-sm font-medium text-gray-700">Quyền trực tiếp</p>
                            <div class="mt-2 space-y-4">
                                @foreach ($permissionGroups as $group)
                                    <div class="rounded-2xl border border-gray-200 p-4">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $group['label'] }}</p>
                                        <div class="mt-3 grid gap-2">
                                            @foreach ($group['permissions'] as $permission)
                                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="checkbox" name="permissions[]" value="{{ $permission['name'] }}" @checked(in_array($permission['name'], old('permissions', []))) class="rounded border-gray-300 text-slate-900 focus:ring-slate-900">
                                                    <span>{{ $permission['label'] }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @error('permissions') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <button class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Lưu tài khoản</button>
                    </form>
                </div>

                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200 lg:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-lg font-semibold text-gray-900">Danh sách</h3>
                        <form method="GET" action="{{ route('users.index') }}" class="flex flex-wrap items-center gap-2">
                            <input name="public_id" value="{{ request('public_id') }}" class="w-36 rounded-xl border-gray-300 text-sm focus:border-slate-900 focus:ring-slate-900" placeholder="Mã">
                            <input name="search" value="{{ request('search') }}" class="w-56 rounded-xl border-gray-300 text-sm focus:border-slate-900 focus:ring-slate-900" placeholder="Tìm tên hoặc email">
                            <button class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white">Tìm</button>
                        </form>
                    </div>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="py-3 pr-4">Mã</th>
                                    <th class="py-3 pr-4">Tài khoản</th>
                                    <th class="py-3 pr-4">Vai trò</th>
                                    <th class="py-3 pr-4">Quyền trực tiếp</th>
                                    <th class="py-3 pr-4"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($users as $user)
                                    <tr class="align-top">
                                        <td class="py-3 pr-4 font-medium text-slate-900">#{{ $user->public_id }}</td>
                                        <td class="py-3 pr-4">
                                            <div class="font-medium text-gray-900">{{ $user->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $user->email }}</div>
                                        </td>
                                        <td class="py-3 pr-4 text-gray-600">
                                            {{ $user->roles->pluck('name')->join(', ') ?: '---' }}
                                        </td>
                                        <td class="py-3 pr-4 text-gray-600">
                                            {{ $user->permissions->pluck('name')->join(', ') ?: '---' }}
                                        </td>
                                        <td class="py-3 pr-4 text-right">
                                            <a href="{{ route('users.edit', $user) }}" class="text-slate-900 hover:underline">Sửa</a>
                                            <span class="ms-4 inline-block align-middle">
                                                <x-confirm-action
                                                    :name="'delete-user-'.$user->public_id"
                                                    :action="route('users.destroy', $user)"
                                                    title="Xoá tài khoản"
                                                    message="Bạn có chắc chắn muốn xoá tài khoản này? Hành động này không thể hoàn tác."
                                                    confirm-text="Xoá"
                                                    trigger-text="Xoá"
                                                />
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-gray-500">Chưa có tài khoản nào.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $users->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
