<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">Sửa nhà cung cấp</h2>
            <p class="text-sm text-gray-500">Cập nhật tên người phụ trách, loại NCC và ngân hàng.</p>
            <p class="mt-1 text-xs font-medium uppercase tracking-wide text-gray-400">Mã công khai: #{{ $supplier->public_id }}</p>
        </div>
    </x-slot>

    @php($bankOptions = $bankOptions ?? config('banks', []))

    <div class="py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <form method="POST" action="{{ route('suppliers.update', $supplier) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Người phụ trách</label>
                        <input name="responsible_name" value="{{ old('responsible_name', $supplier->responsible_name) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" placeholder="Nhập tên người phụ trách">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Phân loại</label>
                        <select name="type" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                            <option value="">-- Chọn loại --</option>
                            @foreach ($supplierTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('type', $supplier->type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tên nhà cung cấp</label>
                        <input name="name" value="{{ old('name', $supplier->name) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Số điện thoại</label>
                            <input name="phone" value="{{ old('phone', $supplier->phone) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Chủ tài khoản</label>
                            <input name="bank_account_name" value="{{ old('bank_account_name', $supplier->bank_account_name) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900">
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ngân hàng</label>
                            <select name="bank_name" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900">
                                <option value="">-- Chọn ngân hàng --</option>
                                @foreach ($bankOptions as $value => $label)
                                    <option value="{{ $label }}" @selected(old('bank_name', $supplier->bank_name) === $label)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Số tài khoản</label>
                            <input name="bank_account_number" value="{{ old('bank_account_number', $supplier->bank_account_number) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Ghi chú</label>
                        <textarea name="notes" rows="5" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900">{{ old('notes', $supplier->notes) }}</textarea>
                    </div>
                    <div class="flex items-center gap-3">
                        <button class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white">Cập nhật</button>
                        <a href="{{ route('suppliers.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Quay lại</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
