<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">Cài đặt hệ thống</h2>
            <p class="text-sm text-gray-500">Thiết lập tài khoản ngân hàng và tỷ lệ chiết khấu theo loại nhà cung cấp.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">

        @if(session('status'))
            <div class="mb-4 rounded-md bg-emerald-50 p-3 text-emerald-700">{{ session('status') }}</div>
        @endif

            <form method="POST" action="{{ route('settings.payment.update') }}" class="space-y-6">
                @csrf

                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200 space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900">Thông tin ngân hàng</h3>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Tên ngân hàng</label>
                        <select name="bank_name" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900">
                            <option value="">-- Chọn ngân hàng --</option>
                            @foreach(config('banks', []) as $code => $label)
                                <option value="{{ $code }}" @selected(old('bank_name', $bankName) === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Số tài khoản</label>
                            <input name="bank_account" value="{{ old('bank_account', $accountNumber) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Tên chủ tài khoản</label>
                            <input name="bank_account_name" value="{{ old('bank_account_name', $accountName) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" />
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200 space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900">Chiết khấu theo loại nhà cung cấp</h3>
                    <p class="text-sm text-gray-500">Nhập tỷ lệ phần trăm cố định cho từng loại. Giá trị này sẽ được dùng làm mặc định cho các nghiệp vụ liên quan tới nhà cung cấp.</p>

                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach(\App\Models\Supplier::TYPES as $type => $label)
                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ $label }}</label>
                                <div class="mt-1 flex items-center gap-2">
                                    <input
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        name="supplier_discount_{{ $type }}"
                                        value="{{ old('supplier_discount_'.$type, $supplierDiscountRates[$type] ?? 0) }}"
                                        class="w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900"
                                    >
                                    <span class="text-sm text-gray-500">%</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="rounded-xl bg-emerald-500 px-5 py-3 text-white font-semibold">Lưu cài đặt</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
