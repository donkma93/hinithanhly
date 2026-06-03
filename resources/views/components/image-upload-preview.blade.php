@props([
    'name',
    'label' => 'Ảnh sản phẩm',
    'currentUrl' => null,
    'helperText' => 'Bạn có thể chụp ảnh hoặc chọn từ thư viện thiết bị.',
    'maxUploadSizeBytes' => 536870912,
])

<div
    x-data="{
        previewUrl: @js($currentUrl),
        defaultPreviewUrl: @js($currentUrl),
        selectedFileSize: null,
        maxUploadSizeBytes: @js((int) $maxUploadSizeBytes),
        formatBytes(bytes) {
            if (!bytes && bytes !== 0) {
                return '---';
            }

            const units = ['B', 'KB', 'MB', 'GB'];
            let size = Number(bytes);
            let unitIndex = 0;

            while (size >= 1024 && unitIndex < units.length - 1) {
                size /= 1024;
                unitIndex += 1;
            }

            const precision = unitIndex === 0 ? 0 : 1;
            return `${size.toFixed(precision)} ${units[unitIndex]}`;
        },
        async handleChange(event) {
            const input = event.target;
            const file = input.files?.[0];

            if (!file) {
                this.previewUrl = this.defaultPreviewUrl;
                this.selectedFileSize = null;
                return;
            }

            this.previewUrl = URL.createObjectURL(file);
            this.selectedFileSize = file.size;
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
        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-xl file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800 sr-only"
        @change="handleChange($event)"
    >
    <p class="mt-2 text-xs text-gray-500">{{ $helperText }}</p>
    <p class="mt-1 text-xs text-gray-500">
        Dung lượng đã chọn:
        <span x-text="selectedFileSize !== null ? formatBytes(selectedFileSize) : 'Chưa chọn ảnh'"></span>
        <span class="mx-1">/</span>
        Tối đa: <span x-text="formatBytes(maxUploadSizeBytes)"></span>
    </p>

    <template x-if="previewUrl">
        <div class="mt-3 overflow-hidden rounded-2xl border border-gray-200 bg-gray-50">
            <img :src="previewUrl" alt="Xem trước ảnh" class="max-h-64 w-full object-contain">
        </div>
    </template>
</div>