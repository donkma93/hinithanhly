<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">Nhật ký người dùng</h2>
            <p class="text-sm text-gray-500">Tra cứu toàn bộ thao tác được ghi trong database.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <form method="GET" action="{{ route('logs.index') }}" class="grid gap-4 md:grid-cols-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Mã log</label>
                        <input name="log_id" value="{{ request('log_id') }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" placeholder="Ví dụ: 123">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Người dùng</label>
                        <select name="user_id" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900">
                            <option value="">Tất cả</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>#{{ $user->public_id }} - {{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Hành động</label>
                        <input name="action" value="{{ request('action') }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" placeholder="Ví dụ: products.store">
                    </div>
                    <div class="flex items-end gap-3">
                        <button class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white">Lọc</button>
                        <a href="{{ route('logs.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700">Xóa lọc</a>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Danh sách log</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-6 py-3">Mã</th>
                                <th class="px-6 py-3">Thời gian</th>
                                <th class="px-6 py-3">Người dùng</th>
                                <th class="px-6 py-3">Hành động</th>
                                <th class="px-6 py-3">Phương thức</th>
                                <th class="px-6 py-3">Trạng thái</th>
                                <th class="px-6 py-3">Đường dẫn</th>
                                <th class="px-6 py-3">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($logs as $log)
                                <tr class="align-top">
                                    <td class="px-6 py-4 font-medium text-slate-900">#{{ $log->id }}</td>
                                    <td class="px-6 py-4 text-gray-600">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900">{{ $log->user?->name ?? 'Hệ thống' }}</div>
                                        <div class="text-xs text-gray-500">{{ $log->user?->email ?? '---' }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">{{ $log->action }}</td>
                                    <td class="px-6 py-4 text-gray-600">{{ $log->method }}</td>
                                    <td class="px-6 py-4 text-gray-600">{{ $log->status_code ?? '---' }}</td>
                                    <td class="px-6 py-4 text-gray-600">
                                        <div>{{ $log->route_name ?: '---' }}</div>
                                        <div class="text-xs text-gray-400">{{ $log->path }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">{{ $log->ip_address }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-10 text-center text-gray-500">Chưa có log nào.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
