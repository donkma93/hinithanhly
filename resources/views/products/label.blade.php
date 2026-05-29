<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-2xl font-semibold text-gray-900">In mã hàng</h2>
                <p class="text-sm text-gray-500">Trang in mã vạch cho sản phẩm {{ $product->name }}.</p>
            </div>
            <div class="no-print flex gap-2">
                <a href="{{ route('products.barcode', $product) }}" class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Tải SVG</a>
                <button type="button" onclick="window.print()" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">In trang này</button>
                <a href="{{ route('products.index') }}" class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Quay lại</a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <style>
                    @media print {
                        body {
                            background: #fff !important;
                        }

                        .no-print {
                            display: none !important;
                        }

                        .print-card {
                            box-shadow: none !important;
                            border: 1px solid #e5e7eb;
                        }
                    }
                </style>

                <div class="print-card grid gap-6 rounded-3xl border border-gray-200 p-6 lg:grid-cols-[1.2fr_0.8fr]">
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Mã hàng</p>
                            <h3 class="mt-2 text-3xl font-semibold text-gray-900">{{ $product->name }}</h3>
                            <p class="mt-2 text-sm text-gray-500">Mã sản phẩm #{{ $product->id }} · Nhà cung cấp #{{ $product->supplier_id }}</p>
                        </div>

                        <dl class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tên nhà cung cấp</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $product->supplier?->name ?? '---' }}</dd>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tồn kho</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $product->quantity }}</dd>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Giá tiền</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ number_format((float) $product->sale_price, 0, ',', '.') }} đ</dd>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Lần gửi</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $sendSummary['label'] }}</dd>
                            </div>
                        </dl>

                        <div class="rounded-2xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-100">
                            Mã vạch này chứa thông tin mã sản phẩm để hỗ trợ tra cứu nhanh.
                        </div>
                    </div>

                    <div class="flex flex-col items-center justify-center rounded-3xl bg-slate-950 p-6 text-center text-white">
                        <div class="rounded-2xl bg-white p-4 shadow-lg">
                            {!! $barcodeSvg !!}
                        </div>
                        <p class="mt-4 text-xs uppercase tracking-[0.2em] text-slate-300">{{ $barcodePayload }}</p>
                        <p class="mt-3 text-2xl font-semibold text-white">{{ number_format((float) $product->sale_price, 0, ',', '.') }} đ</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>