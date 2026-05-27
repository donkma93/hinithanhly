<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-2xl font-semibold text-gray-900">In Mã hàng</h2>
                <p class="text-sm text-gray-500">Chọn nhiều sản phẩm, rồi bấm In Mã hàng để tạo tem QR hàng loạt.</p>
            </div>
            <div class="no-print flex gap-2">
                <span class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-800">Bấm In Mã hàng để mở trang tem, sau đó chọn máy in trong hộp thoại in của trình duyệt</span>
                <a href="{{ route('products.index') }}" class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Sản phẩm</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="no-print mb-6 rounded-3xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
                <form method="GET" action="{{ route('product-labels.index') }}" class="flex flex-wrap items-center gap-3">
                    <x-per-page-select :value="request('per_page', 24)" />
                    <input name="term" value="{{ request('term') }}" class="w-full max-w-md rounded-xl border-gray-300 text-sm focus:border-slate-900 focus:ring-slate-900" placeholder="Tìm theo mã, tên sản phẩm, nhà cung cấp">
                    <button class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white">Lọc</button>
                </form>
            </div>

            <form method="POST" action="{{ route('product-labels.print') }}" target="_blank" class="space-y-4" id="label-print-form">
                @csrf
                <div class="no-print rounded-3xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
                    <div class="flex flex-wrap items-center gap-3">
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                            <input type="checkbox" id="select-all-labels" class="rounded border-gray-300 text-slate-900 focus:ring-slate-900">
                            Chọn tất cả trên trang
                        </label>
                        <button type="submit" class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white">In mã hàng</button>
                    </div>
                </div>

                @if ($products->isEmpty())
                    <div class="rounded-3xl bg-white p-10 text-center text-gray-500 shadow-sm ring-1 ring-gray-200">
                        Chưa có sản phẩm nào để in.
                    </div>
                @else
                    <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-gray-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                                    <tr>
                                        <th class="w-12 px-4 py-3">
                                            <span class="sr-only">Chọn</span>
                                        </th>
                                        <th class="px-4 py-3">Ảnh</th>
                                        <th class="px-4 py-3">Mã</th>
                                        <th class="px-4 py-3">Tên sản phẩm</th>
                                        <th class="px-4 py-3">Nhà cung cấp</th>
                                        <th class="px-4 py-3">Giá</th>
                                        <th class="px-4 py-3">Tồn kho</th>
                                        <th class="px-4 py-3">Lần gửi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($products as $product)
                                        <tr>
                                            <td class="px-4 py-3">
                                                <input type="checkbox" name="ids[]" value="{{ $product->id }}" class="label-checkbox rounded border-gray-300 text-slate-900 focus:ring-slate-900">
                                            </td>
                                            <td class="px-4 py-3">
                                                @if ($product->image_path)
                                                    <div class="w-16 h-16 overflow-hidden rounded">
                                                        <img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                                    </div>
                                                @else
                                                    <div class="w-16 h-16 bg-gray-100 flex items-center justify-center text-xs text-gray-400 rounded">No</div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 font-medium text-slate-900">#{{ $product->id }}</td>
                                            <td class="px-4 py-3 text-gray-900">{{ $product->name }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $product->supplier?->name ?? '---' }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ number_format((float) $product->sale_price, 0, ',', '.') }} đ</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $product->quantity }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $product->send_summary ?? '---' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="no-print">{{ $products->links() }}</div>
                @endif
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const selectAll = document.getElementById('select-all-labels');
                const checkboxes = Array.from(document.querySelectorAll('.label-checkbox'));
                const form = document.getElementById('label-print-form');

                if (selectAll) {
                    selectAll.addEventListener('change', () => {
                        checkboxes.forEach((checkbox) => {
                            checkbox.checked = selectAll.checked;
                        });
                    });
                }

                if (form) {
                    form.addEventListener('submit', (event) => {
                        const selected = checkboxes.some((checkbox) => checkbox.checked);

                        if (!selected) {
                            event.preventDefault();
                            alert('Vui lòng chọn ít nhất 1 sản phẩm để in mã hàng.');
                        }
                    });
                }
            });
        </script>
    @endpush
</x-app-layout>