<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">Sản phẩm</h2>
            <p class="text-sm text-gray-500">Thêm sản phẩm bằng file ảnh hoặc camera từ thiết bị di động.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200 lg:col-span-1">
                    @canany(['products.create', 'products.manage'])
                        <h3 class="text-lg font-semibold text-gray-900">Thêm sản phẩm</h3>
                        <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Phiếu ký gửi</label>
                                <select name="consignment_note_id" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                                    <option value="">-- Chọn phiếu --</option>
                                    @foreach ($consignments as $consignment)
                                        <option value="{{ $consignment->id }}" @selected(old('consignment_note_id') == $consignment->id)>#{{ $consignment->public_id }} - {{ $consignment->sent_date?->format('d/m/Y') }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nhà cung cấp</label>
                                    <select name="supplier_id" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                                        <option value="">-- Chọn NCC --</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>#{{ $supplier->public_id }} - {{ $supplier->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Danh mục</label>
                                    <select name="category_id" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                                        <option value="">-- Chọn danh mục --</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>#{{ $category->public_id }} - {{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tên sản phẩm</label>
                                <input name="name" value="{{ old('name') }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Giá bán</label>
                                    <input type="number" step="0.01" name="sale_price" value="{{ old('sale_price', 0) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Số lượng</label>
                                    <input type="number" min="1" name="quantity" value="{{ old('quantity', 1) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Ảnh / chọn file</label>
                                <input type="file" name="image" accept="image/*" capture="environment" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-xl file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Ghi chú</label>
                                <textarea name="description" rows="4" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900">{{ old('description') }}</textarea>
                            </div>
                            <button class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Lưu sản phẩm</button>
                        </form>
                    @else
                        <h3 class="text-lg font-semibold text-gray-900">Thêm sản phẩm</h3>
                        <p class="mt-3 text-sm text-gray-500">Bạn chỉ có quyền xem sản phẩm.</p>
                    @endcanany
                </div>

                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200 lg:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-lg font-semibold text-gray-900">Danh sách</h3>
                        <form method="GET" action="{{ route('products.index') }}" class="flex items-center gap-2">
                            <input name="public_id" value="{{ request('public_id') }}" class="w-64 rounded-xl border-gray-300 text-sm focus:border-slate-900 focus:ring-slate-900" placeholder="Tìm bằng mã công khai">
                            <button class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white">Tìm</button>
                        </form>
                    </div>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="py-3 pr-4">Mã</th>
                                    <th class="py-3 pr-4">Tên</th>
                                    <th class="py-3 pr-4">Danh mục</th>
                                    <th class="py-3 pr-4">NCC</th>
                                    <th class="py-3 pr-4">Giá</th>
                                    <th class="py-3 pr-4">SL</th>
                                    <th class="py-3 pr-4"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($products as $product)
                                    <tr>
                                        <td class="py-3 pr-4 font-medium text-slate-900">#{{ $product->public_id }}</td>
                                        <td class="py-3 pr-4 font-medium text-gray-900">{{ $product->name }}</td>
                                        <td class="py-3 pr-4 text-gray-600">{{ $product->category?->name ?? '---' }}</td>
                                        <td class="py-3 pr-4 text-gray-600">{{ $product->supplier?->name ?? '---' }}</td>
                                        <td class="py-3 pr-4 text-gray-600">{{ number_format((float) $product->sale_price, 0, ',', '.') }}</td>
                                        <td class="py-3 pr-4 text-gray-600">{{ $product->quantity }}</td>
                                        <td class="py-3 pr-4 text-right">
                                            @canany(['products.update', 'products.manage'])
                                                <a href="{{ route('products.edit', $product) }}" class="text-slate-900 hover:underline">Sửa</a>
                                            @endcanany
                                            @canany(['products.delete', 'products.manage'])
                                                <span class="ms-4 inline-block align-middle">
                                                    <x-confirm-action
                                                        :name="'delete-product-'.$product->public_id"
                                                        :action="route('products.destroy', $product)"
                                                        title="Xoá sản phẩm"
                                                        message="Bạn có chắc chắn muốn xoá sản phẩm này?"
                                                        confirm-text="Xoá"
                                                        trigger-text="Xoá"
                                                    />
                                                </span>
                                            @endcanany
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="py-8 text-center text-gray-500">Chưa có sản phẩm nào.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $products->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
