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
                <label for="scanner-input" class="block text-sm font-medium text-slate-700">Ô quét mã</label>
                <div class="mt-2 flex gap-2">
                    <input id="scanner-input" type="text" inputmode="text" autocomplete="off" placeholder="Quét hoặc nhập mã rồi nhấn Enter"
                        class="h-12 flex-1 rounded-xl border-slate-300 px-4 text-lg outline-none ring-0 focus:border-slate-900 focus:ring-1 focus:ring-slate-900" />
                    <button id="clear-scan-button" type="button" class="rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700">
                        Xóa
                    </button>
                </div>
                <p id="status-box" class="mt-3 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600 ring-1 ring-slate-200">
                    Sẵn sàng nhận mã.
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
                        <div>
                            <p id="preview-name" class="text-base font-semibold">--</p>
                            <p id="preview-code" class="mt-1 text-sm text-slate-500">--</p>
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
                <button id="checkout-button" type="button" class="mt-4 w-full rounded-xl bg-emerald-500 px-4 py-3 font-semibold text-white">
                    Hoàn tất tạm tính
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
        const resetCartButton = document.getElementById('reset-cart-button');
        const checkoutButton = document.getElementById('checkout-button');
        const statusBox = document.getElementById('status-box');
        const cartItemsContainer = document.getElementById('cart-items');
        const cartEmpty = document.getElementById('cart-empty');
        const lastProductEmpty = document.getElementById('last-product-empty');
        const lastProductBox = document.getElementById('last-product-box');
        const previewName = document.getElementById('preview-name');
        const previewCode = document.getElementById('preview-code');
        const previewPrice = document.getElementById('preview-price');
        const itemsCountInline = document.getElementById('items-count-inline');
        const subtotalInline = document.getElementById('subtotal-inline');
        const cartState = document.getElementById('cart-state');

        const moneyFormatter = new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 });
        const cart = new Map();

        const focusScanner = () => window.requestAnimationFrame(() => scannerInput.focus());

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

        const renderLastProduct = (product) => {
            lastProductEmpty.classList.add('hidden');
            lastProductBox.classList.remove('hidden');
            previewName.textContent = product.name || '--';
            previewCode.textContent = `Mã: ${product.public_id || product.id}`;
            previewPrice.textContent = product.sale_price_text || formatMoney(product.sale_price);
        };

        const renderCart = () => {
            const items = Array.from(cart.values());
            const subtotal = items.reduce((sum, item) => sum + Number(item.sale_price || 0), 0);

            itemsCountInline.textContent = String(items.length);
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
                    <div class="min-w-0">
                        <p class="truncate font-medium">${item.name}</p>
                        <p class="text-sm text-slate-500">${item.public_id || item.id}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <p class="font-semibold">${formatMoney(item.sale_price)}</p>
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
            if (cart.has(Number(product.id))) {
                setStatus('Mã này đã có trong hóa đơn. Hãy xóa dòng cũ nếu cần quét lại.', 'warning');
                focusScanner();
                return;
            }

            if (Number(product.quantity) <= 0) {
                setStatus('Sản phẩm này hiện đã hết hàng.', 'error');
                renderLastProduct(product);
                focusScanner();
                return;
            }

            cart.set(Number(product.id), product);
            renderLastProduct(product);
            renderCart();
            setStatus(`Đã thêm ${product.name}.`, 'success');
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
            lookupProduct(scannerInput.value);
            scannerInput.value = '';
        });

        clearScanButton.addEventListener('click', () => {
            scannerInput.value = '';
            setStatus('Đã xóa nội dung ô quét.', 'warning');
            focusScanner();
        });

        resetCartButton.addEventListener('click', () => {
            cart.clear();
            renderCart();
            lastProductEmpty.classList.remove('hidden');
            lastProductBox.classList.add('hidden');
            setStatus('Đã làm mới hóa đơn.', 'warning');
            focusScanner();
        });

        checkoutButton.addEventListener('click', async () => {
            if (!cart.size) {
                setStatus('Chưa có sản phẩm để chốt.', 'warning');
                focusScanner();
                return;
            }

            const items = Array.from(cart.values()).map(item => ({ id: Number(item.id), quantity: 1 }));
            // If you need quantities >1, extend the UI to capture them; current cart stores single qty per scan.

            setStatus('Đang gửi yêu cầu chốt hoá đơn...');

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                const response = await fetch(@json(url('/ban-hang/checkout')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({ items }),
                });

                if (response.status === 401) {
                    setStatus('Vui lòng đăng nhập để hoàn tất hoá đơn.', 'warning');
                    focusScanner();
                    return;
                }

                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    setStatus(payload.message || 'Lỗi khi chốt hoá đơn.', 'error');
                    focusScanner();
                    return;
                }

                cart.clear();
                renderCart();
                lastProductEmpty.classList.remove('hidden');
                lastProductBox.classList.add('hidden');
                setStatus(payload.message || 'Chốt hoá đơn thành công.', 'success');
                focusScanner();
            } catch (err) {
                setStatus('Lỗi khi chốt hoá đơn.', 'error');
                focusScanner();
            }
        });

        scannerInput.addEventListener('blur', () => {
            window.setTimeout(focusScanner, 0);
        });

        document.addEventListener('pointerdown', (event) => {
            if (event.target instanceof HTMLElement && event.target.closest('input, button, a, textarea, select')) {
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
        focusScanner();
    });
</script>
