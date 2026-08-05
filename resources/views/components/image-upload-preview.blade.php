@props([
    'name',
    'label' => 'Ảnh sản phẩm',
    'currentUrl' => null,
    'helperText' => 'Bạn có thể chụp ảnh hoặc chọn từ thư viện thiết bị.',
    'maxUploadSizeBytes' => 536870912,
])

@php($cameraInputName = 'camera_' . $name)
@php($galleryInputId = $name . '-' . uniqid())
@php($cameraInputId = $cameraInputName . '-' . uniqid())

<div
    x-data="{
        previewUrl: @js($currentUrl),
        defaultPreviewUrl: @js($currentUrl),
        objectPreviewUrl: null,
        selectedFileSize: null,
        maxUploadSizeBytes: @js((int) $maxUploadSizeBytes),
        cameraModalOpen: false,
        cameraBusy: false,
        cameraError: '',
        cameraStream: null,
        cameraRequestId: 0,
        init() {
            const form = this.$root.closest('form');

            if (form) {
                form.addEventListener('reset', () => {
                    this.clearInput(this.$refs.galleryInput);
                    this.clearInput(this.$refs.cameraInput);
                    this.closeCameraModal();
                    this.resetPreview();
                });
            }

            window.addEventListener('beforeunload', () => this.stopCameraStream());
        },
        formatBytes(bytes) {
            if (! bytes && bytes !== 0) {
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
        clearInput(input) {
            if (input) {
                input.value = '';
            }
        },
        revokePreviewUrl() {
            if (this.objectPreviewUrl) {
                URL.revokeObjectURL(this.objectPreviewUrl);
                this.objectPreviewUrl = null;
            }
        },
        resetPreview() {
            this.revokePreviewUrl();
            this.previewUrl = this.defaultPreviewUrl;
            this.selectedFileSize = null;
        },
        handleChange(event, source) {
            const input = event.target;
            const file = input.files?.[0];
            const otherInput = source === 'camera' ? this.$refs.galleryInput : this.$refs.cameraInput;

            if (! file) {
                if (! otherInput?.files?.length) {
                    this.resetPreview();
                }

                return;
            }

            this.clearInput(otherInput);
            this.revokePreviewUrl();
            this.objectPreviewUrl = URL.createObjectURL(file);
            this.previewUrl = this.objectPreviewUrl;
            this.selectedFileSize = file.size;
        },
        prefersDesktopCamera() {
            const userAgent = navigator.userAgent || '';
            const isMobileDevice = /Android|iPhone|iPad|iPod|IEMobile|Opera Mini/i.test(userAgent);
            const hasCoarsePointer = window.matchMedia ? window.matchMedia('(pointer: coarse)').matches : false;

            return ! isMobileDevice && ! hasCoarsePointer;
        },
        supportsDesktopCamera() {
            return Boolean(window.isSecureContext && navigator.mediaDevices?.getUserMedia);
        },
        async startCamera() {
            this.cameraError = '';

            if (! this.prefersDesktopCamera() || ! this.supportsDesktopCamera()) {
                this.$refs.cameraInput?.click();
                return;
            }

            this.cameraModalOpen = true;
            this.$nextTick(() => {
                this.openDesktopCamera();
            });
        },
        async openDesktopCamera() {
            if (this.cameraBusy) {
                return;
            }

            const requestId = ++this.cameraRequestId;

            this.cameraBusy = true;
            this.cameraError = '';
            this.stopCameraStream();

            const constraintsList = [
                {
                    video: {
                        facingMode: { ideal: 'environment' },
                        width: { ideal: 1920 },
                        height: { ideal: 1080 },
                    },
                    audio: false,
                },
                {
                    video: true,
                    audio: false,
                },
            ];

            let lastError = null;

            for (const constraints of constraintsList) {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia(constraints);
                    const video = this.$refs.cameraVideo;

                    if (requestId !== this.cameraRequestId || ! this.cameraModalOpen) {
                        stream.getTracks().forEach((track) => track.stop());
                        this.cameraBusy = false;
                        return;
                    }

                    this.cameraStream = stream;

                    if (video) {
                        video.srcObject = stream;
                        await video.play();
                    }

                    this.cameraBusy = false;
                    return;
                } catch (error) {
                    lastError = error;
                }
            }

            if (requestId !== this.cameraRequestId) {
                return;
            }

            this.cameraBusy = false;
            this.cameraError = this.resolveCameraErrorMessage(lastError);
        },
        resolveCameraErrorMessage(error) {
            switch (error?.name) {
                case 'NotAllowedError':
                case 'PermissionDeniedError':
                    return 'Trình duyệt đang chặn quyền dùng camera. Vui lòng cho phép truy cập camera rồi thử lại.';
                case 'NotFoundError':
                case 'DevicesNotFoundError':
                    return 'Không tìm thấy camera trên máy tính này.';
                case 'NotReadableError':
                case 'TrackStartError':
                    return 'Camera đang được ứng dụng khác sử dụng. Vui lòng đóng ứng dụng đó rồi thử lại.';
                case 'OverconstrainedError':
                    return 'Không thể khởi động camera với cấu hình hiện tại. Vui lòng thử lại.';
                default:
                    return 'Không thể kết nối camera lúc này. Vui lòng thử lại.';
            }
        },
        stopCameraStream() {
            if (this.cameraStream) {
                this.cameraStream.getTracks().forEach((track) => track.stop());
                this.cameraStream = null;
            }

            const video = this.$refs.cameraVideo;

            if (video) {
                video.pause();
                video.srcObject = null;
            }
        },
        closeCameraModal() {
            this.cameraRequestId += 1;
            this.cameraModalOpen = false;
            this.cameraBusy = false;
            this.cameraError = '';
            this.stopCameraStream();
        },
        async captureDesktopPhoto() {
            const video = this.$refs.cameraVideo;
            const canvas = this.$refs.cameraCanvas;

            if (! video || ! canvas || ! video.videoWidth || ! video.videoHeight) {
                this.cameraError = 'Camera chưa sẵn sàng để chụp ảnh.';
                return;
            }

            let width = video.videoWidth;
            let height = video.videoHeight;
            const maxDimension = 1600;

            if (Math.max(width, height) > maxDimension) {
                const scale = maxDimension / Math.max(width, height);
                width = Math.round(width * scale);
                height = Math.round(height * scale);
            }

            canvas.width = width;
            canvas.height = height;

            const context = canvas.getContext('2d');

            if (! context) {
                this.cameraError = 'Không thể xử lý ảnh vừa chụp.';
                return;
            }

            context.drawImage(video, 0, 0, width, height);

            const blob = await new Promise((resolve) => {
                canvas.toBlob(resolve, 'image/jpeg', 0.9);
            });

            if (! blob) {
                this.cameraError = 'Không thể tạo ảnh từ camera.';
                return;
            }

            const file = new File([blob], `camera-capture-${Date.now()}.jpg`, {
                type: 'image/jpeg',
                lastModified: Date.now(),
            });

            if (typeof DataTransfer === 'undefined') {
                this.cameraError = 'Trình duyệt hiện tại chưa hỗ trợ lưu trực tiếp ảnh từ webcam. Vui lòng dùng Chọn từ thư viện.';
                return;
            }

            const dataTransfer = new DataTransfer();

            dataTransfer.items.add(file);

            if (this.$refs.cameraInput) {
                this.$refs.cameraInput.files = dataTransfer.files;
            }

            if (! this.$refs.cameraInput?.files?.length) {
                this.cameraError = 'Trình duyệt không thể gắn ảnh vừa chụp vào biểu mẫu. Vui lòng thử lại hoặc chọn từ thư viện.';
                return;
            }

            this.handleChange({ target: this.$refs.cameraInput }, 'camera');
            this.closeCameraModal();
        },
    }"
>
    <label class="block text-sm font-medium text-gray-700">{{ $label }}</label>

    <div class="mt-2 flex gap-2">
        <button type="button" @click="startCamera()" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">
            Chụp ảnh
        </button>
        <label for="{{ $galleryInputId }}" class="inline-flex cursor-pointer items-center gap-2 rounded-lg border bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
            Chọn từ thư viện
        </label>
    </div>

    <input
        x-ref="galleryInput"
        id="{{ $galleryInputId }}"
        type="file"
        name="{{ $name }}"
        accept="image/*"
        class="sr-only mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-xl file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800"
        @change="handleChange($event, 'gallery')"
    >
    <input
        x-ref="cameraInput"
        id="{{ $cameraInputId }}"
        type="file"
        name="{{ $cameraInputName }}"
        accept="image/*"
        capture="environment"
        class="sr-only"
        @change="handleChange($event, 'camera')"
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

    <div
        x-cloak
        x-show="cameraModalOpen"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4"
        @click.self="closeCameraModal()"
        @keydown.escape.window="cameraModalOpen ? closeCameraModal() : null"
    >
        <div class="w-full max-w-3xl rounded-3xl bg-white p-4 shadow-2xl sm:p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Chụp ảnh bằng camera máy tính</h3>
                    <p class="mt-1 text-sm text-slate-500">Canh sản phẩm vào khung hình rồi bấm chụp để đưa ảnh vào biểu mẫu.</p>
                </div>
                <button type="button" class="rounded-full bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200" @click="closeCameraModal()">
                    Đóng
                </button>
            </div>

            <div class="relative mt-4 aspect-[4/3] overflow-hidden rounded-2xl bg-slate-950">
                <video
                    x-ref="cameraVideo"
                    x-show="! cameraError"
                    autoplay
                    playsinline
                    muted
                    class="h-full w-full object-cover"
                ></video>

                <div
                    x-show="cameraBusy"
                    class="absolute inset-0 flex items-center justify-center bg-slate-950/60 px-6 text-center text-sm font-medium text-white"
                >
                    Đang kết nối camera...
                </div>

                <div
                    x-show="cameraError"
                    class="absolute inset-0 flex items-center justify-center px-6 text-center text-sm font-medium text-white"
                >
                    <span x-text="cameraError"></span>
                </div>
            </div>

            <canvas x-ref="cameraCanvas" class="hidden"></canvas>

            <div class="mt-4 flex flex-wrap justify-end gap-2">
                <button
                    type="button"
                    class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    @click="openDesktopCamera()"
                >
                    Bật lại camera
                </button>
                <button
                    type="button"
                    class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    @click="closeCameraModal()"
                >
                    Hủy
                </button>
                <button
                    type="button"
                    class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400"
                    :disabled="cameraBusy || !! cameraError"
                    @click="captureDesktopPhoto()"
                >
                    Chụp ảnh
                </button>
            </div>
        </div>
    </div>
</div>
