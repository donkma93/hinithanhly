<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'COHANKYGUI') }} - Tra cứu tồn kho ký gửi</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet" />
        @if (file_exists(public_path('build/manifest.json')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            @include('layouts.partials.no-build-assets')
        @endif

        <style>
            body {
                font-family: "Manrope", sans-serif;
                background:
                    radial-gradient(circle at 8% 0%, rgba(14, 165, 233, 0.08), transparent 28%),
                    radial-gradient(circle at 92% 4%, rgba(16, 185, 129, 0.07), transparent 26%),
                    #f8fafc;
            }
        </style>
    </head>
    <body class="min-h-screen text-slate-900 antialiased">
        @php
            $hasPortalMap = filled($portalMapUrl ?? '');
            $expiryToneClasses = [
                'success' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/80',
                'warning' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200/80',
                'danger' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200/80',
                'gray' => 'bg-slate-100 text-slate-600 ring-1 ring-slate-200/80',
            ];
            $expiryDotClasses = [
                'success' => 'bg-emerald-500',
                'warning' => 'bg-amber-500',
                'danger' => 'bg-rose-500',
                'gray' => 'bg-slate-400',
            ];
        @endphp

        <div id="home" class="relative min-h-screen pb-20 md:pb-8">
            <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/85 backdrop-blur-md">
                <div class="mx-auto flex h-14 max-w-5xl items-center justify-between gap-3 px-4 sm:px-6">
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-slate-900 to-sky-700 text-xs font-extrabold tracking-tight text-white shadow-sm">
                            CK
                        </div>
                        <div class="leading-tight">
                            <p class="text-xs font-extrabold uppercase tracking-[0.22em] text-slate-900">COHANKYGUI</p>
                            <p class="text-[11px] font-medium text-slate-500">Cổng tra cứu nhà cung cấp</p>
                        </div>
                    </a>

                    <div class="flex items-center gap-2">
                        <a
                            href="#dia-chi"
                            class="hidden items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900 sm:inline-flex"
                        >
                            <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.544l.062.029.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd" />
                            </svg>
                            Địa chỉ
                        </a>

                        @auth
                            <a
                                href="{{ route('dashboard') }}"
                                class="inline-flex items-center gap-1.5 rounded-full bg-slate-900 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-slate-800"
                            >
                                Vào Dashboard
                            </a>
                        @endauth
                    </div>
                </div>
            </header>

            <main class="mx-auto max-w-5xl space-y-5 px-4 py-5 sm:px-6 sm:py-7">
                {{-- Compact Search Hero --}}
                <section id="tra-cuu" class="scroll-mt-20 overflow-hidden rounded-2xl border border-slate-200/90 bg-gradient-to-br from-slate-900 via-slate-900 to-slate-800 p-5 text-white shadow-md sm:p-6">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-cyan-400/15 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.22em] text-cyan-300 ring-1 ring-cyan-400/25">
                                Tra cứu ký gửi
                            </span>
                            <h1 class="mt-2 text-xl font-extrabold tracking-tight text-white sm:text-2xl">
                                Tra cứu tồn kho nhà cung cấp
                            </h1>
                            <p class="mt-1 text-xs leading-relaxed text-slate-300 sm:text-sm">
                                Nhập số điện thoại đã đăng ký để xem doanh số, tồn kho, thời gian lưu kho và hạn ký gửi.
                            </p>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('home') }}" class="mt-4 space-y-3">
                        @if ($isMultiBranch ?? false)
                            <input type="hidden" name="branch" value="{{ $selectedBranch ?? 'all' }}">
                        @endif

                        <div>
                            <label for="phone" class="sr-only">Số điện thoại</label>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <div class="relative flex-1">
                                    <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-slate-400">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M2 3.5A1.5 1.5 0 013.5 2h1.148a1.5 1.5 0 011.465 1.175l.716 3.223a1.5 1.5 0 01-1.052 1.767l-.933.267c-.41.117-.643.555-.48.95a11.542 11.542 0 006.254 6.254c.395.163.833-.07.95-.48l.267-.933a1.5 1.5 0 011.767-1.052l3.223.716A1.5 1.5 0 0118 15.352V16.5a1.5 1.5 0 01-1.5 1.5H15c-1.149 0-2.263-.15-3.326-.43A13.022 13.022 0 012.43 8.326 13.019 13.019 0 012 5V3.5z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                    <input
                                        id="phone"
                                        type="tel"
                                        name="phone"
                                        value="{{ $phone }}"
                                        inputmode="numeric"
                                        autocomplete="tel"
                                        placeholder="Nhập số điện thoại đã đăng ký (VD: 0912345678)"
                                        class="h-11 w-full rounded-xl border-0 bg-white pl-10 pr-4 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-white/10 placeholder:font-normal placeholder:text-slate-400 focus:ring-2 focus:ring-cyan-400"
                                    >
                                </div>

                                <div class="flex gap-2">
                                    <button
                                        type="submit"
                                        class="inline-flex h-11 flex-1 items-center justify-center gap-1.5 rounded-xl bg-cyan-400 px-5 text-sm font-bold text-slate-950 shadow-sm transition hover:bg-cyan-300 sm:flex-none"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                                        </svg>
                                        Tra cứu
                                    </button>

                                    @if ($searchPerformed)
                                        <a
                                            href="{{ route('home') }}"
                                            class="inline-flex h-11 items-center justify-center rounded-xl border border-white/15 bg-white/10 px-4 text-xs font-semibold text-white transition hover:bg-white/15"
                                        >
                                            Xóa lọc
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if (($isMultiBranch ?? false) && ! empty($branchOptions ?? []))
                            <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                                <span class="mr-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Cơ sở:</span>
                                @foreach ($branchOptions as $option)
                                    @php
                                        $isActiveBranch = ($selectedBranch ?? 'all') === $option['key'];
                                        $branchUrlParams = array_filter([
                                            'phone' => $phone !== '' ? $phone : null,
                                            'branch' => $option['key'],
                                        ], fn ($value) => $value !== null);
                                    @endphp
                                    <a
                                        href="{{ route('home', $branchUrlParams) }}"
                                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition {{ $isActiveBranch ? 'bg-cyan-400 text-slate-950 shadow-sm' : 'bg-white/10 text-slate-200 hover:bg-white/15' }}"
                                    >
                                        <span>{{ $option['name'] }}</span>
                                        @if ($searchPerformed && $option['matched'])
                                            <span class="rounded-full px-1.5 py-0.5 text-[10px] font-bold tabular-nums {{ $isActiveBranch ? 'bg-slate-950/15 text-slate-950' : 'bg-emerald-400/20 text-emerald-200' }}">
                                                {{ number_format($option['total_stock']) }}
                                            </span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </form>

                    @if ($searchError)
                        <div class="mt-3 flex items-center gap-2 rounded-xl border border-rose-400/30 bg-rose-500/15 px-3.5 py-2.5 text-xs font-medium text-rose-100 sm:text-sm">
                            <svg class="h-4 w-4 shrink-0 text-rose-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ $searchError }}</span>
                        </div>
                    @endif
                </section>

                {{-- Lookup Results --}}
                @if (($matchedBranchResults ?? collect())->isNotEmpty())
                    <div class="space-y-4">
                        @foreach ($matchedBranchResults as $branchResult)
                            @php
                                $branchSupplier = $branchResult['supplier'];
                                $branchSummaries = $branchResult['inventory_summaries'];
                            @endphp

                            @if ($branchSummaries->isNotEmpty())
                                <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm">
                                    {{-- Compact Supplier Header + Unified Stat Strip --}}
                                    <div class="border-b border-slate-100 p-4 sm:p-5">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    @if ($isMultiBranch ?? false)
                                                        <span class="inline-flex items-center rounded-md bg-sky-50 px-2 py-0.5 text-[11px] font-bold text-sky-700 ring-1 ring-sky-200/80">
                                                            {{ $branchResult['branch_name'] }}
                                                        </span>
                                                    @endif
                                                    <h2 class="truncate text-lg font-extrabold tracking-tight text-slate-950 sm:text-xl">
                                                        {{ $branchSupplier->name }}
                                                    </h2>
                                                </div>

                                                @if ($branchSupplier->phone)
                                                    <div class="mt-1.5 flex flex-wrap items-center gap-2 text-xs text-slate-600">
                                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-700">
                                                            SĐT: {{ $branchSupplier->phone }}
                                                        </span>
                                                        <a
                                                            href="tel:{{ preg_replace('/\D+/', '', $branchSupplier->phone) }}"
                                                            class="inline-flex items-center gap-1 rounded-full bg-emerald-600 px-3 py-1 font-semibold text-white transition hover:bg-emerald-700"
                                                        >
                                                            <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                                <path fill-rule="evenodd" d="M2 3.5A1.5 1.5 0 013.5 2h1.148a1.5 1.5 0 011.465 1.175l.716 3.223a1.5 1.5 0 01-1.052 1.767l-.933.267c-.41.117-.643.555-.48.95a11.542 11.542 0 006.254 6.254c.395.163.833-.07.95-.48l.267-.933a1.5 1.5 0 011.767-1.052l3.223.716A1.5 1.5 0 0118 15.352V16.5a1.5 1.5 0 01-1.5 1.5H15c-1.149 0-2.263-.15-3.326-.43A13.022 13.022 0 012.43 8.326 13.019 13.019 0 012 5V3.5z" clip-rule="evenodd" />
                                                            </svg>
                                                            Gọi ngay
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- Unified Compact 3-Metric KPI Strip --}}
                                            <div class="grid grid-cols-3 divide-x divide-slate-200/80 overflow-hidden rounded-xl border border-slate-200/80 bg-slate-50/70">
                                                <div class="bg-emerald-50/70 px-3.5 py-2.5">
                                                    <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">Doanh số</p>
                                                    <p class="mt-0.5 text-sm font-extrabold tabular-nums text-emerald-800 sm:text-base">
                                                        {{ number_format((float) ($branchResult['total_revenue'] ?? 0), 0, ',', '.') }} ₫
                                                    </p>
                                                </div>
                                                <div class="px-3.5 py-2.5">
                                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">SL đã bán</p>
                                                    <p class="mt-0.5 text-sm font-extrabold tabular-nums text-sky-700 sm:text-base">
                                                        {{ number_format((int) ($branchResult['total_sold'] ?? 0)) }} <span class="text-[11px] font-semibold text-slate-400">SP</span>
                                                    </p>
                                                </div>
                                                <div class="px-3.5 py-2.5">
                                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">SL tồn</p>
                                                    <p class="mt-0.5 text-sm font-extrabold tabular-nums text-slate-900 sm:text-base">
                                                        {{ number_format((int) ($branchResult['total_stock'] ?? 0)) }} <span class="text-[11px] font-semibold text-slate-400">SP</span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Aggregate Consignment Rounds Summary Table (Numbers Only — No Product Details) --}}
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-slate-100 text-[13px]">
                                            <thead class="bg-slate-50/80 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                                <tr>
                                                    <th class="px-4 py-2.5 sm:px-5">Đợt ký gửi</th>
                                                    <th class="px-3 py-2.5 text-right whitespace-nowrap">SL đã bán</th>
                                                    <th class="px-3 py-2.5 text-right whitespace-nowrap">SL tồn</th>
                                                    <th class="px-3 py-2.5 text-right whitespace-nowrap">Doanh số</th>
                                                    <th class="px-3 py-2.5 text-right whitespace-nowrap">Lưu kho</th>
                                                    <th class="px-4 py-2.5 text-right whitespace-nowrap sm:px-5">Hết hạn</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100 bg-white">
                                                @foreach ($branchSummaries as $inventorySummary)
                                                    @php
                                                        $roundTone = $inventorySummary['expiry_tone'] ?? 'gray';
                                                        $roundToneClass = $expiryToneClasses[$roundTone] ?? $expiryToneClasses['gray'];
                                                        $roundDotClass = $expiryDotClasses[$roundTone] ?? $expiryDotClasses['gray'];
                                                    @endphp
                                                    <tr class="transition hover:bg-slate-50/70">
                                                        <td class="px-4 py-3 sm:px-5">
                                                            <p class="font-bold text-slate-900">Lần ký gửi {{ $inventorySummary['round'] }}</p>
                                                            <p class="mt-0.5 text-xs text-slate-500">Ngày gửi: {{ $inventorySummary['sent_date_label'] }}</p>
                                                        </td>
                                                        <td class="px-3 py-3 text-right font-bold tabular-nums {{ ($inventorySummary['sold_quantity'] ?? 0) > 0 ? 'text-sky-700' : 'text-slate-400' }}">
                                                            {{ number_format((int) ($inventorySummary['sold_quantity'] ?? 0)) }}
                                                        </td>
                                                        <td class="px-3 py-3 text-right font-bold tabular-nums text-slate-900">
                                                            {{ number_format((int) ($inventorySummary['stock_quantity'] ?? 0)) }}
                                                        </td>
                                                        <td class="px-3 py-3 text-right font-extrabold tabular-nums whitespace-nowrap {{ ($inventorySummary['total_revenue'] ?? 0) > 0 ? 'text-emerald-700' : 'text-slate-400' }}">
                                                            {{ number_format((float) ($inventorySummary['total_revenue'] ?? 0), 0, ',', '.') }} ₫
                                                        </td>
                                                        <td class="px-3 py-3 text-right text-xs font-semibold tabular-nums text-slate-600 whitespace-nowrap">
                                                            {{ $inventorySummary['storage_label'] }}
                                                        </td>
                                                        <td class="px-4 py-3 text-right whitespace-nowrap sm:px-5">
                                                            <div class="inline-flex flex-col items-end gap-0.5 sm:flex-row sm:items-center sm:gap-1.5">
                                                                @if (! empty($inventorySummary['due_date_label']))
                                                                    <span class="text-xs font-medium tabular-nums text-slate-500">{{ $inventorySummary['due_date_label'] }}</span>
                                                                @endif
                                                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $roundToneClass }}">
                                                                    <span class="h-1.5 w-1.5 rounded-full {{ $roundDotClass }}"></span>
                                                                    {{ $inventorySummary['expiry_label'] }}
                                                                </span>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </section>
                            @else
                                <section class="rounded-2xl border border-amber-200 bg-amber-50/70 p-4 sm:p-5">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if ($isMultiBranch ?? false)
                                            <span class="inline-flex items-center rounded-md bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-900 ring-1 ring-amber-200">
                                                {{ $branchResult['branch_name'] }}
                                            </span>
                                        @endif
                                        <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Chưa có hàng ký gửi</span>
                                    </div>
                                    <h2 class="mt-1.5 text-base font-extrabold text-slate-950 sm:text-lg">{{ $branchSupplier->name }} chưa có dữ liệu tồn kho</h2>
                                    <p class="mt-1 text-xs leading-relaxed text-slate-600 sm:text-sm">
                                        Chưa ghi nhận lần ký gửi nào cho số điện thoại này. Vui lòng liên hệ cửa hàng nếu bạn cần kiểm tra thêm.
                                    </p>
                                </section>
                            @endif
                        @endforeach

                        @if (($isMultiBranch ?? false) && ! empty($unmatchedBranchNames ?? []))
                            <div class="rounded-xl border border-slate-200/80 bg-white px-4 py-3 text-xs text-slate-600 shadow-sm sm:text-sm">
                                Không tìm thấy dữ liệu ký gửi cho số điện thoại này tại:
                                <span class="font-bold text-slate-900">{{ implode(', ', $unmatchedBranchNames) }}</span>.
                            </div>
                        @endif
                    </div>
                @elseif ($searchPerformed)
                    <section class="rounded-2xl border border-rose-200 bg-rose-50/70 p-4 sm:p-5">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-rose-600">Chưa tìm thấy</p>
                        <h2 class="mt-1 text-base font-extrabold text-slate-950 sm:text-lg">Không có dữ liệu phù hợp để hiển thị</h2>
                        <p class="mt-1 text-xs leading-relaxed text-slate-600 sm:text-sm">
                            Vui lòng kiểm tra lại số điện thoại đã đăng ký. Nếu chưa thấy thông tin của mình, hãy liên hệ cửa hàng để được hỗ trợ tra cứu.
                        </p>
                    </section>
                @endif

                {{-- Compact Light Contact & Store Locations Card --}}
                <section id="dia-chi" class="scroll-mt-20 rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm">
                    @if (($isMultiBranch ?? false) && count($storeLocations ?? []) > 1)
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-3">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-sky-600">Hệ thống cơ sở</p>
                                <h2 class="mt-0.5 text-base font-extrabold text-slate-950 sm:text-lg">Địa chỉ & thông tin liên hệ</h2>
                            </div>
                            <a href="#home" class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">
                                Lên đầu trang
                            </a>
                        </div>

                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            @foreach ($storeLocations as $location)
                                <div class="flex flex-col justify-between rounded-xl border border-slate-200/80 bg-slate-50/60 p-4">
                                    <div>
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="inline-flex items-center rounded-md bg-slate-900 px-2.5 py-0.5 text-xs font-bold text-white">
                                                {{ $location['name'] }}
                                            </span>
                                            @if (filled($location['map_url']))
                                                <a
                                                    href="{{ $location['map_url'] }}"
                                                    target="_blank"
                                                    rel="noopener"
                                                    class="inline-flex items-center gap-1 text-xs font-bold text-sky-700 hover:underline"
                                                >
                                                    Xem bản đồ
                                                    <span aria-hidden="true">→</span>
                                                </a>
                                            @endif
                                        </div>
                                        <p class="mt-2.5 text-xs leading-relaxed text-slate-700 sm:text-sm">{{ $location['address'] }}</p>
                                    </div>

                                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1.5 border-t border-slate-200/70 pt-2.5 text-xs text-slate-600">
                                        <span>Hotline: <strong class="font-semibold text-slate-900">{{ $location['hotline'] }}</strong></span>
                                        <span>Giờ mở cửa: <strong class="font-semibold text-slate-900">{{ $location['hours'] }}</strong></span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0 space-y-1.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-sky-600">Địa chỉ cửa hàng</span>
                                </div>
                                <p class="text-sm font-semibold text-slate-900">{{ $portalAddress }}</p>
                                <div class="flex flex-wrap items-center gap-x-5 gap-y-1 text-xs text-slate-600">
                                    <span>Hotline: <strong class="font-semibold text-slate-900">{{ $portalHotline }}</strong></span>
                                    <span>Giờ mở cửa: <strong class="font-semibold text-slate-900">{{ $portalHours }}</strong></span>
                                </div>
                            </div>

                            <div class="flex shrink-0 flex-wrap items-center gap-2">
                                @if ($hasPortalMap)
                                    <a
                                        href="{{ $portalMapUrl }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white transition hover:bg-slate-800"
                                    >
                                        Xem bản đồ
                                    </a>
                                @endif
                                <a href="#home" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                                    Lên đầu trang
                                </a>
                            </div>
                        </div>
                    @endif
                </section>
            </main>

            {{-- Compact Mobile Bottom Nav --}}
            <nav class="fixed inset-x-3 bottom-2.5 z-40 grid grid-cols-3 gap-1 rounded-2xl border border-slate-200/90 bg-white/95 p-1.5 shadow-lg shadow-slate-300/40 backdrop-blur-md md:hidden">
                <a href="#home" class="flex flex-col items-center justify-center gap-0.5 rounded-xl py-2 text-[11px] font-bold text-slate-700 transition hover:bg-slate-100">
                    <svg class="h-4 w-4 text-slate-900" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M10.707 1.293a1 1 0 00-1.414 0l-7 7A1 1 0 003 10h1v6a1 1 0 001 1h2a1 1 0 001-1v-4h2v4a1 1 0 001 1h2a1 1 0 001-1v-6h1a1 1 0 00.707-1.707l-7-7z" />
                    </svg>
                    <span>Home</span>
                </a>
                <a href="#tra-cuu" class="flex flex-col items-center justify-center gap-0.5 rounded-xl py-2 text-[11px] font-bold text-slate-700 transition hover:bg-slate-100">
                    <svg class="h-4 w-4 text-slate-900" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M9 4a5 5 0 103.292 8.707l3.5 3.5a1 1 0 001.415-1.414l-3.5-3.5A5 5 0 009 4zm-3 5a3 3 0 116 0 3 3 0 01-6 0z" clip-rule="evenodd" />
                    </svg>
                    <span>Tra cứu</span>
                </a>
                <a href="#dia-chi" class="flex flex-col items-center justify-center gap-0.5 rounded-xl py-2 text-[11px] font-bold text-slate-700 transition hover:bg-slate-100">
                    <svg class="h-4 w-4 text-slate-900" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M9.5 2a6.5 6.5 0 00-6.5 6.5c0 4.2 6.5 9.5 6.5 9.5s6.5-5.3 6.5-9.5A6.5 6.5 0 009.5 2zm0 9a2.5 2.5 0 100-5 2.5 2.5 0 000 5z" clip-rule="evenodd" />
                    </svg>
                    <span>Địa chỉ</span>
                </a>
            </nav>
        </div>
    </body>
</html>
