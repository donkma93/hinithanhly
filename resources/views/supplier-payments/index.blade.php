<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">Danh sách công nợ NCC</h2>
            <p class="text-sm text-gray-500">Theo dõi công nợ nhà cung cấp theo từng kỳ thanh toán trong tháng.</p>
        </div>
    </x-slot>

    @php
        $supplierOptions = $suppliers->map(fn ($supplier) => [
            'value' => $supplier->id,
            'label' => '#'.($supplier->public_id_display ?? $supplier->public_id).' - '.$supplier->name,
        ])->all();
        $selectedSupplierId = (string) ($selectedSupplier?->id ?? '');
        $statusOptions = [
            '' => '-- Trạng thái --',
            'paid' => 'Đã thanh toán',
            'unpaid' => 'Chưa thanh toán',
        ];
    @endphp

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-10xl space-y-6 px-3 sm:px-6 lg:px-10">
            @if (session('status'))
                <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-3xl border border-sky-200 bg-sky-100 px-4 py-5 shadow-sm">
                <div class="grid gap-4 text-sm text-slate-800 md:grid-cols-4 md:items-center">
                    <div>
                        <span class="font-semibold">Kỳ:</span>
                        {{ $overview['period'] }}
                    </div>
                    <div class="md:text-center">
                        <span class="font-semibold">Đã thanh toán:</span>
                        {{ number_format((float) $overview['paid_amount'], 0, ',', '.') }} đ
                    </div>
                    <div class="md:text-center">
                        <span class="font-semibold">Chưa thanh toán:</span>
                        {{ number_format((float) $overview['unpaid_amount'], 0, ',', '.') }} đ
                    </div>
                    <div class="md:text-right">
                        <span class="font-semibold">Tổng cộng:</span>
                        {{ number_format((float) $overview['total_amount'], 0, ',', '.') }} đ
                    </div>
                </div>
            </div>

            <div class="rounded-3xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:p-5">
                <form id="supplier-payment-filter" method="GET" action="{{ route('supplier-payments.index') }}" class="flex flex-wrap items-center gap-3">
                    <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                    <input type="month" name="month" value="{{ $selectedMonth }}" class="rounded-xl border-gray-300 px-4 py-3 text-sm focus:border-slate-900 focus:ring-slate-900">
                    <select name="status" class="rounded-xl border-gray-300 px-4 py-3 text-sm focus:border-slate-900 focus:ring-slate-900">
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="min-w-[280px] flex-1">
                        <x-searchable-select
                            name="supplier_id"
                            :options="$supplierOptions"
                            :selected="$selectedSupplierId"
                            placeholder="Chọn NCC..."
                            search-placeholder="Tìm theo mã hoặc tên NCC"
                            empty-text="Không có nhà cung cấp phù hợp"
                        />
                    </div>
                    <button type="submit" class="rounded-xl bg-slate-600 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-700">Tìm kiếm</button>
                    <a href="{{ route('supplier-payments.index', ['month' => now()->format('Y-m')]) }}" class="rounded-xl border border-rose-300 px-5 py-3 text-sm font-semibold text-rose-500 hover:bg-rose-50">Bỏ lọc</a>
                </form>
            </div>

            <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Trạng thái</th>
                                <th class="px-4 py-3">ID NCC</th>
                                <th class="px-4 py-3">Điện thoại</th>
                                <th class="px-4 py-3">Tên NCC</th>
                                <th class="px-4 py-3">Loại NCC</th>
                                <th class="px-4 py-3">Ngân hàng</th>
                                <th class="px-4 py-3">Số TK</th>
                                <th class="px-4 py-3">Kỳ báo cáo</th>
                                <th class="px-4 py-3">Số lượng</th>
                                <th class="px-4 py-3">Số tiền</th>
                                <th class="px-4 py-3 text-right">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($supplierRows as $row)
                                @php($supplier = $row['supplier'])
                                <tr>
                                    <td class="px-4 py-3">
                                        @if ($row['status'] === 'paid')
                                            <span class="font-medium text-emerald-600">Đã thanh toán</span>
                                        @else
                                            <span class="font-medium text-amber-600">Chưa thanh toán</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-900">#{{ $supplier->public_id_display }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $supplier->phone ?: '---' }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $supplier->name }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ \App\Models\Supplier::labelForType($supplier->type) }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $supplier->bank_name ?: '---' }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $supplier->bank_account_number ?: '---' }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $row['period_label'] }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $row['units_sold'] }}</td>
                                    <td class="px-4 py-3 text-gray-900">
                                        {{ number_format((float) ($row['status'] === 'paid' ? $row['paid_amount'] : $row['outstanding_amount']), 0, ',', '.') }} đ
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        @if ($row['status'] === 'paid')
                                            <span class="inline-flex items-center rounded-full bg-emerald-600 px-3 py-1 text-xs font-semibold text-white">Đã TT</span>
                                        @else
                                            <button
                                                data-supplier-id="{{ $supplier->id }}"
                                                type="button"
                                                class="create-supplier-payment-button inline-flex items-center rounded-full bg-amber-500 px-3 py-1 text-xs font-semibold text-white hover:bg-amber-600"
                                            >
                                                Thanh toán
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="px-4 py-8 text-center text-gray-500">Không có dữ liệu công nợ cho kỳ này.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold text-gray-900">Lịch sử thanh toán trong kỳ</h3>
                    <div class="flex items-center gap-2">
                        <form method="GET" action="{{ route('supplier-payments.index') }}" class="flex items-center gap-2">
                            <input type="hidden" name="month" value="{{ $selectedMonth }}">
                            <input type="hidden" name="status" value="{{ $status }}">
                            <input type="hidden" name="supplier_id" value="{{ $selectedSupplierId }}">
                            <x-per-page-select :value="request('per_page', 10)" />
                        </form>
                        <span class="text-sm text-gray-500">{{ $payments->total() }} giao dịch</span>
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="py-3 pr-4">Mã</th>
                                <th class="py-3 pr-4">Nhà cung cấp</th>
                                <th class="py-3 pr-4">Khoảng thời gian</th>
                                <th class="py-3 pr-4">Thanh toán</th>
                                <th class="py-3 pr-4">Ngân hàng</th>
                                <th class="py-3 pr-4">Người xử lý</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($payments as $payment)
                                <tr>
                                    <td class="py-3 pr-4 font-medium text-slate-900">#{{ $payment->public_id_display }}</td>
                                    <td class="py-3 pr-4 text-gray-600">{{ $payment->supplier?->name ?? '---' }}</td>
                                    <td class="py-3 pr-4 text-gray-600">{{ $payment->period_from?->format('d/m/Y') }} - {{ $payment->period_to?->format('d/m/Y') }}</td>
                                    <td class="py-3 pr-4 text-gray-900">{{ number_format((float) $payment->payable_amount, 0, ',', '.') }} đ</td>
                                    <td class="py-3 pr-4 text-gray-600">{{ $payment->bank_name }} · {{ $payment->bank_account_number }}</td>
                                    <td class="py-3 pr-4 text-gray-600">{{ $payment->handledBy?->name ?? '---' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-8 text-center text-gray-500">Chưa có lịch sử thanh toán nào trong kỳ này.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $payments->links() }}</div>
            </div>
        </div>
    </div>

    <div id="supplier-payment-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">QR thanh toán nhà cung cấp</h3>
                    <p id="supplier-payment-modal-subtitle" class="text-sm text-gray-500"></p>
                </div>
                <button id="supplier-payment-modal-close" type="button" class="rounded-full bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-700">Đóng</button>
            </div>
            <div class="mt-4 grid gap-4 md:grid-cols-[220px_1fr]">
                <div class="flex items-center justify-center rounded-2xl border border-gray-200 bg-gray-50 p-4">
                    <img id="supplier-payment-qr" alt="QR thanh toán" class="max-h-52 max-w-full">
                </div>
                <div>
                    <pre id="supplier-payment-payload" class="whitespace-pre-wrap rounded-2xl bg-slate-50 p-4 text-sm text-slate-700 ring-1 ring-slate-200"></pre>
                    <div class="mt-4 flex flex-wrap items-center justify-end gap-3">
                        <button id="supplier-payment-cancel" type="button" class="rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold text-gray-700">Hủy</button>
                        <button id="supplier-payment-confirm" type="button" class="rounded-xl bg-emerald-500 px-4 py-3 text-sm font-semibold text-white">Tôi đã chuyển tiền</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('supplier-payment-modal');
            const modalClose = document.getElementById('supplier-payment-modal-close');
            const modalCancel = document.getElementById('supplier-payment-cancel');
            const modalConfirm = document.getElementById('supplier-payment-confirm');
            const modalSubtitle = document.getElementById('supplier-payment-modal-subtitle');
            const qrImage = document.getElementById('supplier-payment-qr');
            const payloadBox = document.getElementById('supplier-payment-payload');
            const filterForm = document.getElementById('supplier-payment-filter');
            const tokenInput = document.createElement('input');

            tokenInput.type = 'hidden';
            tokenInput.id = 'supplier-payment-token';
            tokenInput.value = '';
            document.body.appendChild(tokenInput);

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            const openModal = () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            const getMonthRange = () => {
                const monthValue = filterForm?.querySelector('[name="month"]')?.value || @json($selectedMonth);

                if (!monthValue || !monthValue.includes('-')) {
                    return null;
                }

                const [year, month] = monthValue.split('-').map(Number);
                const start = new Date(year, month - 1, 1);
                const end = new Date(year, month, 0);
                const format = (date) => {
                    const yyyy = date.getFullYear();
                    const mm = String(date.getMonth() + 1).padStart(2, '0');
                    const dd = String(date.getDate()).padStart(2, '0');

                    return `${yyyy}-${mm}-${dd}`;
                };

                return {
                    from: format(start),
                    to: format(end),
                };
            };

            document.addEventListener('click', async (event) => {
                const button = event.target.closest('.create-supplier-payment-button');

                if (!button) {
                    return;
                }

                const supplierId = Number(button.getAttribute('data-supplier-id'));
                const monthRange = getMonthRange();

                if (!supplierId || !monthRange) {
                    alert('Không xác định được kỳ thanh toán.');
                    return;
                }

                try {
                    const response = await fetch(@json(route('supplier-payments.create-payment')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            supplier_id: supplierId,
                            from: monthRange.from,
                            to: monthRange.to,
                        }),
                    });

                    const result = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        alert(result.message || 'Không thể tạo QR thanh toán.');
                        return;
                    }

                    tokenInput.value = result.payment_token || '';
                    modalSubtitle.textContent = `${result.supplier_name || 'Nhà cung cấp'} - ${Number(result.total || 0).toLocaleString('vi-VN')} đ`;
                    qrImage.src = result.qr_url;
                    payloadBox.textContent = result.payload || '';
                    openModal();
                } catch (error) {
                    alert('Không thể tạo QR thanh toán.');
                }
            });

            modalClose?.addEventListener('click', closeModal);
            modalCancel?.addEventListener('click', closeModal);

            modalConfirm?.addEventListener('click', async () => {
                if (!tokenInput.value) {
                    closeModal();
                    return;
                }

                try {
                    const response = await fetch(@json(route('supplier-payments.confirm')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ payment_token: tokenInput.value }),
                    });

                    const result = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        alert(result.message || 'Không thể ghi nhận thanh toán.');
                        return;
                    }

                    window.location.reload();
                } catch (error) {
                    alert('Không thể ghi nhận thanh toán.');
                }
            });

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });
        });
    </script>
</x-app-layout>
