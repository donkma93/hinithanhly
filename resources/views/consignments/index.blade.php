<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">Phiếu ký gửi</h2>
            <p class="text-sm text-gray-500">Chọn người phụ trách, nhà cung cấp và ngày gửi để khởi tạo phiếu.</p>
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
                    @canany(['consignments.create', 'consignments.manage'])
                        <h3 class="text-lg font-semibold text-gray-900">Tạo phiếu</h3>
                        <form method="POST" action="{{ route('consignments.store') }}" class="mt-4 space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Người phụ trách</label>
                                <select name="responsible_user_id" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                                    <option value="">-- Chọn nhân viên --</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}" @selected(old('responsible_user_id') == $user->id)>#{{ $user->public_id }} - {{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nhà cung cấp</label>
                                <select name="supplier_id" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                                    <option value="">-- Chọn nhà cung cấp --</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>#{{ $supplier->public_id }} - {{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Ngày gửi</label>
                                    <input type="date" name="sent_date" value="{{ old('sent_date') }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Số lượng gửi</label>
                                    <input type="number" name="quantity" min="1" value="{{ old('quantity', 1) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Ghi chú</label>
                                <textarea name="notes" rows="4" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900">{{ old('notes') }}</textarea>
                            </div>
                            <button class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Lưu phiếu</button>
                        </form>
                    @else
                        <h3 class="text-lg font-semibold text-gray-900">Tạo phiếu</h3>
                        <p class="mt-3 text-sm text-gray-500">Bạn chỉ có quyền xem phiếu ký gửi.</p>
                    @endcanany
                </div>

                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200 lg:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-lg font-semibold text-gray-900">Danh sách</h3>
                        <form method="GET" action="{{ route('consignments.index') }}" class="flex items-center gap-2">
                            <input name="public_id" value="{{ request('public_id') }}" class="w-64 rounded-xl border-gray-300 text-sm focus:border-slate-900 focus:ring-slate-900" placeholder="Tìm bằng mã công khai">
                            <button class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white">Tìm</button>
                        </form>
                    </div>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="py-3 pr-4">Mã</th>
                                    <th class="py-3 pr-4">Nhà cung cấp</th>
                                    <th class="py-3 pr-4">Phụ trách</th>
                                    <th class="py-3 pr-4">Ngày gửi</th>
                                    <th class="py-3 pr-4">SL</th>
                                    <th class="py-3 pr-4"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($consignments as $consignment)
                                    <tr>
                                        <td class="py-3 pr-4 font-medium text-slate-900">#{{ $consignment->public_id }}</td>
                                        <td class="py-3 pr-4 text-gray-600">{{ $consignment->supplier?->name ?? '---' }}</td>
                                        <td class="py-3 pr-4 text-gray-600">{{ $consignment->responsibleUser?->name ?? '---' }}</td>
                                        <td class="py-3 pr-4 text-gray-600">{{ optional($consignment->sent_date)->format('d/m/Y') }}</td>
                                        <td class="py-3 pr-4 text-gray-600">{{ $consignment->quantity }}</td>
                                        <td class="py-3 pr-4 text-right">
                                            @canany(['consignments.update', 'consignments.manage'])
                                                <a href="{{ route('consignments.edit', $consignment) }}" class="text-slate-900 hover:underline">Sửa</a>
                                            @endcanany
                                            @canany(['consignments.delete', 'consignments.manage'])
                                                <span class="ms-4 inline-block align-middle">
                                                    <x-confirm-action
                                                        :name="'delete-consignment-'.$consignment->public_id"
                                                        :action="route('consignments.destroy', $consignment)"
                                                        title="Xoá phiếu ký gửi"
                                                        message="Bạn có chắc chắn muốn xoá phiếu ký gửi này?"
                                                        confirm-text="Xoá"
                                                        trigger-text="Xoá"
                                                    />
                                                </span>
                                            @endcanany
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="py-8 text-center text-gray-500">Chưa có phiếu ký gửi nào.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $consignments->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
