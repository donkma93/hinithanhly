<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-gray-900">Vai trò</h2>
                <p class="text-sm text-gray-500">Quản lý vai trò và các quyền mà mỗi vai trò được phép dùng.</p>
            </div>
            <form method="GET" action="{{ route('roles.index') }}">
                <x-per-page-select :value="request('per_page', 10)" />
            </form>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold text-gray-900">Danh sách vai trò</h3>
                    <div class="text-sm text-gray-500">{{ $roles->total() }} vai trò</div>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="py-3 pr-4">Vai trò</th>
                                <th class="py-3 pr-4">Số tài khoản</th>
                                <th class="py-3 pr-4">Số quyền</th>
                                <th class="py-3 pr-4">Quyền đầu tiên</th>
                                <th class="py-3 pr-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($roles as $role)
                                <tr>
                                    <td class="py-3 pr-4 font-medium text-gray-900">{{ $role->name }}</td>
                                    <td class="py-3 pr-4 text-gray-600">{{ $role->user_count ?? 0 }}</td>
                                    <td class="py-3 pr-4 text-gray-600">{{ $role->permissions->count() }}</td>
                                    <td class="py-3 pr-4 text-gray-600">{{ $role->permissions->pluck('name')->take(3)->join(', ') ?: '---' }}</td>
                                    <td class="py-3 pr-4 text-right">
                                        <a href="{{ route('roles.edit', $role) }}" class="text-slate-900 hover:underline">Sửa</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-gray-500">Chưa có vai trò nào.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $roles->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>