@props([
    'value' => request('per_page', 10),
    'options' => [10, 20, 50, 100],
    'label' => 'Mỗi trang',
])

<div class="flex items-center gap-2">
    <label class="text-sm font-medium text-gray-700">{{ $label }}</label>
    <select name="per_page" class="rounded-xl border-gray-300 text-sm focus:border-slate-900 focus:ring-slate-900" onchange="this.form.submit()">
        @foreach ($options as $option)
            <option value="{{ $option }}" @selected((string) $value === (string) $option)>{{ $option }}</option>
        @endforeach
    </select>
</div>
