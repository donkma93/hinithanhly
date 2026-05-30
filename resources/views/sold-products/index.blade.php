<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">Sản phẩm đã bán</h2>
            <p class="text-sm text-gray-500">Theo dõi các hoá đơn đã chốt, phương thức thanh toán và danh sách hàng đã bán.</p>
        </div>
    </x-slot>

    @php
        $paymentLabels = [
            'cash' => 'Tiền mặt',
            'transfer' => 'Chuyển khoản',
        ];
    @endphp

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold text-gray-900">Danh sách hoá đơn</h3>
                    <form method="GET" action="{{ route('sold-products.index') }}" class="flex flex-wrap items-center gap-2">
                        <x-per-page-select :value="request('per_page', 10)" />
                        <input name="public_id" value="{{ request('public_id') }}" class="w-52 rounded-xl border-gray-300 text-sm focus:border-slate-900 focus:ring-slate-900" placeholder="Tìm theo mã hoá đơn">
                        <select name="payment_method" class="rounded-xl border-gray-300 text-sm focus:border-slate-900 focus:ring-slate-900">
                            <option value="">Tất cả thanh toán</option>
                            <option value="cash" @selected(request('payment_method') === 'cash')>Tiền mặt</option>
                            <option value="transfer" @selected(request('payment_method') === 'transfer')>Chuyển khoản</option>
                        </select>
                        <button class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white">Tìm</button>
                    </form>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="py-3 pr-4">Mã hoá đơn</th>
                                <th class="py-3 pr-4">Thời gian</th>
                                <th class="py-3 pr-4">Thanh toán</th>
                                <th class="py-3 pr-4">Tổng tiền</th>
                                <th class="py-3 pr-4">Số mặt hàng</th>
                                <th class="py-3 pr-4">Người bán</th>
                                <th class="py-3 pr-4">Sản phẩm</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($sales as $sale)
                                <tr>
                                    <td class="py-3 pr-4 font-medium text-slate-900">#{{ $sale->public_id_display }}</td>
                                    <td class="py-3 pr-4 text-gray-600">{{ optional($sale->completed_at)->format('d/m/Y H:i') ?? $sale->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="py-3 pr-4 text-gray-600">{{ $paymentLabels[$sale->payment_method] ?? $sale->payment_method }}</td>
                                    <td class="py-3 pr-4 text-gray-900">{{ number_format((float) $sale->total_amount, 0, ',', '.') }} đ</td>
                                    <td class="py-3 pr-4 text-gray-600">{{ $sale->items_count }}</td>
                                    <td class="py-3 pr-4 text-gray-600">{{ $sale->cashier?->name ?? '---' }}</td>
                                    <td class="py-3 pr-4 text-gray-600">
                                        <div class="space-y-1">
                                            @foreach ($sale->items as $item)
                                                <div class="rounded-xl bg-slate-50 px-3 py-2 ring-1 ring-slate-100">
                                                    <div class="font-medium text-slate-900">{{ $item->product_name }}</div>
                                                    <div class="text-xs text-slate-500">#{{ $item->product_public_id }} · SL: {{ $item->quantity }} · {{ number_format((float) $item->line_total, 0, ',', '.') }} đ</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-8 text-center text-gray-500">Chưa có hoá đơn nào.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $sales->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>