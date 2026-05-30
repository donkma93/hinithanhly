<div class="mx-auto max-w-6xl px-4 py-4 sm:px-6 lg:px-8">
    <header class="flex items-center justify-between gap-4 rounded-2xl bg-white px-4 py-3 shadow-sm ring-1 ring-slate-200">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Màn hình bán hàng</p>
            <h1 class="text-xl font-semibold">Quét QR để thêm sản phẩm</h1>
        </div>
        <div class="flex items-center gap-3">
            @if(auth()->check())
                <a href="{{ route('dashboard') }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700">Dashboard</a>
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

    <main class="mt-4 grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
        <section class="space-y-4">
            <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                <label for="scanner-input" class="block text-sm font-medium text-slate-700">Ô quét mã hoặc tìm sản phẩm</label>
                <div class="mt-2 flex gap-2">
                    <div class="relative flex-1">
                        <input id="scanner-input" type="text" inputmode="text" autocomplete="off" placeholder="Quét mã hoặc nhập tên sản phẩm"
                        class="h-12 flex-1 rounded-xl border-slate-300 px-4 text-lg outline-none ring-0 focus:border-slate-900 focus:ring-1 focus:ring-slate-900" />
                        <div id="suggestions-panel" class="absolute left-0 right-0 top-full z-20 mt-2 hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl ring-1 ring-slate-200"></div>
                    </div>
                    <button id="clear-scan-button" type="button" class="rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700">
                        Xóa
                    </button>
                </div>
                <p id="status-box" class="mt-3 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600 ring-1 ring-slate-200">
                    Sẵn sàng nhận mã hoặc tên sản phẩm.
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
                                <p id="preview-name" class="truncate text-base font-semibold">--</p>
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
                <button id="reset-cart-button" type="button" class="text-sm font-semibold text-slate-600">Làm mới</button>
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
                <div class="mt-2 flex items-center justify-between text-sm text-slate-600">
                    <span>Tạm tính</span>
                    <strong id="subtotal-inline">0 ₫</strong>
                </div>
                <div class="mt-4">
                    <label for="payment-method-select" class="block text-sm font-medium text-slate-700">Phương thức thanh toán</label>
                    <select id="payment-method-select" class="mt-2 w-full rounded-xl border-slate-300 px-4 py-3 text-sm focus:border-slate-900 focus:ring-slate-900">
                        <option value="cash">Tiền mặt</option>
                        <option value="transfer">Chuyển khoản</option>
                    </select>
                </div>
                <button id="checkout-button" type="button" class="mt-4 w-full rounded-xl bg-emerald-500 px-4 py-3 font-semibold text-white">
                    Hoàn tất bán hàng
                </button>
            </div>
        </aside>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const baseUrl = @json(url('/ban-hang/products'));
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
        const previewCode = document.getElementById('preview-code');
        const previewPrice = document.getElementById('preview-price');
        const previewImage = document.getElementById('preview-image');
        const previewImageFallback = document.getElementById('preview-image-fallback');
        const itemsCountInline = document.getElementById('items-count-inline');
        const subtotalInline = document.getElementById('subtotal-inline');
        const cartState = document.getElementById('cart-state');

        const moneyFormatter = new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 });
        const cart = new Map();
        let suggestionAbortController = null;
        let suggestionTimer = null;

        const focusScanner = () => window.requestAnimationFrame(() => scannerInput.focus());

        const normalizeSearchTerm = (value) => String(value || '').trim();

        const looksLikeProductCode = (value) => /^[0-9\-]+$/.test(normalizeSearchTerm(value));

        const hideSuggestions = () => {
            suggestionsPanel.classList.add('hidden');
            suggestionsPanel.innerHTML = '';
        };

        const renderSuggestions = (items, query) => {
            const cleanQuery = normalizeSearchTerm(query);

            if (!cleanQuery || !items.length) {
                hideSuggestions();
                return;
            }

            suggestionsPanel.innerHTML = items.map((item) => `
                <button type="button" data-product-id="${item.id}" class="flex w-full items-center gap-3 border-b border-slate-100 px-4 py-3 text-left last:border-b-0 hover:bg-slate-50">
                    <div class="flex h-12 w-12 flex-none items-center justify-center overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200">
                        ${item.image_url ? `<img src="${item.image_url}" alt="${item.name}" class="h-full w-full object-cover">` : '<span class="text-[10px] font-semibold uppercase text-slate-400">No ảnh</span>'}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <p class="truncate font-semibold text-slate-900">${item.name}</p>
                            <span class="shrink-0 text-sm font-semibold text-slate-900">${item.sale_price_text}</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">Mã: #${item.public_id_display} · Tồn kho: ${item.quantity}</p>
                        <p class="mt-1 text-xs text-slate-400">${item.supplier ? `${item.supplier.name}${item.supplier.public_id_display ? ' · #' + item.supplier.public_id_display : ''}` : ''}${item.category ? `${item.supplier ? ' · ' : ''}${item.category.name}${item.category.public_id_display ? ' · #' + item.category.public_id_display : ''}` : ''}</p>
                    </div>
                </button>
            `).join('');

            suggestionsPanel.classList.remove('hidden');

            suggestionsPanel.querySelectorAll('[data-product-id]').forEach((button) => {
                button.addEventListener('click', () => {
                    const id = Number(button.getAttribute('data-product-id'));
                    const product = items.find((entry) => Number(entry.id) === id);

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
                const response = await fetch(`${@json(url('/ban-hang/products/search'))}?query=${encodeURIComponent(cleanQuery)}`, {
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

        const buildCheckoutItems = () => Array.from(cart.values()).map((item) => ({
            id: Number(item.id),
            quantity: Number(item.cart_quantity || 0),
        }));

        const completeCheckout = async (paymentMethod, paymentToken = null) => {
            const items = buildCheckoutItems();

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                const checkoutResp = await fetch(@json(url('/ban-hang/checkout')), {
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
            }
        };

        const renderLastProduct = (product) => {
            lastProductEmpty.classList.add('hidden');
            lastProductBox.classList.remove('hidden');
            previewName.textContent = product.name || '--';
            previewCode.textContent = `Mã: ${product.public_id_display || product.public_id || product.id}`;
            previewPrice.textContent = product.sale_price_text || formatMoney(product.sale_price);

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
            const subtotal = items.reduce((sum, item) => sum + (Number(item.sale_price || 0) * Number(item.cart_quantity || 0)), 0);

            itemsCountInline.textContent = String(items.reduce((sum, item) => sum + Number(item.cart_quantity || 0), 0));
            subtotalInline.textContent = formatMoney(subtotal);
            cartState.textContent = items.length ? 'Đang tính tiền' : 'Chờ quét';

            if (!items.length) {
                cartEmpty.classList.remove('hidden');
                cartItemsContainer.classList.add('hidden');
                cartItemsContainer.innerHTML = '';
                return;
            }

            cartEmpty.classList.add('hidden');
            cartItemsContainer.classList.remove('hidden');
            cartItemsContainer.innerHTML = items.map((item) => `
                <div class="flex items-center justify-between gap-3 px-4 py-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex h-12 w-12 flex-none items-center justify-center overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200">
                            ${item.image_url ? `<img src="${item.image_url}" alt="${item.name}" class="h-full w-full object-cover">` : '<span class="text-[10px] font-semibold uppercase text-slate-400">No ảnh</span>'}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-medium">${item.name}</p>
                            <p class="text-sm text-slate-500">${item.public_id_display || item.public_id || item.id}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <p class="font-semibold">${formatMoney(item.sale_price * Number(item.cart_quantity || 0))}</p>
                            <p class="text-xs text-slate-500">SL: ${Number(item.cart_quantity || 0)} × ${formatMoney(item.sale_price)}</p>
                        </div>
                        <button type="button" data-remove-id="${item.id}" class="rounded-lg border border-slate-300 px-3 py-1 text-sm text-slate-600">
                            Xóa
                        </button>
                    </div>
                </div>
            `).join('');

            cartItemsContainer.querySelectorAll('[data-remove-id]').forEach((button) => {
                button.addEventListener('click', () => {
                    const id = Number(button.getAttribute('data-remove-id'));
                    cart.delete(id);
                    renderCart();
                    setStatus('Đã xóa sản phẩm khỏi hóa đơn.', 'warning');
                    focusScanner();
                });
            });
        };

        const addProduct = (product) => {
            const productId = Number(product.id);
            const currentItem = cart.get(productId);
            const currentQuantity = Number(currentItem?.cart_quantity || 0);
            const stockQuantity = Number(product.quantity || 0);

            if (stockQuantity <= 0) {
                setStatus('Sản phẩm này hiện đã hết hàng.', 'error');
                renderLastProduct(product);
                focusScanner();
                return;
            }

            if (currentQuantity >= stockQuantity) {
                setStatus(`Sản phẩm này chỉ còn ${stockQuantity} trong kho, không thể thêm nữa.`, 'warning');
                renderLastProduct(product);
                focusScanner();
                return;
            }

            const nextItem = currentItem ? { ...currentItem, cart_quantity: currentQuantity + 1 } : { ...product, cart_quantity: 1 };

            cart.set(productId, nextItem);
            renderLastProduct(product);
            renderCart();
            setStatus(currentItem ? `Đã tăng số lượng ${product.name} lên ${currentQuantity + 1}.` : `Đã thêm ${product.name}.`, 'success');
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
                const response = await fetch(`${baseUrl}/${encodeURIComponent(cleanCode)}`, {
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) {
                    const error = await response.json().catch(() => ({}));
                    throw new Error(error.message || 'Không tìm thấy sản phẩm.');
                }

                const product = await response.json();
                addProduct(product);
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

            if (looksLikeProductCode(scannerInput.value)) {
                lookupProduct(scannerInput.value);
            } else {
                fetchSuggestions(scannerInput.value);
            }

            scannerInput.value = '';
        });

        scannerInput.addEventListener('input', () => {
            window.clearTimeout(suggestionTimer);

            const cleanValue = normalizeSearchTerm(scannerInput.value);

            if (!cleanValue) {
                hideSuggestions();
                return;
            }

            suggestionTimer = window.setTimeout(() => {
                fetchSuggestions(cleanValue);
            }, 180);
        });

        scannerInput.addEventListener('focus', () => {
            const cleanValue = normalizeSearchTerm(scannerInput.value);

            if (cleanValue) {
                fetchSuggestions(cleanValue);
            }
        });

        clearScanButton.addEventListener('click', () => {
            scannerInput.value = '';
            hideSuggestions();
            setStatus('Đã xóa nội dung ô quét.', 'warning');
            focusScanner();
        });

        resetCartButton.addEventListener('click', () => {
            cart.clear();
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

            const items = buildCheckoutItems();
            // If you need quantities >1, extend the UI to capture them; current cart stores single qty per scan.

            // First, request a payment QR payload from the server. The
            // cashier will ask the customer to transfer and then confirm.
            setStatus('Chuẩn bị mã QR chuyển khoản...', 'info');

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                const createResp = await fetch(@json(url('/ban-hang/create-payment')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({ items }),
                });

                if (createResp.status === 401) {
                    setStatus('Vui lòng đăng nhập để hoàn tất hoá đơn.', 'warning');
                    focusScanner();
                    return;
                }

                const createPayload = await createResp.json().catch(() => ({}));

                if (!createResp.ok) {
                    setStatus(createPayload.message || 'Không thể tạo mã QR.', 'error');
                    focusScanner();
                    return;
                }

                // Show modal with QR
                showPaymentModal(createPayload.qr_url, createPayload.payload, createPayload.payment_token, createPayload.total);
                setStatus('Hiển thị mã QR. Chờ xác nhận chuyển tiền.', 'info');
            } catch (err) {
                setStatus('Lỗi khi tạo mã QR.', 'error');
                focusScanner();
            }
        });

        /* Payment modal handling */
        const paymentModal = document.createElement('div');
        paymentModal.id = 'payment-modal';
        paymentModal.style.display = 'none';
        paymentModal.innerHTML = `
            <div id="payment-modal-backdrop" style="position:fixed;inset:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:60;">
                <div style="background:white;border-radius:12px;padding:20px;max-width:420px;width:90%;box-shadow:0 10px 30px rgba(0,0,0,0.2);">
                    <h3 style="font-weight:600;margin-bottom:12px;">Mã QR chuyển khoản</h3>
                    <div id="payment-qr-container" style="text-align:center;margin-bottom:12px;"></div>
                    <pre id="payment-payload" style="white-space:pre-wrap;background:#f7f7f7;padding:8px;border-radius:6px;margin-bottom:12px;font-size:13px;color:#333;"></pre>
                    <div style="display:flex;gap:8px;justify-content:flex-end;">
                        <button id="payment-cancel" type="button" style="padding:8px 12px;border-radius:8px;border:1px solid #ddd;background:#fff;">Hủy</button>
                        <button id="payment-confirm" type="button" style="padding:8px 12px;border-radius:8px;background:#10b981;color:#fff;border:none;">Tôi đã chuyển tiền</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(paymentModal);

        const showPaymentModal = (qrUrl, payloadText, paymentToken, total) => {
            const container = document.getElementById('payment-qr-container');
            const payloadEl = document.getElementById('payment-payload');
            const modal = document.getElementById('payment-modal');

            if (qrUrl) {
                container.innerHTML = `<img src="${qrUrl}" alt="Mã QR chuyển khoản" style="width:100%;max-width:320px;margin:0 auto;display:block;" />`;
            } else {
                container.innerHTML = `<div style="padding:24px;border-radius:8px;background:#f3f4f6;color:#111;">${payloadText.replace(/\n/g, '<br/>')}</div>`;
            }

            payloadEl.textContent = `Tổng: ${new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(total)} ₫\n${payloadText}`;

            paymentModal.style.display = 'block';

            document.getElementById('payment-cancel').onclick = () => {
                paymentModal.style.display = 'none';
                focusScanner();
            };

            document.getElementById('payment-confirm').onclick = async () => {
                paymentModal.style.display = 'none';
                setStatus('Xác nhận thanh toán...', 'info');

                await completeCheckout('transfer', paymentToken);
            };
        };

        scannerInput.addEventListener('blur', () => {
            // Allow other inputs (e.g. the login form) to keep focus when
            // the user explicitly focuses them on mobile. Only restore
            // focus to the scanner if nothing else sensible is focused.
            window.setTimeout(() => {
                const active = document.activeElement;

                // If something other than the document/body is focused and
                // it's an interactive field, don't steal focus back.
                if (active && active !== document.body && active !== document.documentElement) {
                    try {
                        if (typeof active.closest === 'function' && active.closest('input, textarea, select, [contenteditable]')) {
                            return;
                        }
                    } catch (e) {
                        // If any error, fall back to refocusing the scanner.
                    }
                }

                focusScanner();
            }, 0);
        });

        document.addEventListener('pointerdown', (event) => {
            if (event.target instanceof HTMLElement && event.target.closest('#suggestions-panel, #suggestions-panel *, input, button, a, textarea, select')) {
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

        // If the page already contains an element with `autofocus` (for
        // example the login email field), prefer that element and do not
        // immediately steal focus for the scanner. Otherwise, focus the scanner.
        if (!document.querySelector('input[autofocus], textarea[autofocus], select[autofocus], [autofocus]')) {
            focusScanner();
        }
    });
</script>
