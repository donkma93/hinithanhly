<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">Sửa vai trò</h2>
            <p class="text-sm text-gray-500">Đổi tên vai trò và chọn lại các quyền mà vai trò đó được phép dùng.</p>
            <p class="mt-1 text-xs font-medium uppercase tracking-wide text-gray-400">Vai trò: {{ $role->name }}</p>
        </div>
    </x-slot>

    @php
        $selectedPermissions = old('permissions', $role->permissions->pluck('name')->all());
    @endphp

    <div class="py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <form method="POST" action="{{ route('roles.update', $role) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tên vai trò</label>
                        <input name="name" value="{{ old('name', $role->name) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <p class="block text-sm font-medium text-gray-700">Quyền của vai trò</p>
                        <p class="mt-1 text-xs text-gray-500">Chỉ các quyền ở đây mới được tài khoản nhận thông qua vai trò này.</p>
                        <div class="mt-4 space-y-4">
                            @foreach ($permissionGroups as $group)
                                <div class="rounded-2xl border border-gray-200 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <h3 class="font-semibold text-gray-900">{{ $group['label'] }}</h3>
                                        <span class="text-xs uppercase tracking-wide text-gray-400">{{ count($group['permissions']) }} mục</span>
                                    </div>
                                    <div class="mt-3 grid gap-2 md:grid-cols-2">
                                        @foreach ($group['permissions'] as $permission)
                                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                                <input type="checkbox" name="permissions[]" value="{{ $permission['name'] }}" @checked(in_array($permission['name'], $selectedPermissions)) class="rounded border-gray-300 text-slate-900 focus:ring-slate-900">
                                                <span>{{ $permission['label'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @error('permissions') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <button class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white">Cập nhật vai trò</button>
                        <a href="{{ route('roles.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Quay lại</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>