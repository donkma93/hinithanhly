<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">Doanh thu</h2>
            <p class="text-sm text-gray-500">Tổng hợp doanh thu theo khoảng ngày và phương thức thanh toán.</p>
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
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm text-gray-500">Tổng hoá đơn</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $summary['total_sales'] }}</p>
                </div>
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm text-gray-500">Tổng doanh thu</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ number_format((float) $summary['total_revenue'], 0, ',', '.') }} đ</p>
                </div>
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm text-gray-500">Tiền mặt</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ number_format((float) $summary['cash_revenue'], 0, ',', '.') }} đ</p>
                </div>
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm text-gray-500">Chuyển khoản</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ number_format((float) $summary['transfer_revenue'], 0, ',', '.') }} đ</p>
                </div>
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm text-gray-500">Tổng mặt hàng</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $summary['items_count'] }}</p>
                </div>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold text-gray-900">Hoá đơn theo thời gian</h3>
                    <form method="GET" action="{{ route('revenue.index') }}" class="flex flex-wrap items-center gap-2">
                        <x-per-page-select :value="request('per_page', 10)" />
                        <input type="date" name="from" value="{{ request('from', $startDate->format('Y-m-d')) }}" class="rounded-xl border-gray-300 text-sm focus:border-slate-900 focus:ring-slate-900">
                        <input type="date" name="to" value="{{ request('to', $endDate->format('Y-m-d')) }}" class="rounded-xl border-gray-300 text-sm focus:border-slate-900 focus:ring-slate-900">
                        <button class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white">Lọc</button>
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
                                <th class="py-3 pr-4">Người bán</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($sales as $sale)
                                <tr>
                                    <td class="py-3 pr-4 font-medium text-slate-900">#{{ $sale->public_id_display }}</td>
                                    <td class="py-3 pr-4 text-gray-600">{{ optional($sale->completed_at)->format('d/m/Y H:i') ?? $sale->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="py-3 pr-4 text-gray-600">{{ $paymentLabels[$sale->payment_method] ?? $sale->payment_method }}</td>
                                    <td class="py-3 pr-4 text-gray-900">{{ number_format((float) $sale->total_amount, 0, ',', '.') }} đ</td>
                                    <td class="py-3 pr-4 text-gray-600">{{ $sale->cashier?->name ?? '---' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-8 text-center text-gray-500">Chưa có doanh thu trong khoảng thời gian này.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $sales->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>