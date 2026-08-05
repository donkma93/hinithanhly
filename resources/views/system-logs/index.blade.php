<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">Log hệ thống</h2>
            <p class="text-sm text-gray-500">Tra cứu exception, lỗi runtime và mã tham chiếu để xử lý sự cố nhanh hơn.</p>
        </div>
    </x-slot>

    @php
        $userOptions = $users->map(fn ($user) => [
            'value' => $user->id,
            'label' => '#'.$user->public_id.' - '.$user->name,
        ])->all();
    @endphp

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-10xl space-y-6 px-3 sm:px-6 lg:px-10">
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <form method="GET" action="{{ route('system-logs.index') }}" class="grid gap-4 md:grid-cols-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Mã lỗi</label>
                        <input name="error_uuid" value="{{ request('error_uuid') }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" placeholder="Đúng mã lỗi">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Người dùng</label>
                        <x-searchable-select
                            name="user_id"
                            :options="$userOptions"
                            :selected="request('user_id')"
                            placeholder="Tất cả người dùng"
                            search-placeholder="Chọn đúng người dùng"
                            empty-text="Không có người dùng phù hợp"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Exception</label>
                        <input name="exception_class" value="{{ request('exception_class') }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" placeholder="Đúng exception">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">HTTP code</label>
                        <input name="status_code" value="{{ request('status_code') }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" placeholder="Đúng HTTP code">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Mỗi trang</label>
                        <x-per-page-select :value="request('per_page', 10)" />
                    </div>
                    <div class="flex items-end gap-3">
                        <button class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white">Lọc</button>
                        <a href="{{ route('system-logs.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700">Xóa lọc</a>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Danh sách exception</h3>
                </div>
                <div class="space-y-4 p-4 sm:p-6">
                    @forelse ($logs as $log)
                        <details class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/60">
                            <summary class="cursor-pointer list-none px-4 py-4">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="space-y-2">
                                        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            <span>#{{ $log->id }}</span>
                                            <span>{{ $log->created_at->format('d/m/Y H:i:s') }}</span>
                                            <span class="rounded-full bg-rose-100 px-2.5 py-1 text-rose-700">{{ $log->status_code ?? 500 }}</span>
                                        </div>
                                        <div class="text-sm font-semibold text-slate-900">{{ $log->exception_class }}</div>
                                        <div class="text-sm text-slate-700">{{ $log->message ?: 'Không có message.' }}</div>
                                        <div class="text-xs text-slate-500">Mã lỗi: {{ $log->error_uuid }}</div>
                                    </div>
                                    <div class="max-w-xl text-xs text-slate-500 lg:text-right">
                                        <div>{{ $log->method ?: 'CLI' }} {{ $log->url ?: '---' }}</div>
                                        <div>{{ $log->route_name ?: 'Không có route' }}</div>
                                        <div>{{ $log->user?->name ?? 'Hệ thống' }} · {{ $log->ip_address ?? '---' }}</div>
                                    </div>
                                </div>
                            </summary>

                            <div class="space-y-4 border-t border-slate-200 bg-white px-4 py-4">
                                <div class="grid gap-4 lg:grid-cols-2">
                                    <div class="rounded-2xl bg-slate-50 p-4">
                                        <h4 class="text-sm font-semibold text-slate-900">Nguồn lỗi</h4>
                                        <p class="mt-2 break-all text-sm text-slate-700">{{ $log->file ?: '---' }}</p>
                                        <p class="mt-1 text-sm text-slate-500">Line: {{ $log->line ?? '---' }}</p>
                                    </div>
                                    <div class="rounded-2xl bg-slate-50 p-4">
                                        <h4 class="text-sm font-semibold text-slate-900">Request</h4>
                                        <p class="mt-2 text-sm text-slate-700">User agent: {{ $log->user_agent ?: '---' }}</p>
                                        <p class="mt-1 text-sm text-slate-500">Route: {{ $log->route_name ?: '---' }}</p>
                                    </div>
                                </div>

                                @if (! empty($log->context))
                                    <div>
                                        <h4 class="text-sm font-semibold text-slate-900">Context</h4>
                                        <pre class="mt-2 overflow-x-auto rounded-2xl bg-slate-950 p-4 text-xs text-slate-100">{{ json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </div>
                                @endif

                                <div>
                                    <h4 class="text-sm font-semibold text-slate-900">Stack trace</h4>
                                    <pre class="mt-2 overflow-x-auto rounded-2xl bg-slate-950 p-4 text-xs leading-6 text-slate-100">{{ $log->trace ?: 'Không có stack trace.' }}</pre>
                                </div>
                            </div>
                        </details>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 px-6 py-12 text-center text-sm text-slate-500">
                            Chưa có exception nào được ghi nhận.
                        </div>
                    @endforelse
                </div>
                <div class="px-6 py-4">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
