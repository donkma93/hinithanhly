<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">Sửa danh mục</h2>
            <p class="text-sm text-gray-500">Cập nhật tên và mô tả danh mục.</p>
            <p class="mt-1 text-xs font-medium uppercase tracking-wide text-gray-400">Mã công khai: #{{ $category->public_id }}</p>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-10xl px-3 sm:px-6 lg:px-10">
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <form method="POST" action="{{ route('categories.update', $category) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tên danh mục</label>
                        <input name="name" value="{{ old('name', $category->name) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" required>
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Mô tả</label>
                        <textarea name="description" rows="5" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900">{{ old('description', $category->description) }}</textarea>
                    </div>
                    <div class="flex items-center gap-3">
                        <button class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white">Cập nhật</button>
                        <a href="{{ route('categories.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Quay lại</a>
                    </div>
                    @can('categories.delete')
                        <div class="mt-6 border-t border-gray-200 pt-5">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-sm text-gray-500">Thao tác này sẽ đưa danh mục vào thùng rác thay vì xóa hẳn.</p>
                                <x-confirm-action
                                    :name="'delete-category-'.$category->public_id"
                                    :action="route('categories.destroy', $category)"
                                    title="Đưa danh mục vào thùng rác"
                                    message="Bạn có chắc chắn muốn đưa danh mục này vào thùng rác?"
                                    confirm-text="Đưa vào thùng rác"
                                    trigger-text="Đưa vào thùng rác"
                                    trigger-class="inline-flex items-center justify-center rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 hover:bg-red-100"
                                />
                            </div>
                        </div>
                    @endcan
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
