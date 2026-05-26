@props([
    'name',
    'options' => [],
    'selected' => '',
    'placeholder' => '-- Chọn --',
    'searchPlaceholder' => 'Tìm kiếm...',
    'emptyText' => 'Không tìm thấy kết quả',
    'dependsOn' => '',
    'dependencyValue' => '',
    'filterKey' => 'supplier_id',
])

@php
    $selectedValue = old($name, $selected);
    $normalizedOptions = collect($options)
        ->map(fn ($option) => array_merge($option, [
            'value' => (string) ($option['value'] ?? ''),
            'label' => (string) ($option['label'] ?? ''),
        ]))
        ->values()
        ->all();
@endphp

<div
    class="relative"
    x-data="searchableSelect({
        name: @js($name),
        options: @js($normalizedOptions),
        selected: @js((string) $selectedValue),
        placeholder: @js($placeholder),
        searchPlaceholder: @js($searchPlaceholder),
        emptyText: @js($emptyText),
        dependsOn: @js($dependsOn),
        dependencyValue: @js($dependencyValue),
        filterKey: @js($filterKey),
    })"
    @click.outside="closeMenu()"
>
    <input type="hidden" name="{{ $name }}" :value="selectedValue">

    <button
        type="button"
        class="mt-1 flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-3 text-left text-sm shadow-sm transition focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900"
        @click="toggleMenu()"
        :aria-expanded="open.toString()"
        :disabled="Boolean(dependsOn) && !dependencyValue"
    >
        <span class="truncate" :class="selectedValue ? 'text-gray-900' : 'text-gray-400'" x-text="selectedLabel"></span>
        <svg class="ml-3 h-4 w-4 shrink-0 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="none" aria-hidden="true">
            <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </button>

    <p x-show="dependsOn && !dependencyValue" class="mt-2 text-xs text-amber-600">Hãy chọn mục phụ thuộc trước để mở danh sách này.</p>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-1 scale-95"
        class="absolute z-30 mt-2 w-full overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl"
        style="display: none;"
    >
        <div class="border-b border-gray-100 p-3">
            <input
                x-ref="search"
                x-model="query"
                type="text"
                class="w-full rounded-xl border-gray-300 text-sm focus:border-slate-900 focus:ring-slate-900"
                placeholder="{{ $searchPlaceholder }}"
            >
        </div>

        <div class="max-h-72 overflow-y-auto p-2">
            <template x-if="filteredOptions.length">
                <div class="space-y-1">
                    <template x-for="option in filteredOptions" :key="option.value">
                        <button
                            type="button"
                            class="flex w-full items-start rounded-xl px-3 py-2 text-left text-sm text-gray-700 transition hover:bg-slate-50 hover:text-slate-900"
                            @click="selectOption(option)"
                        >
                            <span class="block truncate" x-text="option.label"></span>
                        </button>
                    </template>
                </div>
            </template>

            <div x-show="!filteredOptions.length" class="px-3 py-4 text-sm text-gray-500">
                {{ $emptyText }}
            </div>
        </div>
    </div>
</div>