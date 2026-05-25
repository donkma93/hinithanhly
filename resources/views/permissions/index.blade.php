<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-gray-900">Phân quyền</h2>
                <p class="text-sm text-gray-500">Tạo, sửa và xoá quyền hệ thống. Thêm chức năng mới chỉ cần mở rộng danh sách này.</p>
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
                    <h3 class="text-lg font-semibold text-gray-900">Thêm quyền</h3>
                    <form method="POST" action="{{ route('permissions.store') }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tên quyền</label>
                            <input name="name" value="{{ old('name') }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" placeholder="Ví dụ: invoices.create" required>
                            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <button class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Lưu quyền</button>
                    </form>
                </div>

                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200 lg:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-lg font-semibold text-gray-900">Danh sách quyền</h3>
                        <div class="text-sm text-gray-500">{{ $permissions->count() }} quyền</div>
                    </div>

                    <div class="mt-4 space-y-4">
                        @foreach ($permissionGroups as $group)
                            <div class="rounded-2xl border border-gray-200 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <h4 class="font-semibold text-gray-900">{{ $group['label'] }}</h4>
                                    <span class="text-xs uppercase tracking-wide text-gray-400">{{ count($group['permissions']) }} mục</span>
                                </div>
                                <div class="mt-3 grid gap-2 md:grid-cols-2">
                                    @foreach ($group['permissions'] as $permission)
                                        <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2 text-sm">
                                            <div>
                                                <div class="font-medium text-gray-900">{{ $permission['name'] }}</div>
                                                <div class="text-xs text-gray-500">{{ $permission['label'] }}</div>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <a href="{{ route('permissions.edit', $permission['name']) }}" class="text-slate-900 hover:underline">Sửa</a>
                                                <x-confirm-action
                                                    :name="'delete-permission-'.$permission['name']"
                                                    :action="route('permissions.destroy', $permission['name'])"
                                                    title="Xoá quyền"
                                                    message="Bạn có chắc chắn muốn xoá quyền này? Hành động này có thể ảnh hưởng đến vai trò và tài khoản đang dùng quyền đó."
                                                    confirm-text="Xoá"
                                                    trigger-text="Xoá"
                                                />
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
