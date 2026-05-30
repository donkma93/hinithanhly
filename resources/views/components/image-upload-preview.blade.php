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
        async handleChange(event) {
            const input = event.target;
            const file = input.files?.[0];

            if (!file) {
                this.previewUrl = this.defaultPreviewUrl;
                return;
            }

            try {
                const compressedBlob = await this.compressImage(file, 1200, 0.6);

                // Create a File from the blob and replace the input files so server receives optimized file
                const ext = compressedBlob.type === 'image/webp' ? '.webp' : '.jpg';
                const newFile = new File([compressedBlob], file.name.replace(/\.[^.]+$/, ext), { type: compressedBlob.type });
                const dt = new DataTransfer();
                dt.items.add(newFile);
                input.files = dt.files;

                this.previewUrl = URL.createObjectURL(newFile);
            } catch (err) {
                // On failure, fall back to original file preview
                this.previewUrl = URL.createObjectURL(file);
            }
        },

        compressImage(file, maxSize, quality) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => {
                    const img = new Image();
                    img.onload = () => {
                        try {
                            let width = img.width;
                            let height = img.height;
                            const scale = Math.min(1, maxSize / Math.max(width, height));
                            width = Math.max(1, Math.round(width * scale));
                            height = Math.max(1, Math.round(height * scale));

                            const canvas = document.createElement('canvas');
                            canvas.width = width;
                            canvas.height = height;
                            const ctx = canvas.getContext('2d');
                            ctx.drawImage(img, 0, 0, width, height);

                            // Prefer WebP when available, fallback to JPEG
                            const mime = 'image/webp';
                            if (canvas.toBlob) {
                                canvas.toBlob((blob) => {
                                    if (!blob) {
                                        reject(new Error('Compression failed'));
                                        return;
                                    }
                                    resolve(blob);
                                }, mime, quality);
                            } else {
                                // older browsers: try dataURL conversion
                                const dataUrl = canvas.toDataURL('image/jpeg', quality);
                                const arr = dataUrl.split(',');
                                const bstr = atob(arr[1]);
                                let n = bstr.length;
                                const u8arr = new Uint8Array(n);
                                while (n--) {
                                    u8arr[n] = bstr.charCodeAt(n);
                                }
                                resolve(new Blob([u8arr], { type: 'image/jpeg' }));
                            }
                        } catch (e) {
                            reject(e);
                        }
                    };
                    img.onerror = reject;
                    img.src = reader.result;
                };
                reader.onerror = reject;
                reader.readAsDataURL(file);
            });
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