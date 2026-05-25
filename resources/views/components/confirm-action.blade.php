@props([
    'name',
    'action',
    'title' => 'Xác nhận thao tác',
    'message' => 'Bạn có chắc chắn muốn thực hiện thao tác này?',
    'confirmText' => 'Đồng ý',
    'cancelText' => 'Hủy',
    'method' => 'DELETE',
    'triggerText' => 'Xóa',
    'triggerClass' => 'text-red-600 hover:underline',
])

<button type="button" class="{{ $triggerClass }}" x-on:click.prevent="$dispatch('open-modal', '{{ $name }}')">
    {{ $triggerText }}
</button>

<x-modal :name="$name" maxWidth="lg" focusable>
    <form method="POST" action="{{ $action }}" class="px-6 py-6 sm:px-8 sm:py-8">
        @csrf

        @if (strtoupper($method) !== 'POST')
            @method($method)
        @endif

        <div class="flex items-start gap-4">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-red-50 text-red-600 ring-1 ring-red-100">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86l-8.18 14.18A2 2 0 003.82 21h16.36a2 2 0 001.71-3.06L13.71 3.86a2 2 0 00-3.42 0z" />
                </svg>
            </div>

            <div class="min-w-0 flex-1">
                <div class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-600">
                    Cần xác nhận
                </div>

                <h2 class="mt-3 text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">
                    {{ $title }}
                </h2>

                <p class="mt-2 text-sm leading-6 text-slate-600 sm:text-[15px]">
                    {{ $message }}
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <x-secondary-button
                        type="button"
                        x-on:click="$dispatch('close-modal', '{{ $name }}')"
                        class="!w-full !rounded-2xl !border-slate-300 !bg-white !px-5 !py-3.5 !text-sm !font-semibold !normal-case !tracking-normal !text-slate-700 hover:!bg-slate-50 sm:!w-auto"
                    >
                        {{ $cancelText }}
                    </x-secondary-button>

                    <x-danger-button class="!w-full rounded-2xl bg-red-600 px-5 py-3.5 text-sm font-semibold normal-case tracking-normal text-white shadow-lg shadow-red-500/20 hover:bg-red-500 focus:ring-red-500 sm:!w-auto">
                        {{ $confirmText }}
                    </x-danger-button>
                </div>
            </div>
        </div>
    </form>
</x-modal>