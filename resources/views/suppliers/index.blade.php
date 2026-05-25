<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">Nhà cung cấp</h2>
            <p class="text-sm text-gray-500">Quản lý tên người phụ trách, cấp độ nhà cung cấp và thông tin ngân hàng.</p>
        </div>
    </x-slot>

    @php($bankOptions = $bankOptions ?? config('banks', []))

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200 lg:col-span-1">
                    @canany(['suppliers.create', 'suppliers.manage'])
                        <h3 class="text-lg font-semibold text-gray-900">Thêm nhà cung cấp</h3>
                        <form method="POST" action="{{ route('suppliers.store') }}" class="mt-4 space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Người phụ trách</label>
                                <input name="responsible_name" value="{{ old('responsible_name') }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" placeholder="Nhập tên người phụ trách">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Phân loại</label>
                                <select name="type" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                                    <option value="">-- Chọn loại --</option>
                                    @foreach ($supplierTypes as $value => $label)
                                        <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tên nhà cung cấp</label>
                                <input name="name" value="{{ old('name') }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Số điện thoại</label>
                                    <input name="phone" value="{{ old('phone') }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Chủ tài khoản</label>
                                    <input name="bank_account_name" value="{{ old('bank_account_name') }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900">
                                </div>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Ngân hàng</label>
                                    <select name="bank_name" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900">
                                        <option value="">-- Chọn ngân hàng --</option>
                                        @foreach ($bankOptions as $value => $label)
                                            <option value="{{ $label }}" @selected(old('bank_name') === $label)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Số tài khoản</label>
                                    <input name="bank_account_number" value="{{ old('bank_account_number') }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Ghi chú</label>
                                <textarea name="notes" rows="4" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900">{{ old('notes') }}</textarea>
                            </div>
                            <button class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Lưu nhà cung cấp</button>
                        </form>
                    @else
                        <h3 class="text-lg font-semibold text-gray-900">Thêm nhà cung cấp</h3>
                        <p class="mt-3 text-sm text-gray-500">Bạn chỉ có quyền xem nhà cung cấp.</p>
                    @endcanany
                </div>

                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200 lg:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-lg font-semibold text-gray-900">Danh sách</h3>
                        <form method="GET" action="{{ route('suppliers.index') }}" class="flex items-center gap-2">
                            <input name="public_id" value="{{ request('public_id') }}" class="w-64 rounded-xl border-gray-300 text-sm focus:border-slate-900 focus:ring-slate-900" placeholder="Tìm bằng mã công khai">
                            <button class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white">Tìm</button>
                        </form>
                    </div>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="py-3 pr-4">Mã</th>
                                    <th class="py-3 pr-4">Tên</th>
                                    <th class="py-3 pr-4">Loại</th>
                                    <th class="py-3 pr-4">Phụ trách</th>
                                    <th class="py-3 pr-4">Ngân hàng</th>
                                    <th class="py-3 pr-4"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($suppliers as $supplier)
                                    <tr>
                                        <td class="py-3 pr-4 font-medium text-slate-900">#{{ $supplier->public_id }}</td>
                                        <td class="py-3 pr-4 font-medium text-gray-900">{{ $supplier->name }}</td>
                                        <td class="py-3 pr-4 text-gray-600">{{ \App\Models\Supplier::TYPES[$supplier->type] ?? $supplier->type }}</td>
                                        <td class="py-3 pr-4 text-gray-600">{{ $supplier->responsible_name ?: '---' }}</td>
                                        <td class="py-3 pr-4 text-gray-600">{{ $supplier->bank_name ?: '---' }}</td>
                                        <td class="py-3 pr-4 text-right">
                                            @canany(['suppliers.update', 'suppliers.manage'])
                                                <a href="{{ route('suppliers.edit', $supplier) }}" class="text-slate-900 hover:underline">Sửa</a>
                                            @endcanany
                                            @canany(['suppliers.delete', 'suppliers.manage'])
                                                <span class="ms-4 inline-block align-middle">
                                                    <x-confirm-action
                                                        :name="'delete-supplier-'.$supplier->public_id"
                                                        :action="route('suppliers.destroy', $supplier)"
                                                        title="Xoá nhà cung cấp"
                                                        message="Bạn có chắc chắn muốn xoá nhà cung cấp này?"
                                                        confirm-text="Xoá"
                                                        trigger-text="Xoá"
                                                    />
                                                </span>
                                            @endcanany
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="py-8 text-center text-gray-500">Chưa có nhà cung cấp nào.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $suppliers->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
