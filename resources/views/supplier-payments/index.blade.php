<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">Thanh toán nhà cung cấp</h2>
            <p class="text-sm text-gray-500">Tính số tiền thanh toán theo doanh số sản phẩm, áp dụng chiết khấu theo loại nhà cung cấp và tạo QR chuyển khoản.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                <div class="space-y-6">
                    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Tạo thanh toán</h3>
                                <p class="text-sm text-gray-500">Chọn nhà cung cấp và khoảng thời gian để tính tiền cần thanh toán.</p>
                            </div>
                            <form method="GET" action="{{ route('supplier-payments.index') }}" class="flex flex-wrap items-center gap-2">
                                <input type="hidden" name="supplier_id" value="{{ request('supplier_id', $selectedSupplier?->id) }}">
                                <input type="hidden" name="from" value="{{ request('from', $startDate->format('Y-m-d')) }}">
                                <input type="hidden" name="to" value="{{ request('to', $endDate->format('Y-m-d')) }}">
                                <x-per-page-select :value="request('per_page', 10)" />
                                <button class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white">Lọc</button>
                            </form>
                        </div>

                        <div class="mt-5 grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nhà cung cấp</label>
                                <form id="supplier-payment-filter" method="GET" action="{{ route('supplier-payments.index') }}" class="mt-1 space-y-4 rounded-2xl border border-gray-200 bg-gray-50 p-4">
                                    <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                                    <div>
                                        <select name="supplier_id" class="w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                                            <option value="">-- Chọn nhà cung cấp --</option>
                                            @foreach ($suppliers as $supplier)
                                                <option value="{{ $supplier->id }}" @selected((string) request('supplier_id', $selectedSupplier?->id) === (string) $supplier->id)>
                                                    #{{ $supplier->public_id_display }} - {{ $supplier->name }} ({{ $supplierDiscountRates[$supplier->type] ?? 0 }}%)
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Từ ngày</label>
                                            <input type="date" name="from" value="{{ request('from', $startDate->format('Y-m-d')) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Đến ngày</label>
                                            <input type="date" name="to" value="{{ request('to', $endDate->format('Y-m-d')) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900">
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
                                @if ($selectedSupplier && $summary)
                                    <div class="space-y-3 text-sm text-slate-700">
                                        <div class="flex items-center justify-between"><span>Nhà cung cấp</span><strong>{{ $selectedSupplier->name }}</strong></div>
                                        <div class="flex items-center justify-between"><span>Loại</span><strong>{{ \App\Models\Supplier::TYPES[$selectedSupplier->type] ?? $selectedSupplier->type }}</strong></div>
                                        <div class="flex items-center justify-between"><span>Tỷ lệ chiết khấu</span><strong>{{ $summary['discount_rate'] }}%</strong></div>
                                        <div class="flex items-center justify-between"><span>Doanh số gốc</span><strong>{{ number_format((float) $summary['gross_amount'], 0, ',', '.') }} đ</strong></div>
                                        <div class="flex items-center justify-between"><span>Chiết khấu</span><strong>- {{ number_format((float) $summary['discount_amount'], 0, ',', '.') }} đ</strong></div>
                                        <div class="flex items-center justify-between text-base font-semibold text-slate-950"><span>Thực thanh toán</span><strong>{{ number_format((float) $summary['payable_amount'], 0, ',', '.') }} đ</strong></div>
                                        <div class="flex items-center justify-between"><span>Tài khoản</span><strong>{{ $selectedSupplier->bank_account_number ?: 'Chưa có' }}</strong></div>
                                    </div>
                                @else
                                    <p class="text-sm text-slate-500">Chọn một nhà cung cấp để xem số tiền cần thanh toán.</p>
                                @endif
                            </div>
                        </div>

                        <div class="mt-5 flex items-center justify-end gap-3">
                            <button id="create-supplier-payment-button" type="button" class="rounded-xl bg-emerald-500 px-5 py-3 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:bg-emerald-300" @disabled(! $selectedSupplier || ! $summary || (float) $summary['gross_amount'] <= 0)>
                                Tạo QR thanh toán
                            </button>
                        </div>
                    </div>

                    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h3 class="text-lg font-semibold text-gray-900">Lịch sử thanh toán</h3>
                            <span class="text-sm text-gray-500">{{ $payments->total() }} giao dịch</span>
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
                                        <tr><td colspan="6" class="py-8 text-center text-gray-500">Chưa có lịch sử thanh toán nào.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">{{ $payments->links() }}</div>
                    </div>
                </div>

                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Thông tin nhà cung cấp</h3>
                    @if ($selectedSupplier)
                        <div class="mt-4 space-y-3 text-sm text-gray-600">
                            <div class="flex items-center justify-between"><span>Mã</span><strong class="text-gray-900">#{{ $selectedSupplier->public_id_display }}</strong></div>
                            <div class="flex items-center justify-between"><span>Tên</span><strong class="text-gray-900">{{ $selectedSupplier->name }}</strong></div>
                            <div class="flex items-center justify-between"><span>Loại</span><strong class="text-gray-900">{{ \App\Models\Supplier::TYPES[$selectedSupplier->type] ?? $selectedSupplier->type }}</strong></div>
                            <div class="flex items-center justify-between"><span>Chiết khấu</span><strong class="text-gray-900">{{ $supplierDiscountRates[$selectedSupplier->type] ?? 0 }}%</strong></div>
                            <div class="flex items-center justify-between"><span>Ngân hàng</span><strong class="text-gray-900">{{ $selectedSupplier->bank_name ?: '---' }}</strong></div>
                            <div class="flex items-center justify-between"><span>Số tài khoản</span><strong class="text-gray-900">{{ $selectedSupplier->bank_account_number ?: '---' }}</strong></div>
                            <div class="flex items-center justify-between"><span>Chủ tài khoản</span><strong class="text-gray-900">{{ $selectedSupplier->bank_account_name ?: '---' }}</strong></div>
                            <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Khoảng thanh toán</p>
                                <p class="mt-1 text-sm font-semibold text-slate-950">{{ $startDate->format('d/m/Y') }} - {{ $endDate->format('d/m/Y') }}</p>
                                <p class="mt-2 text-sm text-slate-600">Dùng doanh số sản phẩm của nhà cung cấp trong khoảng này để tạo QR thanh toán.</p>
                            </div>
                        </div>
                    @else
                        <p class="mt-4 text-sm text-gray-500">Chưa chọn nhà cung cấp.</p>
                    @endif
                </div>
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
            const createButton = document.getElementById('create-supplier-payment-button');
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

            const getFilterPayload = () => {
                const data = new FormData(filterForm);

                return {
                    supplier_id: data.get('supplier_id'),
                    from: data.get('from'),
                    to: data.get('to'),
                };
            };

            createButton?.addEventListener('click', async () => {
                const payload = getFilterPayload();

                try {
                    const response = await fetch(@json(route('supplier-payments.create-payment')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify(payload),
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