@props([
    'name',
    'label' => 'Ảnh sản phẩm',
    'currentUrl' => null,
    'helperText' => 'Bạn có thể chụp ảnh hoặc chọn từ thư viện thiết bị.',
])

<div
    x-data="{
        previewUrl: @js($currentUrl),
        defaultPreviewUrl: @js($currentUrl),
        async handleChange(event) {
            const input = event.target;
            const file = input.files?.[0];

            if (!file) {
                this.previewUrl = this.defaultPreviewUrl;
                return;
            }

            this.previewUrl = URL.createObjectURL(file);
        },
        startCamera() {
            const cameraInput = document.createElement('input');
            cameraInput.type = 'file';
            cameraInput.accept = 'image/*';
            cameraInput.capture = 'environment';
            cameraInput.style.display = 'none';

            cameraInput.onchange = (e) => {
                const file = e.target.files?.[0];
                if (!file) {
                    document.body.removeChild(cameraInput);
                    return;
                }

                const dt = new DataTransfer();
                dt.items.add(file);

                const visible = $refs.fileInput;
                if (visible) {
                    visible.files = dt.files;
                    this.handleChange({ target: visible });
                }

                document.body.removeChild(cameraInput);
            };

            document.body.appendChild(cameraInput);
            cameraInput.click();
        },
    }"
    x-init
>
    <label class="block text-sm font-medium text-gray-700">{{ $label }}</label>

    <div class="mt-2 flex gap-2">
        <button type="button" @click="startCamera()" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">
            Chụp ảnh
        </button>
        <label for="{{ $name }}" class="inline-flex items-center gap-2 rounded-lg bg-white border px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 cursor-pointer">
            Chọn từ thư viện
        </label>
    </div>

    <input
        x-ref="fileInput"
        id="{{ $name }}"
        type="file"
        name="{{ $name }}"
        accept="image/*"
        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-xl file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800 hidden"
        @change="handleChange($event)"
    >
    <p class="mt-2 text-xs text-gray-500">{{ $helperText }}</p>

    <template x-if="previewUrl">
        <div class="mt-3 overflow-hidden rounded-2xl border border-gray-200 bg-gray-50">
            <img :src="previewUrl" alt="Xem trước ảnh" class="max-h-64 w-full object-contain">
        </div>
    </template>
</div>