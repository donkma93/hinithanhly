<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">Quản lý hạn ký gửi</h2>
            <p class="text-sm text-gray-500">Theo dõi các sản phẩm sắp hết hạn, đã quá hạn và đã trả cho người gửi.</p>
        </div>
    </x-slot>

    @php
        $supplierOptions = $suppliers->map(fn ($supplier) => [
            'value' => $supplier->id,
            'label' => '#'.$supplier->public_id_display.' - '.$supplier->name,
        ])->all();

        $statusToneClasses = [
            'gray' => 'border-slate-200 bg-slate-50 text-slate-700 ring-slate-200',
            'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700 ring-emerald-200',
            'warning' => 'border-amber-200 bg-amber-50 text-amber-700 ring-amber-200',
            'danger' => 'border-rose-200 bg-rose-50 text-rose-700 ring-rose-200',
        ];

        $statusCards = [
            'pending' => [
                'label' => 'Cần xử lý',
                'count' => $consignmentExpirySummary['pending'],
                'description' => 'Sắp hết hạn hoặc đã quá hạn',
                'tone' => 'warning',
            ],
            'expiring_soon' => [
                'label' => 'Sắp hết hạn',
                'count' => $consignmentExpirySummary['expiring_soon'],
                'description' => 'Còn tối đa 7 ngày',
                'tone' => 'warning',
            ],
            'expired' => [
                'label' => 'Quá hạn',
                'count' => $consignmentExpirySummary['expired'],
                'description' => 'Đã qua mốc 45 ngày',
                'tone' => 'danger',
            ],
            'returned' => [
                'label' => 'Đã trả',
                'count' => $consignmentExpirySummary['returned'],
                'description' => 'Đã đánh dấu trả cho người gửi',
                'tone' => 'gray',
            ],
        ];

        $activeStatusLabel = $expiryStatusOptions[$filterStatus]['label'] ?? 'Cần xử lý';
    @endphp

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-10xl space-y-6 px-3 sm:px-6 lg:px-10">
            @if (session('status'))
                <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($statusCards as $statusKey => $card)
                    <a
                        href="{{ request()->fullUrlWithQuery(['status' => $statusKey, 'page' => 1]) }}"
                        class="rounded-3xl border bg-white p-5 shadow-sm ring-1 transition hover:-translate-y-0.5 hover:shadow-md {{ $filterStatus === $statusKey ? 'border-slate-900 ring-slate-900/10' : $statusToneClasses[$card['tone']] }}"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.25em] {{ $filterStatus === $statusKey ? 'text-slate-900' : ($card['tone'] === 'danger' ? 'text-rose-700' : ($card['tone'] === 'warning' ? 'text-amber-700' : ($card['tone'] === 'success' ? 'text-emerald-700' : 'text-slate-600'))) }}">
                                    {{ $card['label'] }}
                                </p>
                                <p class="mt-2 text-3xl font-semibold text-slate-900">{{ number_format($card['count']) }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $card['description'] }}</p>
                            </div>
                            @if ($filterStatus === $statusKey)
                                <span class="rounded-full bg-slate-900 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-white">Đang xem</span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="rounded-3xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:p-6">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Bộ lọc hạn ký gửi</h3>
                        <p class="text-sm text-gray-500">Đang xem: {{ $activeStatusLabel }}.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('products.index') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Quay lại sản phẩm
                        </a>
                        <a href="{{ route('products.expiry') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Xóa bộ lọc
                        </a>
                    </div>
                </div>

                <form method="GET" action="{{ route('products.expiry') }}" class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-7 xl:items-end">
                    <input type="hidden" name="status" value="{{ $filterStatus }}">

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Nhà cung cấp</label>
                        <x-searchable-select
                            name="supplier_id"
                            :options="$supplierOptions"
                            :selected="$filterSupplierId"
                            placeholder="-- Tất cả nhà cung cấp --"
                            search-placeholder="Tìm theo mã nhà cung cấp"
                            empty-text="Không có nhà cung cấp phù hợp"
                            submit-on-select
                        />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Mã hàng</label>
                        <input name="product_public_id" value="{{ $exactFilters['product_public_id'] ?? '' }}" class="w-full rounded-xl border-gray-300 text-sm focus:border-slate-900 focus:ring-slate-900" placeholder="Đúng mã hàng">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Tên sản phẩm</label>
                        <input name="product_name" value="{{ $exactFilters['product_name'] ?? '' }}" class="w-full rounded-xl border-gray-300 text-sm focus:border-slate-900 focus:ring-slate-900" placeholder="Đúng tên sản phẩm">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Mã NCC</label>
                        <input name="supplier_public_id" value="{{ $exactFilters['supplier_public_id'] ?? '' }}" class="w-full rounded-xl border-gray-300 text-sm focus:border-slate-900 focus:ring-slate-900" placeholder="Đúng mã NCC">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Tên NCC</label>
                        <input name="supplier_name" value="{{ $exactFilters['supplier_name'] ?? '' }}" class="w-full rounded-xl border-gray-300 text-sm focus:border-slate-900 focus:ring-slate-900" placeholder="Đúng tên NCC">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Mỗi trang</label>
                        <select name="per_page" class="w-full rounded-xl border-gray-300 text-sm focus:border-slate-900 focus:ring-slate-900" onchange="this.form.submit()">
                            @foreach ([10, 20, 50, 100] as $option)
                                <option value="{{ $option }}" @selected($products->perPage() === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <button type="submit" class="w-full rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white whitespace-nowrap">
                            Lọc
                        </button>
                    </div>
                </form>
            </div>

            <div class="rounded-3xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:p-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Danh sách</h3>
                        <p class="text-sm text-gray-500">Các sản phẩm cần xử lý sẽ hiện ở đây để bạn trả cho người gửi ngay khi cần.</p>
                    </div>
                    <div class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                        {{ $products->total() }} kết quả
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-[1120px] divide-y divide-gray-200 text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="py-3 pr-4">Mã hàng</th>
                                <th class="py-3 pr-4">Ảnh</th>
                                <th class="py-3 pr-4">Sản phẩm</th>
                                <th class="py-3 pr-4">Nhà cung cấp</th>
                                <th class="py-3 pr-4">Ngày gửi</th>
                                <th class="py-3 pr-4">Hạn trả</th>
                                <th class="py-3 pr-4">Trạng thái</th>
                                <th class="py-3 pr-4 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($products as $product)
                                @php
                                    $dueDate = $product->consignmentDueDate();
                                    $toneClass = $statusToneClasses[$product->consignment_status_tone] ?? $statusToneClasses['gray'];
                                @endphp
                                <tr class="{{ $product->isConsignmentExpired() ? 'bg-rose-50/40' : ($product->isConsignmentExpiringSoon() ? 'bg-amber-50/40' : '') }}">
                                    <td class="py-3 pr-4 align-top font-medium text-slate-900">#{{ $product->public_id_display }}</td>
                                    <td class="py-3 pr-4 align-top">
                                        @if ($product->image_path)
                                            <a href="{{ asset('storage/'.$product->image_path) }}" target="_blank" rel="noopener" class="block">
                                                <img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}" class="h-14 w-14 rounded-xl object-cover ring-1 ring-gray-200 transition hover:scale-[1.02]">
                                            </a>
                                        @else
                                            <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-slate-100 text-xs font-semibold text-slate-500 ring-1 ring-gray-200">Không ảnh</div>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-4 align-top">
                                        <div class="font-medium text-gray-900">{{ $product->name }}</div>
                                        <div class="mt-1 text-xs text-gray-500">#{{ $product->category?->public_id_display ?? '---' }} · {{ $product->sale_price ? number_format((float) $product->sale_price, 0, ',', '.') . ' đ' : '---' }}</div>
                                    </td>
                                    <td class="py-3 pr-4 align-top text-gray-600">
                                        <div class="font-medium text-gray-900">#{{ $product->supplier?->public_id_display ?? '---' }}</div>
                                        <div class="text-xs text-gray-500">{{ $product->supplier?->name ?? '---' }}</div>
                                    </td>
                                    <td class="py-3 pr-4 align-top text-gray-600">
                                        {{ optional($product->consignmentNote?->sent_date)->format('d/m/Y') ?? '---' }}
                                    </td>
                                    <td class="py-3 pr-4 align-top text-gray-600">
                                        {{ optional($dueDate)->format('d/m/Y') ?? '---' }}
                                    </td>
                                    <td class="py-3 pr-4 align-top">
                                        <div class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $toneClass }}">
                                            {{ $product->consignment_status_label }}
                                        </div>
                                        @if ($product->isReturned())
                                            <p class="mt-2 text-xs text-gray-500">
                                                Đã trả lúc {{ optional($product->returned_at)->format('d/m/Y H:i') }}
                                                @if ($product->returner)
                                                    · {{ $product->returner->name }}
                                                @endif
                                            </p>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-4 align-top text-right whitespace-nowrap">
                                        @if (! $product->isReturned())
                                            @canany(['products.update', 'products.manage'])
                                                <x-confirm-action
                                                    :name="'return-expiry-product-'.$product->public_id"
                                                    :action="route('products.return', $product)"
                                                    title="Trả sản phẩm cho người gửi"
                                                    :message="'Sản phẩm #'.$product->public_id_display.' - '.$product->name.' sẽ được đánh dấu đã trả cho người gửi và không thể bán trên hệ thống nữa.'"
                                                    confirm-text="Đánh dấu đã trả"
                                                    trigger-text="Trả hàng"
                                                    method="POST"
                                                    trigger-class="inline-flex items-center rounded-xl bg-rose-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-rose-500"
                                                />
                                            @endcanany
                                        @else
                                            <span class="inline-flex items-center rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700">Đã trả</span>
                                        @endif

                                        @canany(['products.update', 'products.manage'])
                                            <a href="{{ route('products.edit', $product) }}" class="ms-3 inline-flex items-center rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                                                Sửa
                                            </a>
                                        @endcanany
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-10 text-center text-sm text-gray-500">
                                        Không có sản phẩm nào phù hợp bộ lọc hiện tại.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $products->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
