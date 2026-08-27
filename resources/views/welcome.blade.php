<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'HINITHANLYKYGUI') }} - Tra cứu tồn kho ký gửi</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800,900&display=swap" rel="stylesheet" />
        @if (file_exists(public_path('build/manifest.json')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            @include('layouts.partials.no-build-assets')
        @endif

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
                            <p class="mt-1 text-sm font-medium text-slate-600">Cổng tra cứu tồn kho dành cho người ký gửi</p>
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
                    <section>
                        <article id="tra-cuu" class="scroll-mt-24 rounded-[2rem] border border-slate-200 bg-slate-950 p-6 text-white shadow-[0_20px_60px_rgba(15,23,42,0.18)] sm:p-8 lg:p-10">
                            <p class="text-xs font-bold uppercase tracking-[0.35em] text-cyan-300">TRA CỨU TỒN KHO</p>
                            <h1 class="mt-4 text-3xl font-black text-white sm:text-4xl">Tra cứu tồn kho nhà cung cấp</h1>
                            <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-200 sm:text-base">
                                Nhập số điện thoại đã đăng ký để xem sản phẩm còn tồn theo từng lần ký gửi của bạn.
                            </p>

                            <form method="GET" action="{{ route('home') }}" class="mt-7 space-y-4">
                                <div>
                                    <label for="phone" class="mb-2 block text-sm font-semibold text-white">Số điện thoại</label>
                                    <div class="flex flex-col gap-3 sm:flex-row">
                                        <input
                                            id="phone"
                                            type="tel"
                                            name="phone"
                                            value="{{ $phone }}"
                                            inputmode="numeric"
                                            autocomplete="tel"
                                            placeholder="Nhập số điện thoại của bạn"
                                            class="min-w-0 flex-1 rounded-2xl border border-white/10 bg-white/95 px-4 py-3 text-sm font-medium text-slate-950 shadow-sm focus:border-cyan-300 focus:outline-none focus:ring-2 focus:ring-cyan-300/20"
                                        >
                                        <button
                                            type="submit"
                                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-cyan-300 px-5 py-3 text-sm font-bold text-slate-950 shadow-lg shadow-cyan-300/20 transition hover:bg-cyan-200 sm:min-w-[150px]"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M9 4a5 5 0 103.292 8.707l3.5 3.5a1 1 0 001.415-1.414l-3.5-3.5A5 5 0 009 4zm-3 5a3 3 0 116 0 3 3 0 01-6 0z" clip-rule="evenodd" />
                                            </svg>
                                            Tra cứu
                                        </button>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    @if ($searchPerformed)
                                        <a
                                            href="{{ route('home') }}"
                                            class="inline-flex items-center justify-center rounded-full border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10"
                                        >
                                            Xóa lọc
                                        </a>
                                    @endif
                                </div>
                            </form>

                            @if ($searchError)
                                <div class="mt-5 rounded-2xl border border-rose-300/30 bg-rose-400/10 px-4 py-3 text-sm font-medium text-rose-100">
                                    {{ $searchError }}
                                </div>
                            @endif

                        </article>
                    </section>

                    @if ($supplier && $inventorySummaries->isNotEmpty())
                        <section class="mt-6 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)] sm:p-8">
                            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                                <div class="max-w-3xl">
                                    <p class="text-xs font-bold uppercase tracking-[0.35em] text-emerald-600">KẾT QUẢ TRA CỨU</p>
                                    <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950">{{ $supplier->name }}</h2>
                                    @if ($supplier->phone)
                                        <div class="mt-3 flex flex-wrap items-center gap-3">
                                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-700">
                                                SĐT: {{ $supplier->phone }}
                                            </span>
                                            <a
                                                href="tel:{{ preg_replace('/\D+/', '', $supplier->phone) }}"
                                                class="inline-flex items-center justify-center rounded-full bg-emerald-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 transition hover:bg-emerald-600"
                                            >
                                                Gọi ngay
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-6 space-y-4">
                                @foreach ($inventorySummaries as $inventorySummary)
                                    <article class="overflow-hidden rounded-[1.5rem] border border-slate-200">
                                        <div class="flex flex-col gap-3 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <h3 class="text-lg font-black text-slate-950">Lần ký gửi {{ $inventorySummary['round'] }}</h3>
                                                <p class="mt-1 text-sm text-slate-600">Ngày gửi: {{ $inventorySummary['sent_date_label'] }}</p>
                                            </div>
                                            <div class="flex flex-wrap gap-2">
                                                <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-700 ring-1 ring-slate-200">
                                                    {{ number_format($inventorySummary['product_count']) }} mặt hàng
                                                </span>
                                                <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">
                                                    Tồn kho: {{ number_format($inventorySummary['stock_quantity']) }}
                                                </span>
                                            </div>
                                        </div>

                                        @if ($inventorySummary['products']->isNotEmpty())
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                                    <thead class="bg-white text-left text-xs font-bold uppercase tracking-[0.2em] text-slate-500">
                                                        <tr>
                                                            <th class="px-4 py-3">Mã hàng</th>
                                                            <th class="px-4 py-3">Sản phẩm</th>
                                                            <th class="px-4 py-3 text-right">Tồn kho</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100 bg-white">
                                                        @foreach ($inventorySummary['products'] as $product)
                                                            <tr>
                                                                <td class="px-4 py-4 font-semibold text-slate-700">#{{ $product->public_id_display }}</td>
                                                                <td class="px-4 py-4 font-semibold text-slate-950">{{ $product->name }}</td>
                                                                <td class="px-4 py-4 text-right font-black text-emerald-700">{{ number_format($product->quantity) }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <p class="bg-white px-5 py-5 text-sm font-medium text-slate-500">Lần ký gửi này hiện không còn sản phẩm tồn kho.</p>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @elseif ($supplier)
                        <section class="mt-6 rounded-[2rem] border border-amber-200 bg-amber-50 p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)] sm:p-8">
                            <p class="text-xs font-bold uppercase tracking-[0.35em] text-amber-700">CHƯA CÓ HÀNG KÝ GỬI</p>
                            <h2 class="mt-3 text-2xl font-black text-slate-950">{{ $supplier->name }} chưa có dữ liệu tồn kho</h2>
                            <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-700">
                                Chưa ghi nhận lần ký gửi nào cho số điện thoại này. Vui lòng liên hệ cửa hàng nếu bạn cần kiểm tra thêm.
                            </p>
                        </section>
                    @elseif ($searchPerformed)
                        <section class="mt-6 rounded-[2rem] border border-rose-200 bg-rose-50 p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)] sm:p-8">
                            <p class="text-xs font-bold uppercase tracking-[0.35em] text-rose-600">CHƯA TÌM THẤY</p>
                            <h2 class="mt-3 text-2xl font-black text-slate-950">Không có dữ liệu phù hợp để hiển thị</h2>
                            <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-700">
                                Vui lòng kiểm tra lại số điện thoại đã đăng ký. Nếu chưa thấy thông tin của mình, hãy liên hệ cửa hàng để được hỗ trợ tra cứu.
                            </p>
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
