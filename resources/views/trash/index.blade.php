<x-app-layout>
    <x-slot name="header">
        <div class="space-y-2">
            <h2 class="text-2xl font-semibold text-gray-900">Thùng rác</h2>
            <p class="text-sm text-gray-500">Khôi phục hoặc xóa vĩnh viễn các bản ghi đã bị đưa vào thùng rác.</p>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div
            class="mx-auto max-w-10xl space-y-6 px-3 sm:px-6 lg:px-10"
            x-data="{
                imageZoomOpen: false,
                imageZoomSrc: '',
                imageZoomAlt: '',
                openImageZoom(src, alt) {
                    this.imageZoomSrc = src;
                    this.imageZoomAlt = alt;
                    this.imageZoomOpen = true;
                },
                closeImageZoom() {
                    this.imageZoomOpen = false;
                },
            }"
        >
            @if (session('status'))
                <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('warning'))
                <div class="rounded-2xl bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 ring-1 ring-amber-200">
                    {{ session('warning') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-2xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700 ring-1 ring-red-200">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
                @foreach ($sections as $section)
                    <div class="rounded-3xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-gray-400">{{ $section['label'] }}</p>
                        <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $section['count'] }}</p>
                        <p class="mt-1 text-sm text-gray-500">Mục trong thùng rác</p>
                    </div>
                @endforeach
            </div>

            <div class="rounded-3xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Quản lý thùng rác</h3>
                        <p class="mt-1 text-sm text-gray-500">Khôi phục dữ liệu hoặc dọn sạch từng nhóm mục đã xóa mềm.</p>
                    </div>

                    @if ($totalCount > 0)
                        <x-confirm-action
                            :name="'empty-trash-all'"
                            :action="route('trash.empty-all')"
                            method="POST"
                            title="Dọn sạch toàn bộ thùng rác"
                            message="Hành động này sẽ xóa vĩnh viễn các mục có thể dọn sạch. Những mục còn liên kết sẽ được giữ lại để tránh mất dữ liệu."
                            confirm-text="Dọn sạch"
                            trigger-text="Dọn sạch tất cả"
                            trigger-class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800"
                        />
                    @endif
                </div>
            </div>

            @foreach ($sections as $section)
                <section class="rounded-3xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:p-6">
                    <div class="flex flex-col gap-4 border-b border-gray-100 pb-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-3">
                                <h3 class="text-lg font-semibold text-gray-900">{{ $section['label'] }}</h3>
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-slate-600">
                                    {{ $section['count'] }} mục
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">{{ $section['description'] }}</p>
                        </div>

                        @if ($section['count'] > 0)
                            <x-confirm-action
                                :name="'empty-trash-'.$section['key']"
                                :action="route('trash.empty', ['type' => $section['key']])"
                                method="POST"
                                :title="'Dọn sạch '.$section['label']"
                                :message="'Hành động này sẽ xóa vĩnh viễn các mục trong '.$section['label'].'. Các mục còn liên kết sẽ được giữ lại để tránh mất dữ liệu.'"
                                confirm-text="Dọn sạch"
                                trigger-text="Dọn sạch nhóm này"
                                trigger-class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            />
                        @endif
                    </div>

                    <div class="mt-4 space-y-4">
                        @forelse ($section['items'] as $item)
                            <article class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                                    @if (! empty($item['thumbnail_url']))
                                        <button type="button" class="flex-shrink-0" @click="openImageZoom(@js($item['thumbnail_url']), @js($item['thumbnail_alt'] ?? $item['title']))">
                                            <img src="{{ $item['thumbnail_url'] }}" alt="{{ $item['thumbnail_alt'] ?? $item['title'] }}" class="h-16 w-16 rounded-2xl object-cover ring-1 ring-gray-200 transition hover:scale-105">
                                        </button>
                                    @endif

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                            <div class="min-w-0">
                                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-gray-500">{{ $item['code'] }}</p>
                                                <h4 class="mt-1 text-base font-semibold text-gray-900">{{ $item['title'] }}</h4>
                                                <p class="mt-1 text-sm text-gray-600">{{ $item['subtitle'] }}</p>
                                            </div>

                                            @if ($item['forceable'])
                                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                                                    Có thể xóa vĩnh viễn
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">
                                                    Còn liên kết
                                                </span>
                                            @endif
                                        </div>

                                        <div class="mt-3 grid gap-1 text-sm text-gray-600 sm:grid-cols-2">
                                            @foreach ($item['meta'] as $meta)
                                                <p>{{ $meta }}</p>
                                            @endforeach
                                        </div>

                                        @if (! $item['forceable'] && ! empty($item['force_reason']))
                                            <p class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-700">
                                                {{ $item['force_reason'] }}
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-end">
                                    <form method="POST" action="{{ $item['restore_url'] }}">
                                        @csrf
                                        <button class="inline-flex w-full items-center justify-center rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-500 sm:w-auto">
                                            Khôi phục
                                        </button>
                                    </form>

                                    @if ($item['forceable'])
                                        <x-confirm-action
                                            :name="'force-trash-'.$section['key'].'-'.$item['id']"
                                            :action="$item['force_url']"
                                            title="Xóa vĩnh viễn"
                                            message="Bạn có chắc chắn muốn xóa vĩnh viễn mục này khỏi thùng rác?"
                                            confirm-text="Xóa vĩnh viễn"
                                            trigger-text="Xóa vĩnh viễn"
                                            trigger-class="inline-flex w-full items-center justify-center rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 hover:bg-red-100 sm:w-auto"
                                        />
                                    @else
                                        <span class="text-sm font-medium text-gray-400">Chưa thể xóa vĩnh viễn</span>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-4 py-8 text-center text-gray-500">
                                Chưa có mục nào trong nhóm này.
                            </div>
                        @endforelse
                    </div>
                </section>
            @endforeach

            <div
                x-cloak
                x-show="imageZoomOpen"
                x-transition.opacity
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4"
                @click.self="closeImageZoom()"
                @keydown.escape.window="closeImageZoom()"
            >
                <div class="relative w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-2xl">
                    <button type="button" class="absolute right-3 top-3 z-10 rounded-full bg-white/90 px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 hover:bg-white" @click="closeImageZoom()">Đóng</button>
                    <img :src="imageZoomSrc" :alt="imageZoomAlt" class="max-h-[85vh] w-full bg-slate-950 object-contain">
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
