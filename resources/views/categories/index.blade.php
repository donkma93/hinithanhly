<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-gray-900">Danh mục</h2>
                <p class="text-sm text-gray-500">Tạo và quản lý tên danh mục sản phẩm.</p>
            </div>
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
                    @canany(['categories.create', 'categories.manage'])
                        <h3 class="text-lg font-semibold text-gray-900">Thêm danh mục</h3>
                        <form method="POST" action="{{ route('categories.store') }}" class="mt-4 space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tên danh mục</label>
                                <input name="name" value="{{ old('name') }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Mô tả</label>
                                <textarea name="description" rows="4" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900">{{ old('description') }}</textarea>
                                @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <button class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Lưu danh mục</button>
                        </form>
                    @else
                        <h3 class="text-lg font-semibold text-gray-900">Thêm danh mục</h3>
                        <p class="mt-3 text-sm text-gray-500">Bạn chỉ có quyền xem danh mục.</p>
                    @endcanany
                </div>

                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200 lg:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-lg font-semibold text-gray-900">Danh sách</h3>
                        <form method="GET" action="{{ route('categories.index') }}" class="flex items-center gap-2">
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
                                    <th class="py-3 pr-4">Mô tả</th>
                                    <th class="py-3 pr-4">Trạng thái</th>
                                    <th class="py-3 pr-4"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($categories as $category)
                                    <tr>
                                        <td class="py-3 pr-4 font-medium text-slate-900">#{{ $category->public_id }}</td>
                                        <td class="py-3 pr-4 font-medium text-gray-900">{{ $category->name }}</td>
                                        <td class="py-3 pr-4 text-gray-600">{{ $category->description ?: '---' }}</td>
                                        <td class="py-3 pr-4 text-gray-600">{{ $category->is_active ? 'Đang dùng' : 'Tắt' }}</td>
                                        <td class="py-3 pr-4 text-right">
                                            @canany(['categories.update', 'categories.manage'])
                                                <a href="{{ route('categories.edit', $category) }}" class="text-slate-900 hover:underline">Sửa</a>
                                            @endcanany
                                            @canany(['categories.delete', 'categories.manage'])
                                                <span class="ms-4 inline-block align-middle">
                                                    <x-confirm-action
                                                        :name="'delete-category-'.$category->public_id"
                                                        :action="route('categories.destroy', $category)"
                                                        title="Xoá danh mục"
                                                        message="Bạn có chắc chắn muốn xoá danh mục này? Hành động này không thể hoàn tác."
                                                        confirm-text="Xoá"
                                                        trigger-text="Xoá"
                                                    />
                                                </span>
                                            @endcanany
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="py-8 text-center text-gray-500">Chưa có danh mục nào.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $categories->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
