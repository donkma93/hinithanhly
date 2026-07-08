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

    @php
        $supplierDiscountRates = \App\Models\Setting::supplierDiscountRates();
        $supplierOptions = collect([[
            'value' => '',
            'label' => '-- Tất cả nhà cung cấp --',
        ]])->merge($suppliersForType->map(fn ($supplierOption) => [
            'value' => $supplierOption->id,
            'label' => '#'.($supplierOption->public_id_display ?? $supplierOption->public_id).' - '.$supplierOption->name,
        ]))->all();
    @endphp

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-10xl space-y-6 px-3 sm:px-6 lg:px-10">
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
                        <select name="supplier_type" class="rounded-xl border-gray-300 text-sm focus:border-slate-900 focus:ring-slate-900" onchange="this.form.requestSubmit()">
                            <option value="">-- Tất cả loại NCC --</option>
                            @foreach (collect(\App\Models\Supplier::TYPES)->except('ncc_nhieu_san_pham') as $type => $label)
                                <option value="{{ $type }}" {{ request('supplier_type') === $type ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="min-w-[260px] flex-1">
                            <x-searchable-select
                                name="supplier_id"
                                :options="$supplierOptions"
                                :selected="$selectedSupplierId"
                                placeholder="-- Tất cả nhà cung cấp --"
                                search-placeholder="Tìm NCC theo mã hoặc tên"
                                empty-text="Không có nhà cung cấp phù hợp"
                                depends-on="supplier_type"
                                :dependency-value="$supplierType"
                                :submit-on-select="true"
                            />
                        </div>
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
                                    <td class="py-3 pr-4 text-gray-600">{{ optional(($sale->completed_at ?? $sale->created_at)?->copy()->timezone($reportTimezone ?? 'Asia/Bangkok'))->format('d/m/Y H:i') }}</td>
                                    <td class="py-3 pr-4 text-gray-600">{{ $paymentLabels[$sale->payment_method] ?? $sale->payment_method }}</td>
                                    <td class="py-3 pr-4 text-gray-900">{{ number_format((float) $sale->items->sum('line_total'), 0, ',', '.') }} đ</td>
                                    <td class="py-3 pr-4 text-gray-600">{{ $sale->cashier?->name ?? '---' }}</td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="bg-gray-50 px-4 py-3">
                                        <div class="text-sm text-gray-700">Sản phẩm đã bán:</div>
                                        <div class="mt-2 overflow-x-auto">
                                            <table class="min-w-full text-sm">
                                                <thead class="text-left text-xs uppercase text-gray-500">
                                                    <tr>
                                                        <th class="py-2 pr-4">Sản phẩm</th>
                                                        <th class="py-2 pr-4">Nhà cung cấp</th>
                                                        <th class="py-2 pr-4">Loại NCC (Chiết khấu)</th>
                                                        <th class="py-2 pr-4">Số lượng</th>
                                                        <th class="py-2 pr-4">Thành tiền</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($sale->items as $item)
                                                        @php
                                                            $product = $item->product;
                                                            $supplier = $product?->supplier;
                                                            $supplierType = $supplier?->type ?? null;
                                                            $discountRate = $supplierType ? ($supplierDiscountRates[$supplierType] ?? 0) : 0;
                                                        @endphp
                                                        <tr>
                                                            <td class="py-2 pr-4 font-medium text-gray-900">{{ $product?->name ?? $item->product_name }}</td>
                                                            <td class="py-2 pr-4 text-gray-600">{{ $supplier?->name ?? '---' }}</td>
                                                            <td class="py-2 pr-4 text-gray-600">{{ $supplier?->type ? (\App\Models\Supplier::TYPES[$supplier->type] ?? $supplier->type) : '---' }} ({{ $discountRate }}%)</td>
                                                            <td class="py-2 pr-4 text-gray-600">{{ $item->quantity }}</td>
                                                            <td class="py-2 pr-4 text-gray-900">{{ number_format((float) $item->line_total, 0, ',', '.') }} đ</td>
                                                        </tr>
                                                    @empty
                                                        <tr><td colspan="5" class="py-3 text-gray-500">Không có sản phẩm.</td></tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-8 text-center text-gray-500">Chưa có doanh thu trong khoảng thời gian này.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $sales->links() }}</div>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Thống kê theo phân loại NCC</h3>
                        <p class="text-sm text-gray-500">Từ mỗi nhóm loại nhà cung cấp, hệ thống thống kê tiếp từng NCC chi tiết trong khoảng thời gian đã chọn.</p>
                    </div>
                </div>

                @if ($supplierTypeSummaries->isEmpty())
                    <div class="mt-4 rounded-2xl bg-gray-50 px-4 py-8 text-center text-sm text-gray-500">
                        Chưa có doanh thu để thống kê theo phân loại nhà cung cấp trong khoảng thời gian này.
                    </div>
                @else
                    <div class="mt-4 space-y-6">
                        @foreach ($supplierTypeSummaries as $typeSummary)
                            <div class="overflow-hidden rounded-3xl border border-gray-200">
                                <div class="border-b border-gray-200 bg-gray-50 px-5 py-4">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <h4 class="text-base font-semibold text-gray-900">{{ $typeSummary['label'] }}</h4>
                                            <p class="text-sm text-gray-500">{{ $typeSummary['supplier_count'] }} NCC, {{ $typeSummary['total_sales'] }} hóa đơn, {{ $typeSummary['items_count'] }} sản phẩm</p>
                                        </div>
                                        <div class="text-right text-sm text-gray-600">
                                            <div>Tổng doanh thu: <span class="font-semibold text-gray-900">{{ number_format($typeSummary['total_revenue'], 0, ',', '.') }} đ</span></div>
                                            <div>Tiền mặt: {{ number_format($typeSummary['cash_revenue'], 0, ',', '.') }} đ</div>
                                            <div>Chuyển khoản: {{ number_format($typeSummary['transfer_revenue'], 0, ',', '.') }} đ</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                                        <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
                                            <tr>
                                                <th class="px-5 py-3">Nhà cung cấp</th>
                                                <th class="px-5 py-3">Số hóa đơn</th>
                                                <th class="px-5 py-3">Số lượng bán</th>
                                                <th class="px-5 py-3">Tiền mặt</th>
                                                <th class="px-5 py-3">Chuyển khoản</th>
                                                <th class="px-5 py-3">Tổng doanh thu</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach ($typeSummary['suppliers'] as $supplierSummary)
                                                <tr>
                                                    <td class="px-5 py-3">
                                                        <div class="font-medium text-gray-900">{{ $supplierSummary['name'] }}</div>
                                                        <div class="text-xs text-gray-500">#{{ ltrim($supplierSummary['public_id'], '0') !== '' ? ltrim($supplierSummary['public_id'], '0') : $supplierSummary['public_id'] }}</div>
                                                    </td>
                                                    <td class="px-5 py-3 text-gray-600">{{ $supplierSummary['total_sales'] }}</td>
                                                    <td class="px-5 py-3 text-gray-600">{{ $supplierSummary['items_count'] }}</td>
                                                    <td class="px-5 py-3 text-gray-600">{{ number_format($supplierSummary['cash_revenue'], 0, ',', '.') }} đ</td>
                                                    <td class="px-5 py-3 text-gray-600">{{ number_format($supplierSummary['transfer_revenue'], 0, ',', '.') }} đ</td>
                                                    <td class="px-5 py-3 font-semibold text-gray-900">{{ number_format($supplierSummary['total_revenue'], 0, ',', '.') }} đ</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
