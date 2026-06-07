<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">Sửa phiếu ký gửi</h2>
            <p class="text-sm text-gray-500">Cập nhật người phụ trách, nhà cung cấp, ngày gửi và số lượng.</p>
            <p class="mt-1 text-xs font-medium uppercase tracking-wide text-gray-400">Mã công khai: #{{ $consignment->public_id }}</p>
        </div>
    </x-slot>

    @php
        $supplierOptions = $suppliers->map(fn ($supplier) => [
            'value' => $supplier->id,
            'label' => '#'.$supplier->public_id.' - '.$supplier->name,
        ])->all();
    @endphp

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-10xl px-3 sm:px-6 lg:px-10">
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <form method="POST" action="{{ route('consignments.update', $consignment) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Người phụ trách</label>
                        <input name="responsible_name" value="{{ old('responsible_name', $consignment->responsible_name) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" placeholder="Nhập tên người phụ trách" required>
                        @error('responsible_name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nhà cung cấp</label>
                        <x-searchable-select
                            name="supplier_id"
                            :options="$supplierOptions"
                            :selected="old('supplier_id', $consignment->supplier_id)"
                            placeholder="-- Chọn nhà cung cấp --"
                            search-placeholder="Tìm theo mã hoặc tên"
                            empty-text="Không có nhà cung cấp phù hợp"
                        />
                        @error('supplier_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ngày gửi</label>
                            <input type="date" name="sent_date" value="{{ old('sent_date', optional($consignment->sent_date)->format('Y-m-d')) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                            @error('sent_date') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Số lượng gửi</label>
                            <input type="number" min="1" name="quantity" value="{{ old('quantity', $consignment->quantity) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                            @error('quantity') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Ghi chú</label>
                        <textarea name="notes" rows="5" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900">{{ old('notes', $consignment->notes) }}</textarea>
                        @error('notes') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-center gap-3">
                        <button class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white">Cập nhật</button>
                        <a href="{{ route('consignments.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Quay lại</a>
                    </div>
                    @can('consignments.delete')
                        <div class="mt-6 border-t border-gray-200 pt-5">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-sm text-gray-500">Thao tác này sẽ đưa phiếu ký gửi vào thùng rác thay vì xóa hẳn.</p>
                                <x-confirm-action
                                    :name="'delete-consignment-'.$consignment->public_id"
                                    :action="route('consignments.destroy', $consignment)"
                                    title="Đưa phiếu ký gửi vào thùng rác"
                                    message="Bạn có chắc chắn muốn đưa phiếu ký gửi này vào thùng rác?"
                                    confirm-text="Đưa vào thùng rác"
                                    trigger-text="Đưa vào thùng rác"
                                    trigger-class="inline-flex items-center justify-center rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 hover:bg-red-100"
                                />
                            </div>
                        </div>
                    @endcan
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
