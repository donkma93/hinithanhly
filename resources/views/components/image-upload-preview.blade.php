@props([
    'name',
    'label' => 'Ảnh sản phẩm',
    'currentUrl' => null,
    'helperText' => 'Trên điện thoại, trình duyệt có thể mở camera trực tiếp hoặc chọn ảnh từ bộ nhớ thiết bị.',
])

<div
    x-data="{
        previewUrl: @js($currentUrl),
        defaultPreviewUrl: @js($currentUrl),
        handleChange(event) {
            const file = event.target.files?.[0];

            if (!file) {
                this.previewUrl = this.defaultPreviewUrl;
                return;
            }

            this.previewUrl = URL.createObjectURL(file);
        },
    }"
>
    <label class="block text-sm font-medium text-gray-700">{{ $label }}</label>
    <input
        type="file"
        name="{{ $name }}"
        accept="image/*"
        capture="environment"
        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-xl file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800"
        @change="handleChange($event)"
    >
    <p class="mt-2 text-xs text-gray-500">{{ $helperText }}</p>

    <template x-if="previewUrl">
        <div class="mt-3 overflow-hidden rounded-2xl border border-gray-200 bg-gray-50">
            <img :src="previewUrl" alt="Xem trước ảnh" class="max-h-64 w-full object-contain">
        </div>
    </template>
</div>