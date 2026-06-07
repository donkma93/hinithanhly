<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'HINITHANLYKYGUI') }} - Tra cứu nhà cung cấp</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800,900&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body {
                font-family: "Manrope", sans-serif;
                background:
                    radial-gradient(circle at 12% 12%, rgba(14, 165, 233, 0.12), transparent 22%),
                    radial-gradient(circle at 88% 10%, rgba(16, 185, 129, 0.10), transparent 20%),
                    linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
            }
        </style>
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
        @php
            $statusToneClasses = [
                'gray' => 'border-slate-200 bg-slate-50 text-slate-700',
                'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                'warning' => 'border-amber-200 bg-amber-50 text-amber-700',
                'danger' => 'border-rose-200 bg-rose-50 text-rose-700',
            ];

            $statusFilters = $statusOptions ?? [];
            $hasPortalMap = filled($portalMapUrl ?? '');
        @endphp

        <div id="home" class="relative isolate overflow-hidden pb-28 md:pb-0">
            <div class="pointer-events-none absolute inset-0 opacity-60">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-sky-400/70 to-transparent"></div>
                <div class="absolute -left-28 top-24 h-72 w-72 rounded-full bg-sky-200/50 blur-3xl"></div>
                <div class="absolute right-0 top-40 h-80 w-80 rounded-full bg-emerald-200/40 blur-3xl"></div>
            </div>

            <header class="relative z-10 border-b border-slate-200/80 bg-white/80 backdrop-blur-xl">
                <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500 to-cyan-400 text-lg font-black text-white shadow-lg shadow-sky-500/25">
                            HK
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.35em] text-sky-600">HINITHANLYKYGUI</p>
                            <p class="mt-1 text-sm font-medium text-slate-600">Cổng tra cứu sản phẩm ký gửi cho nhà cung cấp</p>
                        </div>
                    </div>

                    @auth
                        <div class="flex flex-wrap items-center gap-3">
                            <a
                                href="{{ route('dashboard') }}"
                                class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
                            >
                                Vào Dashboard
                            </a>
                        </div>
                    @endauth
                </div>
            </header>

            <main class="relative z-10">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-10 lg:px-8">
                    <section class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                        <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)] sm:p-8 lg:p-10">
                            <span class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-4 py-1.5 text-xs font-bold uppercase tracking-[0.3em] text-sky-700">
                                Tra cứu nhà cung cấp
                            </span>

                            <h1 class="mt-5 max-w-3xl text-4xl font-black tracking-tight text-slate-950 sm:text-5xl lg:text-6xl lg:leading-[0.95]">
                                Nhà cung cấp xem ngay tình trạng sản phẩm của mình, rõ ràng và dễ tra cứu
                            </h1>

                            <p class="mt-4 max-w-3xl text-base leading-8 text-slate-700 sm:text-lg">
                                Nhập mã nhà cung cấp hoặc số điện thoại để kiểm tra danh sách sản phẩm đang ký gửi. Hệ thống sẽ chia rõ món còn hiệu lực, sắp hết hạn, quá hạn và đã được trả.
                            </p>

                            <div class="mt-6 flex flex-wrap gap-3 text-sm font-semibold text-slate-700">
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2">Chu kỳ ký gửi {{ $consignmentTermDays }} ngày</span>
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2">Cảnh báo trước {{ $consignmentWarningDays }} ngày</span>
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2">Dữ liệu đồng bộ theo hệ thống</span>
                            </div>

                            <div class="mt-8 grid gap-4 sm:grid-cols-3">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-slate-500">Nhanh</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">Tra cứu bằng mã NCC hoặc số điện thoại</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-slate-500">Rõ ràng</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">Trạng thái hiển thị theo từng nhóm</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-slate-500">Gọn</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">Có nút mobile Home, Tra cứu, Địa chỉ</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[2rem] border border-slate-200 bg-gradient-to-br from-slate-900 to-slate-800 p-6 text-black shadow-[0_20px_60px_rgba(15,23,42,0.18)] sm:p-8">
                            <p class="text-xs font-bold uppercase tracking-[0.35em] text-cyan-300">THÔNG BÁO</p>
                            <h2 class="mt-4 text-2xl font-black text-black">Theo dõi nhanh, nhìn thấy rõ ngay</h2>
                            <ul class="mt-6 space-y-4 text-sm leading-7 text-slate-200">
                                <li class="flex gap-3">
                                    <span class="mt-2 h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                                    <span>Mỗi sản phẩm được theo dõi theo chu kỳ ký gửi 45 ngày.</span>
                                </li>
                                <li class="flex gap-3">
                                    <span class="mt-2 h-2.5 w-2.5 rounded-full bg-amber-300"></span>
                                    <span>Món còn tối đa 7 ngày sẽ được xếp vào nhóm sắp hết hạn.</span>
                                </li>
                                <li class="flex gap-3">
                                    <span class="mt-2 h-2.5 w-2.5 rounded-full bg-rose-300"></span>
                                    <span>Sản phẩm đã trả sẽ được gắn trạng thái riêng và không còn bán trên hệ thống.</span>
                                </li>
                            </ul>

                            <div class="mt-8 rounded-3xl border border-white/10 bg-white/5 p-5">
                                <p class="text-xs font-bold uppercase tracking-[0.3em] text-slate-300">Ký gửi</p>
                                <p class="mt-3 text-sm leading-7 text-slate-200">Quy tắc hiển thị giúp nhà cung cấp nhận biết nhanh món nào cần xử lý, không phải dò từng dòng dài.</p>
                            </div>
                        </div>
                    </section>

                    <section class="mt-6 grid gap-6 lg:grid-cols-[0.9fr_1.1fr_1fr]">
                        <article class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
                            <p class="text-xs font-bold uppercase tracking-[0.35em] text-emerald-600">THÔNG BÁO</p>
                            <h2 class="mt-4 text-2xl font-black text-slate-950">Tập trung vào sản phẩm sắp cần xử lý</h2>
                            <p class="mt-3 text-sm leading-7 text-slate-700">
                                Các món sắp hết hạn hoặc quá hạn sẽ được ưu tiên đẩy lên đầu để nhà cung cấp dễ theo dõi và trao đổi với cửa hàng.
                            </p>
                        </article>

                        <article class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
                            <p class="text-xs font-bold uppercase tracking-[0.35em] text-sky-600">KÝ GỬI</p>
                            <h2 class="mt-4 text-2xl font-black text-slate-950">Kiểm tra theo mã NCC</h2>
                            <div class="mt-5 space-y-4 text-sm leading-7 text-slate-700">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="font-semibold text-slate-950">1. Tra cứu bằng mã NCC</p>
                                    <p class="mt-1">Nhập đúng mã giống trên phiếu hoặc nhãn để tìm đúng dữ liệu.</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="font-semibold text-slate-950">2. Tra cứu bằng số điện thoại</p>
                                    <p class="mt-1">Nếu cần, có thể dùng số điện thoại liên hệ đã đăng ký.</p>
                                </div>
                            </div>
                        </article>

                        <article id="tra-cuu" class="scroll-mt-24 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
                            <p class="text-xs font-bold uppercase tracking-[0.35em] text-cyan-600">NHÀ CUNG CẤP - TRA CỨU</p>
                            <h2 class="mt-4 text-2xl font-black text-slate-950">Nhập mã để xem sản phẩm</h2>
                            <p class="mt-2 text-sm leading-7 text-slate-700">
                                Điền mã nhà cung cấp hoặc số điện thoại rồi bấm tra cứu để xem danh sách sản phẩm của bạn.
                            </p>

                            <form method="GET" action="{{ route('home') }}" class="mt-5 space-y-4">
                                <div>
                                    <label for="supplier_code" class="mb-2 block text-sm font-semibold text-slate-800">Mã nhà cung cấp</label>
                                    <input
                                        id="supplier_code"
                                        type="text"
                                        name="supplier_code"
                                        value="{{ $supplierCode }}"
                                        placeholder="Tìm theo mã nhà cung cấp"
                                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-950 placeholder:text-slate-400 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/15"
                                    >
                                </div>

                                <div>
                                    <label for="phone" class="mb-2 block text-sm font-semibold text-slate-800">Số điện thoại</label>
                                    <input
                                        id="phone"
                                        type="text"
                                        name="phone"
                                        value="{{ $phone }}"
                                        placeholder="Nhập số điện thoại liên hệ"
                                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-950 placeholder:text-slate-400 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/15"
                                    >
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    <button
                                        type="submit"
                                        class="inline-flex items-center justify-center rounded-full bg-slate-950 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-950/20 transition hover:bg-slate-800"
                                    >
                                        Tra cứu
                                    </button>

                                    @if ($searchPerformed)
                                        <a
                                            href="{{ route('home') }}"
                                            class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                                        >
                                            Xóa lọc
                                        </a>
                                    @endif
                                </div>
                            </form>

                            @if ($searchError)
                                <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                                    {{ $searchError }}
                                </div>
                            @endif
                        </article>
                    </section>

                    @if ($supplier)
                        <section class="mt-6 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)] sm:p-8">
                            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                                <div class="max-w-3xl">
                                    <p class="text-xs font-bold uppercase tracking-[0.35em] text-emerald-600">KẾT QUẢ TRA CỨU</p>
                                    <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950">{{ $supplier->name }}</h2>
                                    <p class="mt-2 text-sm font-medium leading-7 text-slate-700">
                                        #{{ $supplier->public_id_display }}
                                        <span class="mx-2 text-slate-300">·</span>
                                        {{ \App\Models\Supplier::labelForType($supplier->type) }}
                                        @if ($supplier->phone)
                                            <span class="mx-2 text-slate-300">·</span>
                                            {{ $supplier->phone }}
                                        @endif
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2 text-xs font-bold uppercase tracking-[0.2em] text-slate-600">
                                    <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-2">Tổng {{ number_format($statusSummary['all'] ?? 0) }} sản phẩm</span>
                                    <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-2">Đã trả {{ number_format($statusSummary['returned'] ?? 0) }}</span>
                                </div>
                            </div>

                            <div class="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                @foreach ([
                                    'active' => ['label' => 'Đang hiệu lực', 'description' => 'Còn hơn 7 ngày trước hạn'],
                                    'expiring_soon' => ['label' => 'Sắp hết hạn', 'description' => 'Còn tối đa 7 ngày'],
                                    'expired' => ['label' => 'Quá hạn', 'description' => 'Đã vượt mốc 45 ngày'],
                                    'returned' => ['label' => 'Đã trả', 'description' => 'Đã đánh dấu trả cho người gửi'],
                                ] as $key => $card)
                                    @php $isActive = $statusFilter === $key; @endphp
                                    <a
                                        href="{{ request()->fullUrlWithQuery(['status' => $key, 'page' => 1]) }}"
                                        class="rounded-2xl border p-4 transition hover:-translate-y-0.5 hover:shadow-lg {{ $isActive ? 'border-sky-300 bg-sky-50 ring-1 ring-sky-200' : $statusToneClasses['gray'] }}"
                                    >
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="text-xs font-bold uppercase tracking-[0.25em] text-slate-500">{{ $card['label'] }}</p>
                                                <p class="mt-2 text-3xl font-black text-slate-950">{{ number_format($statusSummary[$key] ?? 0) }}</p>
                                                <p class="mt-1 text-sm font-medium text-slate-600">{{ $card['description'] }}</p>
                                            </div>

                                            @if ($isActive)
                                                <span class="rounded-full bg-slate-950 px-2.5 py-1 text-[11px] font-bold uppercase tracking-[0.2em] text-white">Đang xem</span>
                                            @endif
                                        </div>
                                    </a>
                                @endforeach
                            </div>

                            <div class="mt-6 flex flex-wrap gap-2">
                                @foreach ($statusFilters as $key => $option)
                                    <a
                                        href="{{ request()->fullUrlWithQuery(['status' => $key, 'page' => 1]) }}"
                                        class="inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold transition {{ $statusFilter === $key ? 'bg-slate-950 text-white shadow-lg shadow-slate-950/15' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}"
                                    >
                                        {{ $option['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @elseif ($searchPerformed)
                        <section class="mt-6 rounded-[2rem] border border-rose-200 bg-rose-50 p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)] sm:p-8">
                            <p class="text-xs font-bold uppercase tracking-[0.35em] text-rose-600">CHƯA TÌM THẤY</p>
                            <h2 class="mt-3 text-2xl font-black text-slate-950">Không có dữ liệu phù hợp để hiển thị</h2>
                            <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-700">
                                Vui lòng kiểm tra lại mã nhà cung cấp hoặc số điện thoại. Nếu chưa có mã, hãy liên hệ cửa hàng để được hỗ trợ tra cứu.
                            </p>
                        </section>
                    @else
                        <section class="mt-6 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)] sm:p-8">
                            <div class="grid gap-4 lg:grid-cols-3">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                    <p class="text-sm font-bold text-slate-950">Bước 1</p>
                                    <p class="mt-2 text-sm leading-7 text-slate-700">Nhập mã nhà cung cấp hoặc số điện thoại vào form tra cứu bên trên.</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                    <p class="text-sm font-bold text-slate-950">Bước 2</p>
                                    <p class="mt-2 text-sm leading-7 text-slate-700">Xem danh sách sản phẩm của bạn được chia theo từng trạng thái rõ ràng.</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                    <p class="text-sm font-bold text-slate-950">Bước 3</p>
                                    <p class="mt-2 text-sm leading-7 text-slate-700">Kiểm tra món sắp hết hạn để chủ động liên hệ cửa hàng và xử lý kịp thời.</p>
                                </div>
                            </div>
                        </section>
                    @endif

                    @if ($supplier && $products)
                        <section class="mt-6 rounded-[2rem] border border-slate-200 bg-white p-0 shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
                            <div class="flex flex-col gap-3 border-b border-slate-200 p-6 sm:p-7 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.35em] text-slate-500">DANH SÁCH SẢN PHẨM</p>
                                    <h3 class="mt-2 text-2xl font-black tracking-tight text-slate-950">Theo dõi trạng thái từng sản phẩm</h3>
                                    <p class="mt-2 text-sm leading-7 text-slate-600">Bạn có thể lọc theo trạng thái để xem nhanh món nào còn hiệu lực, sắp hết hạn hoặc đã quá hạn.</p>
                                </div>

                                <div class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-bold text-slate-700">
                                    {{ $products->total() }} kết quả
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-[1100px] w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-[0.22em] text-slate-500">
                                        <tr>
                                            <th class="px-6 py-4">Mã SP</th>
                                            <th class="px-6 py-4">Tên sản phẩm</th>
                                            <th class="px-6 py-4">Danh mục</th>
                                            <th class="px-6 py-4">Phiếu</th>
                                            <th class="px-6 py-4">Ngày gửi</th>
                                            <th class="px-6 py-4">Hạn trả</th>
                                            <th class="px-6 py-4">Trạng thái</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @forelse ($products as $product)
                                            @php
                                                $dueDate = $product->consignmentDueDate();
                                                $toneClass = $statusToneClasses[$product->consignment_status_tone] ?? $statusToneClasses['gray'];
                                                $rowClass = $product->isReturned()
                                                    ? 'bg-slate-50'
                                                    : ($product->isConsignmentExpired() ? 'bg-rose-50/80' : ($product->isConsignmentExpiringSoon() ? 'bg-amber-50/80' : 'bg-white'));
                                            @endphp
                                            <tr class="{{ $rowClass }}">
                                                <td class="px-6 py-4 align-top font-semibold text-slate-950">#{{ $product->public_id_display }}</td>
                                                <td class="px-6 py-4 align-top">
                                                    <div class="font-semibold text-slate-950">{{ $product->name }}</div>
                                                    <div class="mt-1 text-xs font-medium text-slate-500">
                                                        {{ $product->sale_price ? number_format((float) $product->sale_price, 0, ',', '.') . ' đ' : '---' }}
                                                        @if ($product->quantity !== null)
                                                            <span class="mx-1">·</span>{{ $product->quantity }} chiếc
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 align-top text-slate-700">
                                                    {{ $product->category?->name ?? '---' }}
                                                </td>
                                                <td class="px-6 py-4 align-top text-slate-700">
                                                    #{{ $product->consignmentNote?->public_id_display ?? '---' }}
                                                </td>
                                                <td class="px-6 py-4 align-top text-slate-700">
                                                    {{ optional($product->consignmentNote?->sent_date)->format('d/m/Y') ?? '---' }}
                                                </td>
                                                <td class="px-6 py-4 align-top text-slate-700">
                                                    {{ optional($dueDate)->format('d/m/Y') ?? '---' }}
                                                </td>
                                                <td class="px-6 py-4 align-top">
                                                    <div class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $toneClass }}">
                                                        {{ $product->consignment_status_label }}
                                                    </div>
                                                    @if ($product->isReturned())
                                                        <p class="mt-2 text-xs text-slate-500">
                                                            Đã trả lúc {{ optional($product->returned_at)->format('d/m/Y H:i') }}
                                                            @if ($product->returner)
                                                                · {{ $product->returner->name }}
                                                            @endif
                                                        </p>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="px-6 py-12 text-center text-sm text-slate-500">
                                                    Không có sản phẩm ở trạng thái này.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="border-t border-slate-200 px-6 py-4 sm:px-7">
                                {{ $products->links() }}
                            </div>
                        </section>
                    @endif

                    <section id="dia-chi" class="scroll-mt-24 mt-6 rounded-[2rem] border border-slate-200 bg-slate-950 p-6 text-white shadow-[0_20px_60px_rgba(15,23,42,0.18)] sm:p-8">
                        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                            <div class="max-w-3xl">
                                <p class="text-xs font-bold uppercase tracking-[0.35em] text-cyan-300">ĐỊA CHỈ</p>
                                <h2 class="mt-4 text-2xl font-black text-white">Thông tin liên hệ và vị trí cửa hàng</h2>
                                <p class="mt-3 text-sm leading-7 text-slate-200">
                                    {{ $portalAddress }}
                                </p>
                                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                        <p class="text-xs font-bold uppercase tracking-[0.25em] text-slate-300">Hotline</p>
                                        <p class="mt-2 text-sm font-semibold text-white">{{ $portalHotline }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                        <p class="text-xs font-bold uppercase tracking-[0.25em] text-slate-300">Giờ mở cửa</p>
                                        <p class="mt-2 text-sm font-semibold text-white">{{ $portalHours }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-3">
                                @if ($hasPortalMap)
                                    <a
                                        href="{{ $portalMapUrl }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="inline-flex items-center justify-center rounded-full bg-cyan-300 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-200"
                                    >
                                        Xem bản đồ
                                    </a>
                                @endif
                                <a href="#home" class="inline-flex items-center justify-center rounded-full border border-white/10 bg-white/5 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                                    Quay lên đầu trang
                                </a>
                            </div>
                        </div>
                    </section>
                </div>
            </main>

            <nav class="fixed inset-x-3 bottom-3 z-50 grid grid-cols-3 gap-2 rounded-[1.6rem] border border-slate-200 bg-white/95 p-2 shadow-2xl shadow-slate-300/40 backdrop-blur-xl md:hidden">
                <a href="#home" class="flex flex-col items-center justify-center gap-1 rounded-2xl px-2 py-3 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                    <svg class="h-5 w-5 text-slate-900" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M10.707 1.293a1 1 0 00-1.414 0l-7 7A1 1 0 003 10h1v6a1 1 0 001 1h2a1 1 0 001-1v-4h2v4a1 1 0 001 1h2a1 1 0 001-1v-6h1a1 1 0 00.707-1.707l-7-7z" />
                    </svg>
                    <span>Home</span>
                </a>
                <a href="#tra-cuu" class="flex flex-col items-center justify-center gap-1 rounded-2xl px-2 py-3 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                    <svg class="h-5 w-5 text-slate-900" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M9 4a5 5 0 103.292 8.707l3.5 3.5a1 1 0 001.415-1.414l-3.5-3.5A5 5 0 009 4zm-3 5a3 3 0 116 0 3 3 0 01-6 0z" clip-rule="evenodd" />
                    </svg>
                    <span>Tra cứu</span>
                </a>
                <a href="#dia-chi" class="flex flex-col items-center justify-center gap-1 rounded-2xl px-2 py-3 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                    <svg class="h-5 w-5 text-slate-900" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M9.5 2a6.5 6.5 0 00-6.5 6.5c0 4.2 6.5 9.5 6.5 9.5s6.5-5.3 6.5-9.5A6.5 6.5 0 009.5 2zm0 9a2.5 2.5 0 100-5 2.5 2.5 0 000 5z" clip-rule="evenodd" />
                    </svg>
                    <span>Địa chỉ</span>
                </a>
            </nav>
        </div>
    </body>
</html>
