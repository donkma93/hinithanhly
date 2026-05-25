<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-gray-900">
                    {{ __('Bảng điều khiển') }}
                </h2>
                <p class="text-sm text-gray-500">{{ __('Vai trò hiện tại: ') }}{{ $activeRole }}</p>
            </div>
            <div class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-sm">
                {{ __('Hệ thống ký gửi') }}
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm text-gray-500">Danh mục</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $stats['categories'] }}</p>
                </div>
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm text-gray-500">Nhà cung cấp</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $stats['suppliers'] }}</p>
                </div>
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm text-gray-500">Phiếu ký gửi</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $stats['consignments'] }}</p>
                </div>
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm text-gray-500">Sản phẩm</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $stats['products'] }}</p>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200 xl:col-span-2">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Biểu đồ nhập sản phẩm và phiếu ký gửi</h3>
                            <p class="text-sm text-gray-500">6 tháng gần nhất</p>
                        </div>
                    </div>
                    <div class="mt-6 h-80">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>

                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Phân loại nhà cung cấp</h3>
                    <p class="text-sm text-gray-500">Tỷ trọng theo cấp độ NCC</p>
                    <div class="mt-6 h-80">
                        <canvas id="supplierChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Truy cập nhanh</h3>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <a href="{{ route('categories.index') }}" class="rounded-2xl bg-slate-900 p-4 text-white">
                            <p class="text-sm text-slate-300">Quản lý</p>
                            <p class="mt-1 font-semibold">Danh mục và nhà cung cấp</p>
                        </a>
                        <a href="{{ route('consignments.index') }}" class="rounded-2xl bg-emerald-600 p-4 text-white">
                            <p class="text-sm text-emerald-100">Luồng nghiệp vụ</p>
                            <p class="mt-1 font-semibold">Phiếu ký gửi và sản phẩm</p>
                        </a>
                    </div>
                </div>

                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Danh sách gần đây</h3>
                    <div class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                            <span>Danh mục</span>
                            <span class="font-semibold">{{ $stats['categories'] }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                            <span>Nhà cung cấp</span>
                            <span class="font-semibold">{{ $stats['suppliers'] }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                            <span>Phiếu ký gửi</span>
                            <span class="font-semibold">{{ $stats['consignments'] }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                            <span>Sản phẩm</span>
                            <span class="font-semibold">{{ $stats['products'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            const activityContext = document.getElementById('activityChart');
            if (activityContext) {
                new Chart(activityContext, {
                    type: 'line',
                    data: {
                        labels: @json($chartLabels),
                        datasets: [{
                            label: 'Sản phẩm',
                            data: @json($productSeries),
                            borderColor: '#0f172a',
                            backgroundColor: 'rgba(15, 23, 42, 0.12)',
                            tension: 0.35,
                            fill: true,
                        }, {
                            label: 'Phiếu ký gửi',
                            data: @json($consignmentSeries),
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.12)',
                            tension: 0.35,
                            fill: true,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' }
                        },
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }

            const supplierContext = document.getElementById('supplierChart');
            if (supplierContext) {
                new Chart(supplierContext, {
                    type: 'doughnut',
                    data: {
                        labels: @json($supplierTypeLabels),
                        datasets: [{
                            data: @json($supplierTypeSeries),
                            backgroundColor: ['#0f172a', '#14b8a6', '#f59e0b', '#6366f1', '#ef4444'],
                            borderWidth: 0,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });
            }
        </script>
    @endpush
</x-app-layout>
