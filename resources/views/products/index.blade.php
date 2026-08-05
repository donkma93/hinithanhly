<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">Sản phẩm</h2>
            <p class="text-sm text-gray-500">Thêm sản phẩm bằng file ảnh hoặc camera từ thiết bị di động.</p>
        </div>
    </x-slot>

    @php
        $supplierOptions = $suppliers->map(fn ($supplier) => [
            'value' => $supplier->id,
            'label' => '#'.$supplier->public_id_display.' - '.$supplier->name,
        ])->all();
        $categoryOptions = $categories->map(fn ($category) => [
            'value' => $category->id,
            'label' => '#'.$category->public_id_display.' - '.$category->name,
        ])->all();
        $supplierTypeMap = $suppliers->mapWithKeys(fn ($supplier) => [(string) $supplier->id => $supplier->type])->all();
    @endphp

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-10xl space-y-6 px-3 sm:px-6 lg:px-10">
            @if (session('status'))
                <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-2xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700 ring-1 ring-red-200">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div id="consignment-expiry-summary" class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-3xl border border-amber-200 bg-white p-5 shadow-sm ring-1 ring-amber-100">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-amber-700">Sắp hết hạn</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $consignmentExpirySummary['expiring_soon'] }}</p>
                    <p class="mt-1 text-sm text-slate-500">Trong vòng 7 ngày tới cần theo dõi.</p>
                </div>

                <div class="rounded-3xl border border-rose-200 bg-white p-5 shadow-sm ring-1 ring-rose-100">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-rose-700">Quá hạn</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $consignmentExpirySummary['expired'] }}</p>
                    <p class="mt-1 text-sm text-slate-500">Đã quá 45 ngày và cần trả ngay.</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm ring-1 ring-slate-100">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-600">Đã trả</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $consignmentExpirySummary['returned'] }}</p>
                    <p class="mt-1 text-sm text-slate-500">Đã đánh dấu trả cho người gửi.</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 rounded-3xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                <div>
                    <p class="text-sm font-semibold text-slate-900">Cần xem danh sách hết hạn riêng?</p>
                    <p class="text-sm text-slate-500">Mở màn quản lý để lọc nhanh sản phẩm sắp hết hạn, quá hạn và đã trả.</p>
                </div>
                <a href="{{ route('products.expiry') }}" class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                    Mở màn quản lý hạn ký gửi
                    <span class="rounded-full bg-white/10 px-2 py-0.5 text-xs font-semibold">{{ $consignmentExpirySummary['pending'] }}</span>
                </a>
            </div>

            {{-- Mobile: Nút mở form thêm sản phẩm --}}
            @canany(['products.create', 'products.manage'])
            <div class="lg:hidden">
                <button
                    type="button"
                    id="mobile-add-product-toggle"
                    onclick="(function(btn){
                        var panel = document.getElementById('mobile-add-product-panel');
                        var isHidden = panel.classList.contains('hidden');
                        panel.classList.toggle('hidden', !isHidden);
                        btn.querySelector('span').textContent = isHidden ? 'Đóng form' : '+ Thêm sản phẩm';
                    })(this)"
                    class="flex w-full items-center justify-center gap-2 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    <span>+ Thêm sản phẩm</span>
                </button>

                {{-- Form thêm sản phẩm mobile (ẩn mặc định) --}}
                <div id="mobile-add-product-panel" class="hidden mt-3 rounded-3xl bg-white p-4 shadow-sm ring-1 ring-gray-200"
                    x-data="{
                        supplierTypes: @js($supplierTypeMap),
                        manualTypes: @js(\App\Models\Supplier::MANUAL_CONSIGNMENT_TYPES),
                        autoTypes: @js(\App\Models\Supplier::AUTO_CONSIGNMENT_TYPES),
                        selectedSupplierId: @js((string) old('supplier_id')),
                        init() {
                            window.addEventListener('searchable-select-change', (event) => {
                                if (event.detail?.name !== 'supplier_id') return;
                                this.selectedSupplierId = String(event.detail?.value ?? '');
                            });
                        },
                        get selectedSupplierType() { return this.supplierTypes[this.selectedSupplierId] ?? ''; },
                        get requiresManualConsignment() { return this.manualTypes.includes(this.selectedSupplierType); },
                        get usesAutoGeneratedConsignment() { return this.autoTypes.includes(this.selectedSupplierType); },
                    }"
                >
                    <h3 class="text-lg font-semibold text-gray-900">Thêm sản phẩm</h3>
                    <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                        @csrf
                        <x-image-upload-preview
                            name="image"
                            label="Ảnh sản phẩm"
                            helper-text="Chụp ảnh hoặc chọn từ thư viện thiết bị."
                        />
                        @error('image') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nhà cung cấp</label>
                            <x-searchable-select
                                name="supplier_id"
                                :options="$supplierOptions"
                                :selected="old('supplier_id')"
                                placeholder="-- Chọn NCC --"
                                search-placeholder="Tìm theo mã hoặc tên"
                                empty-text="Không có nhà cung cấp phù hợp"
                            />
                            @error('supplier_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Danh mục</label>
                            <x-searchable-select
                                name="category_id"
                                :options="$categoryOptions"
                                :selected="old('category_id')"
                                placeholder="-- Chọn danh mục --"
                                search-placeholder="Tìm theo mã hoặc tên"
                                empty-text="Không có danh mục phù hợp"
                            />
                            @error('category_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div x-cloak x-show="requiresManualConsignment">
                            <label class="block text-sm font-medium text-gray-700">Phiếu ký gửi</label>
                            <x-searchable-select
                                name="consignment_note_id"
                                :options="$consignmentOptions"
                                :selected="old('consignment_note_id')"
                                placeholder="-- Chọn phiếu --"
                                search-placeholder="Tìm theo lần gửi hoặc ngày gửi"
                                empty-text="Không có phiếu phù hợp"
                                depends-on="supplier_id"
                                :dependency-value="old('supplier_id')"
                            />
                            @error('consignment_note_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tên sản phẩm</label>
                            <input name="name" value="{{ old('name') }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                            @error('name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Giá bán</label>
                                <input type="number" step="0.01" name="sale_price" value="{{ old('sale_price', 0) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                                @error('sale_price') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Số lượng</label>
                                <input type="number" min="1" name="quantity" value="{{ old('quantity', 1) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                                @error('quantity') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ghi chú</label>
                            <textarea name="description" rows="3" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900">{{ old('description') }}</textarea>
                            @error('description') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <button class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Lưu sản phẩm</button>
                    </form>
                </div>
            </div>
            @endcanany

            {{-- Grid chính: form (desktop only) + bảng danh sách --}}
            <div class="grid gap-6 lg:grid-cols-12 lg:items-start">
                {{-- Form thêm sản phẩm - chỉ hiện trên desktop --}}
                <div
                    class="hidden rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200 lg:col-span-4 lg:block"
                    x-data="{
                        supplierTypes: @js($supplierTypeMap),
                        manualTypes: @js(\App\Models\Supplier::MANUAL_CONSIGNMENT_TYPES),
                        autoTypes: @js(\App\Models\Supplier::AUTO_CONSIGNMENT_TYPES),
                        selectedSupplierId: @js((string) old('supplier_id')),
                        init() {
                            window.addEventListener('searchable-select-change', (event) => {
                                if (event.detail?.name !== 'supplier_id') return;
                                this.selectedSupplierId = String(event.detail?.value ?? '');
                            });
                        },
                        get selectedSupplierType() { return this.supplierTypes[this.selectedSupplierId] ?? ''; },
                        get requiresManualConsignment() { return this.manualTypes.includes(this.selectedSupplierType); },
                        get usesAutoGeneratedConsignment() { return this.autoTypes.includes(this.selectedSupplierType); },
                    }"
                >
                    @canany(['products.create', 'products.manage'])
                        <h3 class="text-lg font-semibold text-gray-900">Thêm sản phẩm</h3>
                        <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                            @csrf
                            <x-image-upload-preview
                                name="image"
                                label="Ảnh sản phẩm"
                                helper-text="Bạn có thể chụp ảnh trực tiếp từ camera hoặc chọn ảnh từ thư viện thiết bị trước khi upload."
                            />
                            @error('image') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nhà cung cấp</label>
                                <x-searchable-select
                                    name="supplier_id"
                                    :options="$supplierOptions"
                                    :selected="old('supplier_id')"
                                    placeholder="-- Chọn NCC --"
                                    search-placeholder="Tìm theo mã hoặc tên"
                                    empty-text="Không có nhà cung cấp phù hợp"
                                />
                                @error('supplier_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Danh mục</label>
                                <x-searchable-select
                                    name="category_id"
                                    :options="$categoryOptions"
                                    :selected="old('category_id')"
                                    placeholder="-- Chọn danh mục --"
                                    search-placeholder="Tìm theo mã hoặc tên"
                                    empty-text="Không có danh mục phù hợp"
                                />
                                @error('category_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div x-cloak x-show="requiresManualConsignment">
                                <label class="block text-sm font-medium text-gray-700">Phiếu ký gửi</label>
                                <x-searchable-select
                                    name="consignment_note_id"
                                    :options="$consignmentOptions"
                                    :selected="old('consignment_note_id')"
                                    placeholder="-- Chọn phiếu --"
                                    search-placeholder="Tìm theo lần gửi hoặc ngày gửi"
                                    empty-text="Không có phiếu phù hợp"
                                    depends-on="supplier_id"
                                    :dependency-value="old('supplier_id')"
                                />
                                @error('consignment_note_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tên sản phẩm</label>
                                <input name="name" value="{{ old('name') }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                                @error('name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Giá bán</label>
                                    <input type="number" step="0.01" name="sale_price" value="{{ old('sale_price', 0) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                                    @error('sale_price') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Số lượng</label>
                                    <input type="number" min="1" name="quantity" value="{{ old('quantity', 1) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                                    @error('quantity') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Ghi chú</label>
                                <textarea name="description" rows="4" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900">{{ old('description') }}</textarea>
                                @error('description') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <button class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Lưu sản phẩm</button>
                        </form>
                    @else
                        <h3 class="text-lg font-semibold text-gray-900">Thêm sản phẩm</h3>
                        <p class="mt-3 text-sm text-gray-500">Bạn chỉ có quyền xem sản phẩm.</p>
                    @endcanany
                </div>

                <div
                    class="min-w-0 rounded-3xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:p-6 lg:col-span-8"
                    x-data="{
                        imageZoomOpen: false,
                        imageZoomSrc: '',
                        imageZoomAlt: '',
                        openImageZoom(src, alt) {
                            this.imageZoomSrc = src;
                            this.imageZoomAlt = alt;
                            this.imageZoomOpen = true;
                        },
                        closeImageZoom() {
                            this.imageZoomOpen = false;
                        },
                    }"
                >
                    <div class="space-y-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <h3 class="text-lg font-semibold text-gray-900">Danh sách</h3>
                                @if ($filterSupplierId)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-700 ring-1 ring-indigo-200">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" /></svg>
                                        Đang lọc theo NCC
                                    </span>
                                @endif
                            </div>
                            <x-per-page-select :value="request('per_page', 10)" />
                        </div>
                        <form method="GET" action="{{ route('products.index') }}" class="grid gap-2 md:grid-cols-2 xl:grid-cols-4" id="product-filter-form">
                            <x-searchable-select
                                name="supplier_id"
                                :options="$filterSupplierOptions"
                                :selected="request('supplier_id', $filterSupplierId)"
                                placeholder="Tất cả NCC"
                                search-placeholder="Tìm NCC theo mã hoặc tên"
                                empty-text="Không có NCC phù hợp"
                                submit-on-select
                            />
                            <input name="product_public_id" value="{{ request('product_public_id', request('public_id')) }}" class="rounded-xl border-gray-300 text-sm focus:border-slate-900 focus:ring-slate-900" placeholder="Đúng mã hàng">
                            <input name="product_name" value="{{ request('product_name') }}" class="rounded-xl border-gray-300 text-sm focus:border-slate-900 focus:ring-slate-900" placeholder="Tìm tên sản phẩm">
                            <button class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white whitespace-nowrap">Lọc</button>
                        </form>
                    </div>
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full min-w-[1200px] table-fixed divide-y divide-gray-200 text-sm">
                            <colgroup>
                                <col class="w-[9%]">
                                <col class="w-[14%]">
                                <col class="w-[8%]">
                                <col class="w-[26%]">
                                <col class="w-[10%]">
                                <col class="w-[8%]">
                                <col class="w-[15%]">
                                <col class="w-[10%]">
                            </colgroup>
                            <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="py-3 pr-4">Mã hàng</th>
                                    <th class="py-3 pr-4">Mã NCC</th>
                                    <th class="py-3 pr-4">Ảnh</th>
                                    <th class="py-3 pr-4">Tên sản phẩm</th>
                                    <th class="py-3 pr-4">Giá</th>
                                    <th class="py-3 pr-4">Tồn kho</th>
                                    <th class="py-3 pr-4">Lần gửi</th>
                                    <th class="py-3 pr-4 text-right">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($products as $product)
                                    <tr>
                                        <td class="py-3 pr-4 font-medium text-slate-900">#{{ $product->public_id_display }}</td>
                                        <td class="py-3 pr-4 align-top text-gray-600 break-words">
                                            <div class="font-medium text-gray-900">{{ $product->supplier?->public_id ? '#'.$product->supplier->public_id_display : '---' }}</div>
                                            <div class="text-xs text-gray-500">{{ $product->supplier?->name ?? '---' }}</div>
                                        </td>
                                        <td class="py-3 pr-4">
                                            @if ($product->image_path)
                                                <button
                                                    type="button"
                                                    class="group block"
                                                    @click="openImageZoom(@js(asset('storage/' . $product->image_path)), @js($product->name))"
                                                >
                                                    <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}" class="h-14 w-14 rounded-xl object-cover ring-1 ring-gray-200 transition group-hover:scale-105 group-hover:ring-slate-400">
                                                </button>
                                            @else
                                                <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-slate-100 text-xs font-semibold text-slate-500 ring-1 ring-gray-200">Không ảnh</div>
                                            @endif
                                        </td>
                                        <td class="py-3 pr-4 align-top font-medium text-gray-900 break-words">{{ $product->name }}</td>
                                        <td class="py-3 pr-4 align-top text-gray-600 whitespace-nowrap">{{ number_format($product->sale_price ?? 0, 0, ',', '.') }} đ</td>
                                        <td class="py-3 pr-4 align-top text-gray-600 whitespace-nowrap">{{ $product->quantity }}</td>
                                        <td class="py-3 pr-4 align-top text-gray-600">
                                            <div class="font-medium text-gray-900">Lần {{ $product->send_round ?? 1 }}</div>
                                            <div class="text-xs text-gray-500">{{ $product->send_summary ?? '---' }}</div>
                                            <div class="mt-1 text-xs {{ $product->isReturned() ? 'text-rose-600' : ($product->isConsignmentExpired() ? 'text-rose-600' : ($product->isConsignmentExpiringSoon() ? 'text-amber-600' : 'text-emerald-600')) }}">
                                                {{ $product->consignment_status_label }}
                                            </div>
                                            @if ($product->returned_at)
                                                <div class="mt-1 text-[11px] text-gray-400">Đã trả lúc {{ optional($product->returned_at)->format('d/m/Y H:i') }}</div>
                                            @endif
                                        </td>
                                        <td class="py-3 pr-4 align-top text-right whitespace-nowrap">
                                            @if ($product->isReturned())
                                                <span class="inline-flex items-center rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-200">Đã trả NCC</span>
                                                @canany(['products.update', 'products.manage'])
                                                    <a href="{{ route('products.edit', $product) }}" class="ms-4 text-slate-900 hover:underline">Sửa</a>
                                                @endcanany
                                            @else
                                                @can('products.view')
                                                    <a href="{{ route('products.label', $product) }}" target="_blank" rel="noopener" class="text-slate-900 hover:underline">In mã hàng</a>
                                                @endcan
                                                @canany(['products.update', 'products.manage'])
                                                    <a href="{{ route('products.edit', $product) }}" class="ms-4 text-slate-900 hover:underline">Sửa</a>
                                                @endcanany
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="py-8 text-center text-gray-500">Chưa có sản phẩm nào.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $products->links() }}</div>

                    <div
                        x-cloak
                        x-show="imageZoomOpen"
                        x-transition.opacity
                        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4"
                        @click.self="closeImageZoom()"
                        @keydown.escape.window="closeImageZoom()"
                    >
                        <div class="relative w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-2xl">
                            <button type="button" class="absolute right-3 top-3 z-10 rounded-full bg-white/90 px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 hover:bg-white" @click="closeImageZoom()">Đóng</button>
                            <img :src="imageZoomSrc" :alt="imageZoomAlt" class="max-h-[85vh] w-full bg-slate-950 object-contain">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
