@php
    $salesBranches = $salesBranches ?? [];
    $isMultiBranch = $isMultiBranch ?? (count($salesBranches) > 1);
    $currentBranchKey = $currentBranchKey ?? 'cs1';
    $canManageSettings = $canManageSettings ?? (auth()->user()?->can('settings.manage') ?? false);
    $isAuthenticated = auth()->check();
@endphp

<div class="mx-auto max-w-10xl px-3 py-4 sm:px-6 lg:px-10">
    <header class="flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-white px-4 py-3 shadow-sm ring-1 ring-slate-200">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">
                Màn hình bán hàng{{ $isMultiBranch ? ' chung tất cả cơ sở' : '' }}
            </p>
            <h1 class="text-xl font-semibold">Quét QR để thêm sản phẩm</h1>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @if($isAuthenticated)
                @if($canManageSettings)
                    <a href="{{ route('settings.payment.edit') }}" class="rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-sm font-semibold text-sky-700 hover:bg-sky-100">
                        Cài đặt ngân hàng
                    </a>
                @endif
                <a href="{{ route('dashboard') }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Dashboard</a>
                <div class="text-sm text-slate-700">Người bán: <strong>{{ auth()->user()->name }}</strong></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Đăng xuất</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
                    Đăng nhập
                </a>
            @endif
        </div>
    </header>

    <main class="mt-4 grid gap-4 lg:grid-cols-[1.15fr_0.85fr]">
        <section class="space-y-4">
            <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                @if($isMultiBranch)
                    <div class="mb-4 flex flex-wrap items-center gap-2 border-b border-slate-100 pb-3">
                        <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Cơ sở quét mã:</span>
                        <button
                            type="button"
                            data-branch-filter="all"
                            class="branch-filter-btn rounded-full px-3.5 py-1.5 text-xs font-semibold transition"
                        >
                            Tất cả cơ sở (Tự nhận diện)
                        </button>
                        @foreach($salesBranches as $branch)
                            <button
                                type="button"
                                data-branch-filter="{{ $branch['key'] }}"
                                class="branch-filter-btn rounded-full px-3.5 py-1.5 text-xs font-semibold transition"
                            >
                                {{ $branch['name'] }}
                                @if(!($branch['bank_configured'] ?? false))
                                    <span class="ml-1 text-[10px] opacity-75">(chưa có TK)</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif

                <label for="scanner-input" class="block text-sm font-medium text-slate-700">
                    Ô quét hoặc nhập đúng mã sản phẩm{{ $isMultiBranch ? ' (hỗ trợ mã cơ sở như cs2-15)' : '' }}
                </label>
                <div class="mt-2 flex gap-2">
                    <div class="relative flex-1">
                        <input id="scanner-input" type="text" inputmode="text" autocomplete="off" placeholder="Quét mã hoặc nhập đúng mã"
                        class="h-12 w-full rounded-xl border-slate-300 px-4 text-lg outline-none ring-0 focus:border-slate-900 focus:ring-1 focus:ring-slate-900" />
                        <div id="suggestions-panel" class="absolute left-0 right-0 top-full z-20 mt-2 hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl ring-1 ring-slate-200"></div>
                    </div>
                    <button id="clear-scan-button" type="button" class="rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700">
                        Xóa
                    </button>
                </div>
                <p id="status-box" class="mt-3 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600 ring-1 ring-slate-200">
                    Sẵn sàng nhận đúng mã sản phẩm.
                </p>
            </div>

            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                    <h2 class="font-semibold">Sản phẩm vừa quét</h2>
                    <span id="cart-state" class="text-sm text-slate-500">Chờ quét</span>
                </div>
                <div id="last-product-empty" class="px-4 py-8 text-sm text-slate-500">
                    Chưa có sản phẩm nào.
                </div>
                <div id="last-product-box" class="hidden px-4 py-4">
                    <div class="flex items-start justify-between gap-4 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="flex h-14 w-14 flex-none items-center justify-center overflow-hidden rounded-xl bg-white ring-1 ring-slate-200">
                                <img id="preview-image" alt="Ảnh sản phẩm" class="hidden h-full w-full object-cover">
                                <span id="preview-image-fallback" class="text-[10px] font-semibold uppercase text-slate-400">No ảnh</span>
                            </div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p id="preview-name" class="truncate text-base font-semibold">--</p>
                                    <span id="preview-branch" class="hidden rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-semibold text-sky-800"></span>
                                </div>
                                <p id="preview-code" class="mt-1 text-sm text-slate-500">--</p>
                            </div>
                        </div>
                        <p id="preview-price" class="text-lg font-semibold text-slate-900">--</p>
                    </div>
                </div>
            </div>
        </section>

        <aside class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                <h2 class="font-semibold">Hóa đơn hiện tại</h2>
                <button id="reset-cart-button" type="button" class="text-sm font-semibold text-slate-600 hover:text-slate-900">Làm mới</button>
            </div>

            <div id="cart-empty" class="px-4 py-8 text-sm text-slate-500">
                Chưa quét sản phẩm nào.
            </div>

            <div id="cart-items" class="hidden divide-y divide-slate-200"></div>

            <div class="border-t border-slate-200 px-4 py-4">
                <div class="flex items-center justify-between text-sm text-slate-600">
                    <span>Số mặt hàng</span>
                    <strong id="items-count-inline">0</strong>
                </div>

                <div id="branch-subtotals-box" class="mt-2 hidden space-y-1 rounded-xl bg-slate-50 p-3 text-xs text-slate-600 ring-1 ring-slate-200"></div>

                <div class="mt-2 flex items-center justify-between text-base text-slate-800">
                    <span class="font-medium">Tổng thanh toán</span>
                    <strong id="subtotal-inline" class="text-lg text-slate-950">0 ₫</strong>
                </div>

                <div class="mt-4">
                    <label for="payment-method-select" class="block text-sm font-medium text-slate-700">Phương thức thanh toán</label>
                    <select id="payment-method-select" class="mt-2 w-full rounded-xl border-slate-300 px-4 py-3 text-sm focus:border-slate-900 focus:ring-slate-900">
                        <option value="cash">Tiền mặt</option>
                        <option value="transfer">Chuyển khoản (Mã QR)</option>
                    </select>
                </div>

                <div id="transfer-qr-section" class="mt-4 hidden space-y-3">
                    <div id="transfer-qr-loading" class="hidden rounded-2xl border border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-600">
                        Đang tạo mã QR thanh toán...
                    </div>
                    <div id="transfer-qr-error" class="hidden rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800"></div>
                    <div id="transfer-qr-cards" class="space-y-3"></div>
                </div>

                <button id="checkout-button" type="button" class="mt-4 w-full rounded-xl bg-emerald-500 px-4 py-3 font-semibold text-white transition hover:bg-emerald-600 disabled:cursor-not-allowed disabled:opacity-60">
                    Hoàn tất bán hàng
                </button>
            </div>
        </aside>
    </main>
</div>

<div id="qr-zoom-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-4">
    <div class="w-full max-w-md rounded-3xl bg-white p-6 text-center shadow-2xl">
        <div class="flex items-start justify-between gap-4 text-left">
            <div>
                <p id="qr-zoom-branch" class="text-xs font-bold uppercase tracking-[0.2em] text-sky-600"></p>
                <h3 class="mt-1 text-lg font-bold text-slate-950">Quét mã QR để thanh toán</h3>
            </div>
            <button id="qr-zoom-close" type="button" class="rounded-full bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-200">
                Đóng
            </button>
        </div>
        <div class="mt-4 flex justify-center rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <img id="qr-zoom-image" alt="QR thanh toán phóng to" class="max-h-80 w-auto rounded-xl">
        </div>
        <p id="qr-zoom-amount" class="mt-4 text-2xl font-black text-emerald-600"></p>
        <div id="qr-zoom-meta" class="mt-2 space-y-1 text-sm text-slate-600"></div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const baseUrl = @json(url('/ban-hang/products'));
        const searchUrl = @json(url('/ban-hang/products/search'));
        const createPaymentUrl = @json(url('/ban-hang/create-payment'));
        const checkoutUrl = @json(url('/ban-hang/checkout'));
        const settingsUrl = @json($canManageSettings ? route('settings.payment.edit') : null);
        const loginUrl = @json(route('login'));
        const isAuthenticated = @json($isAuthenticated);
        const isMultiBranch = @json($isMultiBranch);
        const currentBranchKey = @json($currentBranchKey);
        const configuredBranches = @json($salesBranches);

        const scannerInput = document.getElementById('scanner-input');
        const clearScanButton = document.getElementById('clear-scan-button');
        const suggestionsPanel = document.getElementById('suggestions-panel');
        const resetCartButton = document.getElementById('reset-cart-button');
        const checkoutButton = document.getElementById('checkout-button');
        const paymentMethodSelect = document.getElementById('payment-method-select');
        const statusBox = document.getElementById('status-box');
        const cartItemsContainer = document.getElementById('cart-items');
        const cartEmpty = document.getElementById('cart-empty');
        const lastProductEmpty = document.getElementById('last-product-empty');
        const lastProductBox = document.getElementById('last-product-box');
        const previewName = document.getElementById('preview-name');
        const previewBranch = document.getElementById('preview-branch');
        const previewCode = document.getElementById('preview-code');
        const previewPrice = document.getElementById('preview-price');
        const previewImage = document.getElementById('preview-image');
        const previewImageFallback = document.getElementById('preview-image-fallback');
        const itemsCountInline = document.getElementById('items-count-inline');
        const branchSubtotalsBox = document.getElementById('branch-subtotals-box');
        const subtotalInline = document.getElementById('subtotal-inline');
        const cartState = document.getElementById('cart-state');
        const branchFilterButtons = document.querySelectorAll('.branch-filter-btn');

        const transferQrSection = document.getElementById('transfer-qr-section');
        const transferQrLoading = document.getElementById('transfer-qr-loading');
        const transferQrError = document.getElementById('transfer-qr-error');
        const transferQrCards = document.getElementById('transfer-qr-cards');

        const qrZoomModal = document.getElementById('qr-zoom-modal');
        const qrZoomClose = document.getElementById('qr-zoom-close');
        const qrZoomBranch = document.getElementById('qr-zoom-branch');
        const qrZoomImage = document.getElementById('qr-zoom-image');
        const qrZoomAmount = document.getElementById('qr-zoom-amount');
        const qrZoomMeta = document.getElementById('qr-zoom-meta');

        const moneyFormatter = new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 });
        const cart = new Map();
        const validBranchKeys = new Set(configuredBranches.map((b) => b.key));
        const storageBranchKey = 'sales.selectedBranch';

        let selectedBranch = 'all';
        if (isMultiBranch) {
            const savedBranch = window.localStorage.getItem(storageBranchKey);
            if (savedBranch && (savedBranch === 'all' || validBranchKeys.has(savedBranch))) {
                selectedBranch = savedBranch;
            }
        } else {
            selectedBranch = currentBranchKey;
        }

        let suggestionAbortController = null;
        let suggestionTimer = null;
        let qrAbortController = null;
        let qrDebounceTimer = null;
        let activePaymentToken = null;
        let activePaymentSignature = null;
        let latestPayments = [];

        const escapeHtml = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');

        const focusScanner = () => window.requestAnimationFrame(() => scannerInput.focus());

        const normalizeSearchTerm = (value) => String(value || '').trim();

        const looksLikeProductCode = (value) => {
            const clean = normalizeSearchTerm(value);
            if (/^[0-9\-]+$/.test(clean)) {
                return true;
            }

            const dashIdx = clean.indexOf('-');
            if (dashIdx > 0) {
                const prefix = clean.slice(0, dashIdx).toLowerCase();
                const rest = clean.slice(dashIdx + 1);
                return validBranchKeys.has(prefix) && /^[0-9\-]+$/.test(rest);
            }

            return false;
        };

        const getCartKey = (item) => `${item.branch_key || currentBranchKey}:${Number(item.id)}`;

        const getRemainingQuantity = (item) => {
            const stockQuantity = Number(item.quantity || 0);
            const cartQuantity = Number(cart.get(getCartKey(item))?.cart_quantity || 0);

            return Math.max(stockQuantity - cartQuantity, 0);
        };

        const getBranchBadgeClass = (branchKey) => {
            const idx = configuredBranches.findIndex((b) => b.key === branchKey);
            if (idx === 1) {
                return 'bg-violet-100 text-violet-800';
            }
            if (idx >= 2) {
                return 'bg-emerald-100 text-emerald-800';
            }

            return 'bg-sky-100 text-sky-800';
        };

        const updateBranchFilterUi = () => {
            branchFilterButtons.forEach((btn) => {
                const key = btn.getAttribute('data-branch-filter');
                const isActive = key === selectedBranch;
                btn.className = 'branch-filter-btn rounded-full px-3.5 py-1.5 text-xs font-semibold transition ' + (
                    isActive
                        ? 'bg-slate-900 text-white shadow-sm'
                        : 'border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100'
                );
            });
        };

        branchFilterButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                selectedBranch = btn.getAttribute('data-branch-filter') || 'all';
                window.localStorage.setItem(storageBranchKey, selectedBranch);
                updateBranchFilterUi();
                hideSuggestions();
                const label = btn.textContent?.trim() || selectedBranch;
                setStatus(`Đang quét mã theo: ${label}.`, 'info');
                focusScanner();
            });
        });

        updateBranchFilterUi();

        const hideSuggestions = () => {
            suggestionsPanel.classList.add('hidden');
            suggestionsPanel.innerHTML = '';
        };

        const renderSuggestions = (items, query, allowZeroRemaining = false) => {
            const cleanQuery = normalizeSearchTerm(query);
            const visibleItems = allowZeroRemaining
                ? items
                : items.filter((item) => getRemainingQuantity(item) > 0);

            if (!cleanQuery || !visibleItems.length) {
                hideSuggestions();
                return;
            }

            suggestionsPanel.innerHTML = visibleItems.map((item) => {
                const compositeKey = getCartKey(item);
                const branchBadge = isMultiBranch && item.branch_name
                    ? `<span class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-[11px] font-bold ${getBranchBadgeClass(item.branch_key)}">${escapeHtml(item.branch_name)}</span>`
                    : '';

                return `
                    <button type="button" data-cart-key="${escapeHtml(compositeKey)}" class="flex w-full items-center gap-3 border-b border-slate-100 px-4 py-3 text-left last:border-b-0 hover:bg-slate-50">
                        <div class="flex h-12 w-12 flex-none items-center justify-center overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200">
                            ${item.image_url ? `<img src="${escapeHtml(item.image_url)}" alt="${escapeHtml(item.name)}" class="h-full w-full object-cover">` : '<span class="text-[10px] font-semibold uppercase text-slate-400">No ảnh</span>'}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex min-w-0 items-center gap-2">
                                    ${branchBadge}
                                    <p class="truncate font-semibold text-slate-900">${escapeHtml(item.name)}</p>
                                </div>
                                <span class="shrink-0 text-sm font-semibold text-slate-900">${escapeHtml(item.sale_price_text)}</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">Mã: #${escapeHtml(item.public_id_display)} · Còn có thể bán: ${getRemainingQuantity(item)}/${Number(item.quantity || 0)}</p>
                            <p class="mt-1 text-xs text-slate-400">${item.supplier ? `${escapeHtml(item.supplier.name)}${item.supplier.public_id_display ? ' · #' + escapeHtml(item.supplier.public_id_display) : ''}` : ''}${item.category ? `${item.supplier ? ' · ' : ''}${escapeHtml(item.category.name)}${item.category.public_id_display ? ' · #' + escapeHtml(item.category.public_id_display) : ''}` : ''}</p>
                        </div>
                    </button>
                `;
            }).join('');

            suggestionsPanel.classList.remove('hidden');

            suggestionsPanel.querySelectorAll('[data-cart-key]').forEach((button) => {
                button.addEventListener('click', () => {
                    const key = button.getAttribute('data-cart-key');
                    const product = visibleItems.find((entry) => getCartKey(entry) === key);

                    if (product) {
                        addProduct(product);
                        scannerInput.value = '';
                        hideSuggestions();
                    }
                });
            });
        };

        const fetchSuggestions = async (query) => {
            const cleanQuery = normalizeSearchTerm(query);

            if (!cleanQuery) {
                hideSuggestions();
                return;
            }

            if (suggestionAbortController) {
                suggestionAbortController.abort();
            }

            suggestionAbortController = new AbortController();

            try {
                const params = new URLSearchParams({ query: cleanQuery });
                if (isMultiBranch && selectedBranch) {
                    params.set('branch', selectedBranch);
                }

                const response = await fetch(`${searchUrl}?${params.toString()}`, {
                    headers: { Accept: 'application/json' },
                    signal: suggestionAbortController.signal,
                });

                if (!response.ok) {
                    hideSuggestions();
                    return;
                }

                const payload = await response.json().catch(() => ({}));
                renderSuggestions(payload.items || [], cleanQuery);
            } catch (error) {
                if (error.name === 'AbortError') {
                    return;
                }

                hideSuggestions();
            }
        };

        const setStatus = (message, tone = 'info') => {
            statusBox.textContent = message;
            statusBox.className = 'mt-3 rounded-xl px-4 py-3 text-sm ring-1';

            if (tone === 'success') {
                statusBox.classList.add('bg-emerald-50', 'text-emerald-700', 'ring-emerald-200');
            } else if (tone === 'error') {
                statusBox.classList.add('bg-rose-50', 'text-rose-700', 'ring-rose-200');
            } else if (tone === 'warning') {
                statusBox.classList.add('bg-amber-50', 'text-amber-700', 'ring-amber-200');
            } else {
                statusBox.classList.add('bg-slate-50', 'text-slate-600', 'ring-slate-200');
            }
        };

        const formatMoney = (value) => `${moneyFormatter.format(Number(value ?? 0))} ₫`;

        const buildCheckoutItems = () => Array.from(cart.values())
            .map((item) => ({
                branch_key: item.branch_key || currentBranchKey,
                id: Number(item.id),
                quantity: Number(item.cart_quantity || 1),
            }))
            .sort((a, b) => `${a.branch_key}:${a.id}`.localeCompare(`${b.branch_key}:${b.id}`));

        const getCartSignature = () => JSON.stringify(buildCheckoutItems());

        const resetTransferQrState = () => {
            window.clearTimeout(qrDebounceTimer);
            if (qrAbortController) {
                qrAbortController.abort();
                qrAbortController = null;
            }
            activePaymentToken = null;
            activePaymentSignature = null;
            latestPayments = [];
            transferQrLoading.classList.add('hidden');
            transferQrError.classList.add('hidden');
            transferQrError.innerHTML = '';
            transferQrCards.innerHTML = '';
            transferQrSection.classList.add('hidden');
        };

        const openQrZoom = (payment) => {
            qrZoomBranch.textContent = isMultiBranch ? (payment.branch_name || '') : 'Thanh toán chuyển khoản';
            qrZoomImage.src = payment.qr_url;
            qrZoomAmount.textContent = payment.amount_text || formatMoney(payment.amount);
            qrZoomMeta.innerHTML = `
                <p><strong>${escapeHtml(payment.bank_name)}</strong> · STK: <strong>${escapeHtml(payment.account_number)}</strong></p>
                <p>Chủ TK: <strong>${escapeHtml(payment.account_name)}</strong></p>
                <p class="text-xs text-slate-500">Nội dung: ${escapeHtml(payment.payment_content)}</p>
            `;
            qrZoomModal.classList.remove('hidden');
            qrZoomModal.classList.add('flex');
        };

        const closeQrZoom = () => {
            qrZoomModal.classList.add('hidden');
            qrZoomModal.classList.remove('flex');
        };

        qrZoomClose?.addEventListener('click', closeQrZoom);
        qrZoomModal?.addEventListener('click', (event) => {
            if (event.target === qrZoomModal) {
                closeQrZoom();
            }
        });

        const renderTransferQrCards = (payments) => {
            latestPayments = payments;
            transferQrLoading.classList.add('hidden');
            transferQrError.classList.add('hidden');

            transferQrCards.innerHTML = payments.map((payment, index) => `
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50/40 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            ${isMultiBranch ? `<span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold ${getBranchBadgeClass(payment.branch_key)}">${escapeHtml(payment.branch_name)}</span>` : '<span class="text-xs font-bold uppercase tracking-[0.15em] text-emerald-700">VietQR thanh toán</span>'}
                        </div>
                        <span class="text-base font-black text-emerald-700">${escapeHtml(payment.amount_text || formatMoney(payment.amount))}</span>
                    </div>

                    <div class="mt-3 grid items-center gap-3 sm:grid-cols-[160px_1fr]">
                        <div class="flex flex-col items-center rounded-xl border border-slate-200 bg-white p-2">
                            <img src="${escapeHtml(payment.qr_url)}" alt="QR thanh toán ${escapeHtml(payment.branch_name || '')}" class="h-40 w-40 object-contain">
                            <button type="button" data-zoom-index="${index}" class="mt-1.5 text-xs font-semibold text-sky-700 hover:underline">
                                Phóng to QR
                            </button>
                        </div>
                        <div class="space-y-1 text-xs text-slate-700">
                            <p><span class="text-slate-500">Ngân hàng:</span> <strong>${escapeHtml(payment.bank_name)}</strong></p>
                            <p><span class="text-slate-500">Số TK:</span> <strong class="font-mono text-sm text-slate-950">${escapeHtml(payment.account_number)}</strong></p>
                            <p><span class="text-slate-500">Chủ TK:</span> <strong>${escapeHtml(payment.account_name)}</strong></p>
                            <p><span class="text-slate-500">Nội dung:</span> <strong class="font-mono text-slate-900">${escapeHtml(payment.payment_content)}</strong></p>
                        </div>
                    </div>
                </div>
            `).join('');

            transferQrCards.querySelectorAll('[data-zoom-index]').forEach((button) => {
                button.addEventListener('click', () => {
                    const idx = Number(button.getAttribute('data-zoom-index'));
                    if (latestPayments[idx]) {
                        openQrZoom(latestPayments[idx]);
                    }
                });
            });
        };

        const syncTransferQr = (immediate = false) => {
            const paymentMethod = paymentMethodSelect?.value || 'cash';
            const items = buildCheckoutItems();
            const subtotal = Array.from(cart.values()).reduce(
                (sum, item) => sum + (Number(item.sale_price || 0) * Number(item.cart_quantity || 1)),
                0
            );

            if (paymentMethod !== 'transfer' || !items.length || subtotal <= 0) {
                resetTransferQrState();
                return;
            }

            transferQrSection.classList.remove('hidden');

            if (!isAuthenticated) {
                transferQrLoading.classList.add('hidden');
                transferQrCards.innerHTML = '';
                transferQrError.classList.remove('hidden');
                transferQrError.innerHTML = `Vui lòng <a href="${escapeHtml(loginUrl)}" class="font-bold underline">đăng nhập</a> để hệ thống tạo mã QR thanh toán và chốt hóa đơn.`;
                return;
            }

            const signature = getCartSignature();
            if (activePaymentToken && activePaymentSignature === signature && latestPayments.length > 0) {
                return;
            }

            window.clearTimeout(qrDebounceTimer);
            const runFetch = async () => {
                if (qrAbortController) {
                    qrAbortController.abort();
                }

                qrAbortController = new AbortController();
                activePaymentToken = null;
                activePaymentSignature = null;
                transferQrError.classList.add('hidden');
                transferQrCards.innerHTML = '';
                transferQrLoading.classList.remove('hidden');

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    const response = await fetch(createPaymentUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ items }),
                        signal: qrAbortController.signal,
                    });

                    const payload = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        transferQrLoading.classList.add('hidden');
                        transferQrCards.innerHTML = '';
                        transferQrError.classList.remove('hidden');
                        const settingsLinkHtml = settingsUrl
                            ? ` <a href="${escapeHtml(settingsUrl)}" class="font-bold underline">Đi tới Cài đặt ngân hàng</a>`
                            : '';
                        transferQrError.innerHTML = `${escapeHtml(payload.message || 'Không thể tạo mã QR thanh toán.')}${settingsLinkHtml}`;
                        return;
                    }

                    activePaymentToken = payload.payment_token || null;
                    activePaymentSignature = signature;

                    const payments = Array.isArray(payload.payments) && payload.payments.length > 0
                        ? payload.payments
                        : [{
                            branch_key: currentBranchKey,
                            branch_name: '',
                            bank_name: payload.bank_name || '',
                            account_number: payload.account_number || '',
                            account_name: payload.account_name || '',
                            amount: payload.total || subtotal,
                            amount_text: formatMoney(payload.total || subtotal),
                            payment_content: payload.payment_content || '',
                            qr_url: payload.qr_url,
                        }];

                    renderTransferQrCards(payments);
                } catch (error) {
                    if (error.name === 'AbortError') {
                        return;
                    }
                    transferQrLoading.classList.add('hidden');
                    transferQrError.classList.remove('hidden');
                    transferQrError.textContent = 'Không thể kết nối máy chủ để tạo mã QR thanh toán.';
                }
            };

            if (immediate) {
                runFetch();
            } else {
                qrDebounceTimer = window.setTimeout(runFetch, 220);
            }
        };

        const completeCheckout = async (paymentMethod, paymentToken = null) => {
            const items = buildCheckoutItems();
            checkoutButton.disabled = true;

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                const checkoutResp = await fetch(checkoutUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({ items, payment_method: paymentMethod, payment_token: paymentToken }),
                });

                const payload = await checkoutResp.json().catch(() => ({}));

                if (!checkoutResp.ok) {
                    setStatus(payload.message || 'Lỗi khi chốt hoá đơn.', 'error');
                    focusScanner();
                    return false;
                }

                cart.clear();
                closeQrZoom();
                resetTransferQrState();
                renderCart();
                lastProductEmpty.classList.remove('hidden');
                lastProductBox.classList.add('hidden');
                hideSuggestions();
                setStatus(payload.message || 'Chốt hoá đơn thành công.', 'success');
                focusScanner();
                return true;
            } catch (err) {
                setStatus('Lỗi khi chốt hoá đơn.', 'error');
                focusScanner();
                return false;
            } finally {
                checkoutButton.disabled = false;
            }
        };

        const renderLastProduct = (product) => {
            lastProductEmpty.classList.add('hidden');
            lastProductBox.classList.remove('hidden');
            previewName.textContent = product.name || '--';
            previewCode.textContent = `Mã: ${product.public_id_display || product.public_id || product.id}`;
            previewPrice.textContent = product.sale_price_text || formatMoney(product.sale_price);

            if (isMultiBranch && product.branch_name) {
                previewBranch.textContent = product.branch_name;
                previewBranch.className = `rounded-full px-2.5 py-0.5 text-xs font-semibold ${getBranchBadgeClass(product.branch_key)}`;
            } else {
                previewBranch.classList.add('hidden');
            }

            if (product.image_url) {
                previewImage.src = product.image_url;
                previewImage.classList.remove('hidden');
                previewImageFallback.classList.add('hidden');
            } else {
                previewImage.removeAttribute('src');
                previewImage.classList.add('hidden');
                previewImageFallback.classList.remove('hidden');
            }
        };

        const renderCart = () => {
            const items = Array.from(cart.values());
            const itemsCount = items.reduce((sum, item) => sum + Number(item.cart_quantity || 1), 0);
            const subtotal = items.reduce((sum, item) => sum + (Number(item.sale_price || 0) * Number(item.cart_quantity || 1)), 0);

            itemsCountInline.textContent = String(itemsCount);
            subtotalInline.textContent = formatMoney(subtotal);
            cartState.textContent = items.length ? 'Đang tính tiền' : 'Chờ quét';

            // Per-branch subtotal summary when multi-branch cart has items
            if (isMultiBranch && items.length > 0) {
                const totalsByBranch = new Map();
                items.forEach((item) => {
                    const key = item.branch_key || currentBranchKey;
                    const prev = totalsByBranch.get(key) || { name: item.branch_name || key, total: 0, qty: 0 };
                    prev.total += Number(item.sale_price || 0) * Number(item.cart_quantity || 1);
                    prev.qty += Number(item.cart_quantity || 1);
                    totalsByBranch.set(key, prev);
                });

                branchSubtotalsBox.innerHTML = Array.from(totalsByBranch.entries()).map(([key, info]) => `
                    <div class="flex items-center justify-between gap-2">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold ${getBranchBadgeClass(key)}">${escapeHtml(info.name)}</span>
                            <span>(${info.qty} SP)</span>
                        </span>
                        <strong class="text-slate-900">${formatMoney(info.total)}</strong>
                    </div>
                `).join('');
                branchSubtotalsBox.classList.remove('hidden');
            } else {
                branchSubtotalsBox.classList.add('hidden');
                branchSubtotalsBox.innerHTML = '';
            }

            if (!items.length) {
                cartEmpty.classList.remove('hidden');
                cartItemsContainer.classList.add('hidden');
                cartItemsContainer.innerHTML = '';
                syncTransferQr(false);
                return;
            }

            cartEmpty.classList.add('hidden');
            cartItemsContainer.classList.remove('hidden');
            cartItemsContainer.innerHTML = items.map((item) => {
                const compositeKey = getCartKey(item);
                const branchBadge = isMultiBranch && item.branch_name
                    ? `<span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold ${getBranchBadgeClass(item.branch_key)}">${escapeHtml(item.branch_name)}</span>`
                    : '';

                return `
                    <div class="flex items-center justify-between gap-3 px-4 py-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="flex h-12 w-12 flex-none items-center justify-center overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200">
                                ${item.image_url ? `<img src="${escapeHtml(item.image_url)}" alt="${escapeHtml(item.name)}" class="h-full w-full object-cover">` : '<span class="text-[10px] font-semibold uppercase text-slate-400">No ảnh</span>'}
                            </div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    ${branchBadge}
                                    <p class="truncate font-medium">${escapeHtml(item.name)}</p>
                                </div>
                                <p class="text-sm text-slate-500">#${escapeHtml(item.public_id_display || item.public_id || item.id)}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <p class="font-semibold">${formatMoney(Number(item.sale_price || 0) * Number(item.cart_quantity || 1))}</p>
                                <p class="text-xs text-slate-500">SL: ${Number(item.cart_quantity || 1)} x ${formatMoney(item.sale_price)}</p>
                            </div>
                            <button type="button" data-remove-key="${escapeHtml(compositeKey)}" class="rounded-lg border border-slate-300 px-3 py-1 text-sm text-slate-600 hover:bg-slate-50">
                                Xóa
                            </button>
                        </div>
                    </div>
                `;
            }).join('');

            cartItemsContainer.querySelectorAll('[data-remove-key]').forEach((button) => {
                button.addEventListener('click', () => {
                    const key = button.getAttribute('data-remove-key');
                    cart.delete(key);
                    renderCart();
                    setStatus('Đã xóa sản phẩm khỏi hóa đơn.', 'warning');
                    focusScanner();
                });
            });

            syncTransferQr(false);
        };

        const addProduct = (product) => {
            const normalizedProduct = {
                ...product,
                branch_key: product.branch_key || currentBranchKey,
                branch_name: product.branch_name || '',
            };
            const compositeKey = getCartKey(normalizedProduct);
            const currentItem = cart.get(compositeKey);
            const stockQuantity = Number(normalizedProduct.quantity || 0);

            if (stockQuantity <= 0) {
                setStatus('Sản phẩm này hiện đã hết hàng.', 'error');
                renderLastProduct(normalizedProduct);
                focusScanner();
                return;
            }

            if (currentItem) {
                const currentQuantity = Number(currentItem.cart_quantity || 1);

                if (currentQuantity >= stockQuantity) {
                    setStatus('Sản phẩm này không còn đủ tồn kho để thêm vào hóa đơn.', 'error');
                    renderLastProduct(normalizedProduct);
                    focusScanner();
                    return;
                }

                cart.set(compositeKey, { ...currentItem, quantity: stockQuantity, cart_quantity: currentQuantity + 1 });
                renderLastProduct(normalizedProduct);
                renderCart();
                const branchSuffix = isMultiBranch && normalizedProduct.branch_name ? ` (${normalizedProduct.branch_name})` : '';
                setStatus(`Đã thêm 1 ${normalizedProduct.name}${branchSuffix}. Số lượng hiện tại: ${currentQuantity + 1}.`, 'success');
                focusScanner();
                return;
            }

            cart.set(compositeKey, { ...normalizedProduct, cart_quantity: 1 });
            renderLastProduct(normalizedProduct);
            renderCart();
            const branchSuffix = isMultiBranch && normalizedProduct.branch_name ? ` (${normalizedProduct.branch_name})` : '';
            setStatus(`Đã thêm ${normalizedProduct.name}${branchSuffix}.`, 'success');
            focusScanner();
        };

        const lookupProduct = async (code) => {
            const cleanCode = String(code || '').trim();

            if (!cleanCode) {
                setStatus('Vui lòng quét mã trước.', 'warning');
                focusScanner();
                return;
            }

            setStatus(`Đang tra mã ${cleanCode}...`);

            try {
                const params = new URLSearchParams();
                if (isMultiBranch && selectedBranch) {
                    params.set('branch', selectedBranch);
                }
                const queryString = params.toString() ? `?${params.toString()}` : '';

                const response = await fetch(`${baseUrl}/${encodeURIComponent(cleanCode)}${queryString}`, {
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) {
                    const error = await response.json().catch(() => ({}));
                    throw new Error(error.message || 'Không tìm thấy sản phẩm.');
                }

                const payload = await response.json();

                if (payload.ambiguous && Array.isArray(payload.matches)) {
                    setStatus(payload.message || 'Mã sản phẩm tồn tại ở nhiều cơ sở. Vui lòng chọn đúng cơ sở bên dưới.', 'warning');
                    renderSuggestions(payload.matches, cleanCode, true);
                    focusScanner();
                    return;
                }

                addProduct(payload);
            } catch (error) {
                setStatus(error.message || 'Quét mã thất bại.', 'error');
                focusScanner();
            }
        };

        scannerInput.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') {
                return;
            }

            event.preventDefault();
            hideSuggestions();

            if (!looksLikeProductCode(scannerInput.value)) {
                setStatus('Vui lòng nhập đúng mã sản phẩm.', 'warning');
                scannerInput.value = '';
                focusScanner();
                return;
            }

            lookupProduct(scannerInput.value);
            scannerInput.value = '';
        });

        scannerInput.addEventListener('input', () => {
            window.clearTimeout(suggestionTimer);

            const cleanValue = normalizeSearchTerm(scannerInput.value);

            if (!cleanValue || !looksLikeProductCode(cleanValue)) {
                hideSuggestions();
                return;
            }

            suggestionTimer = window.setTimeout(() => {
                fetchSuggestions(cleanValue);
            }, 180);
        });

        scannerInput.addEventListener('focus', () => {
            const cleanValue = normalizeSearchTerm(scannerInput.value);

            if (cleanValue && looksLikeProductCode(cleanValue)) {
                fetchSuggestions(cleanValue);
            }
        });

        paymentMethodSelect?.addEventListener('change', () => {
            syncTransferQr(true);
        });

        clearScanButton.addEventListener('click', () => {
            scannerInput.value = '';
            hideSuggestions();
            setStatus('Đã xóa nội dung ô quét.', 'warning');
            focusScanner();
        });

        resetCartButton.addEventListener('click', () => {
            cart.clear();
            closeQrZoom();
            resetTransferQrState();
            renderCart();
            lastProductEmpty.classList.remove('hidden');
            lastProductBox.classList.add('hidden');
            hideSuggestions();
            setStatus('Đã làm mới hóa đơn.', 'warning');
            focusScanner();
        });

        checkoutButton.addEventListener('click', async () => {
            if (!cart.size) {
                setStatus('Chưa có sản phẩm để chốt.', 'warning');
                focusScanner();
                return;
            }

            const paymentMethod = paymentMethodSelect?.value || 'cash';

            if (paymentMethod === 'cash') {
                setStatus('Đang chốt hoá đơn tiền mặt...', 'info');
                await completeCheckout('cash');
                return;
            }

            const tokenToUse = activePaymentSignature === getCartSignature() ? activePaymentToken : null;
            setStatus('Đang chốt hoá đơn chuyển khoản...', 'info');
            await completeCheckout('transfer', tokenToUse);
        });

        scannerInput.addEventListener('blur', () => {
            window.setTimeout(() => {
                const active = document.activeElement;

                if (active && active !== document.body && active !== document.documentElement) {
                    try {
                        if (typeof active.closest === 'function' && active.closest('input, textarea, select, [contenteditable]')) {
                            return;
                        }
                    } catch (e) {
                        // Fall back to refocusing scanner
                    }
                }

                focusScanner();
            }, 0);
        });

        document.addEventListener('pointerdown', (event) => {
            if (event.target instanceof HTMLElement && event.target.closest('#suggestions-panel, #suggestions-panel *, #qr-zoom-modal, #qr-zoom-modal *, input, button, a, textarea, select')) {
                return;
            }

            focusScanner();
        });

        window.addEventListener('focus', focusScanner);
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                focusScanner();
            }
        });

        renderCart();

        if (!document.querySelector('input[autofocus], textarea[autofocus], select[autofocus], [autofocus]')) {
            focusScanner();
        }
    });
</script>
