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
                            <p class="mt-1 text-sm font-medium text-slate-600">Cổng tra cứu doanh số & thanh toán cho nhà cung cấp</p>
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
                                Tra cứu doanh số
                            </span>

                            <h1 class="mt-5 max-w-3xl text-4xl font-black tracking-tight text-slate-950 sm:text-5xl lg:text-6xl lg:leading-[0.95]">
                                Nhà cung cấp xem ngay tình trạng thanh toán và doanh số của mình
                            </h1>

                            <p class="mt-4 max-w-3xl text-base leading-8 text-slate-700 sm:text-lg">
                                Nhập mã nhà cung cấp hoặc số điện thoại để kiểm tra doanh số bán hàng và tình trạng thanh toán trong tháng.
                            </p>

                            <div class="mt-6 flex flex-wrap gap-3 text-sm font-semibold text-slate-700">
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2">Cập nhật nhanh chóng</span>
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2">Tra cứu theo tháng</span>
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2">Dữ liệu đồng bộ theo hệ thống</span>
                            </div>

                            <div class="mt-8 grid gap-4 sm:grid-cols-3">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-slate-500">Nhanh</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">Tra cứu bằng mã NCC hoặc số điện thoại</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-slate-500">Rõ ràng</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">Thông tin thanh toán hiển thị chi tiết</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-slate-500">Gọn</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">Có nút mobile Home, Tra cứu, Địa chỉ</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[2rem] border border-slate-200 bg-gradient-to-br from-slate-900 to-slate-800 p-6 text-black shadow-[0_20px_60px_rgba(15,23,42,0.18)] sm:p-8 lg:col-span-1">
                            <p class="text-xs font-bold uppercase tracking-[0.35em] text-cyan-300">THÔNG BÁO</p>
                            <h2 class="mt-4 text-2xl font-black text-white">Kiểm tra theo mã NCC</h2>
                            <div class="mt-5 space-y-4 text-sm leading-7 text-slate-200">
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                    <p class="font-semibold text-white">1. Tra cứu bằng mã NCC</p>
                                    <p class="mt-1">Nhập đúng mã giống trên phiếu hoặc nhãn để tìm đúng dữ liệu.</p>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                    <p class="font-semibold text-white">2. Tra cứu bằng số điện thoại</p>
                                    <p class="mt-1">Nếu cần, có thể dùng số điện thoại liên hệ đã đăng ký.</p>
                                </div>
                            </div>
                        </div>

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

                                <div>
                                    <label for="month" class="mb-2 block text-sm font-semibold text-slate-800">Tháng tra cứu</label>
                                    <input
                                        id="month"
                                        type="month"
                                        name="month"
                                        value="{{ $selectedMonth }}"
                                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-950 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/15"
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

                    @if ($supplier && $paymentSummary)
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
                            </div>

                            <div class="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-slate-500">Kỳ doanh số</p>
                                    <p class="mt-2 text-3xl font-black text-slate-950">{{ $paymentSummary['period_label'] }}</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-slate-500">Sản phẩm đã bán</p>
                                    <p class="mt-2 text-3xl font-black text-slate-950">{{ number_format($paymentSummary['units_sold']) }}</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-slate-500">Số tiền cần thanh toán</p>
                                    <p class="mt-2 text-3xl font-black text-sky-600">{{ number_format($paymentSummary['payable_amount'], 0, ',', '.') }} đ</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 {{ $paymentSummary['status'] === 'paid' ? 'bg-emerald-50 border-emerald-200' : 'bg-amber-50 border-amber-200' }} p-4">
                                    <p class="text-xs font-bold uppercase tracking-[0.25em] {{ $paymentSummary['status'] === 'paid' ? 'text-emerald-600' : 'text-amber-600' }}">Trạng thái</p>
                                    <p class="mt-2 text-3xl font-black {{ $paymentSummary['status'] === 'paid' ? 'text-emerald-700' : 'text-amber-700' }}">{{ $paymentSummary['status_label'] }}</p>
                                </div>
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
                                    <p class="mt-2 text-sm leading-7 text-slate-700">Chọn tháng bạn muốn tra cứu doanh số và thanh toán.</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                    <p class="text-sm font-bold text-slate-950">Bước 3</p>
                                    <p class="mt-2 text-sm leading-7 text-slate-700">Xem ngay số lượng sản phẩm bán được và số tiền cần thanh toán của kỳ đó.</p>
                                </div>
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
