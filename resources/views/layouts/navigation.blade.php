@php($activeRole = auth()->user()?->getRoleNames()->first() ?? 'staff')

<aside class="fixed inset-y-0 left-0 z-50 w-72 -translate-x-full transform border-r border-slate-800 bg-slate-950 text-white transition-transform duration-200 lg:translate-x-0" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
    <div class="flex h-full flex-col">
        <div class="flex h-16 items-center gap-3 border-b border-white/10 px-6">
            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white/10 text-sm font-bold">K</div>
            <div>
                <p class="text-sm font-semibold">{{ config('app.name', 'HINITHANLYKYGUI') }}</p>
                <p class="text-xs text-slate-400">Quản lý ký gửi</p>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-6">
            <div class="mb-6 rounded-2xl bg-white/5 px-4 py-3">
                <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Tài khoản</p>
                <p class="mt-1 text-sm font-semibold text-white">{{ Auth::user()->name }}</p>
                <p class="text-xs text-slate-400">{{ $activeRole }}</p>
            </div>

            <nav
                class="space-y-3"
                x-data="{
                    commonOpen: {{ request()->routeIs('sales.*', 'categories.*', 'suppliers.*', 'consignments.*', 'products.*', 'product-labels.*', 'sold-products.*', 'revenue.*', 'supplier-payments.*') ? 'true' : 'false' }},
                    systemOpen: {{ request()->routeIs('logs.*', 'users.*', 'permissions.*', 'roles.*', 'settings.*') ? 'true' : 'false' }},
                    init() {
                        this.commonOpen = localStorage.getItem('sidebar.commonOpen') === '1' || this.commonOpen;
                        this.systemOpen = localStorage.getItem('sidebar.systemOpen') === '1' || this.systemOpen;
                    },
                    toggleCommon() {
                        this.commonOpen = !this.commonOpen;
                        localStorage.setItem('sidebar.commonOpen', this.commonOpen ? '1' : '0');
                    },
                    toggleSystem() {
                        this.systemOpen = !this.systemOpen;
                        localStorage.setItem('sidebar.systemOpen', this.systemOpen ? '1' : '0');
                    },
                    openCommon() {
                        this.commonOpen = true;
                        localStorage.setItem('sidebar.commonOpen', '1');
                    },
                    openSystem() {
                        this.systemOpen = true;
                        localStorage.setItem('sidebar.systemOpen', '1');
                    },
                }"
            >
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-white text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('sales.index') }}" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('sales.*') ? 'bg-white text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                    <span>Bán hàng</span>
                </a>

                <div class="group rounded-3xl bg-white/5 p-1">
                    <button type="button" class="flex w-full items-center justify-between rounded-2xl px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/10" @click="toggleCommon()">
                        <span>Chung</span>
                        <svg class="h-4 w-4 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div class="mt-1 space-y-1 px-2 pb-2" x-cloak x-show="commonOpen" x-transition>
                        @can('categories.view')
                            <a href="{{ route('categories.index') }}" @click="openCommon()" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('categories.*') ? 'bg-white text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                <span>Danh mục</span>
                            </a>
                        @endcan
                        @can('suppliers.view')
                            <a href="{{ route('suppliers.index') }}" @click="openCommon()" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('suppliers.*') ? 'bg-white text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                <span>Nhà cung cấp</span>
                            </a>
                        @endcan
                        @can('consignments.view')
                            <a href="{{ route('consignments.index') }}" @click="openCommon()" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('consignments.*') ? 'bg-white text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                <span>Phiếu ký gửi</span>
                            </a>
                        @endcan
                        @can('products.view')
                            <a href="{{ route('products.index') }}" @click="openCommon()" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('products.*') ? 'bg-white text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                <span>Sản phẩm</span>
                            </a>
                            <a href="{{ route('product-labels.index') }}" @click="openCommon()" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('product-labels.*') ? 'bg-white text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                <span>In Mã hàng</span>
                            </a>
                        @endcan
                        @can('sales.records.view')
                            <a href="{{ route('sold-products.index') }}" @click="openCommon()" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('sold-products.*') ? 'bg-white text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                <span>Sản phẩm đã bán</span>
                            </a>
                        @endcan
                        @can('sales.revenue.view')
                            <a href="{{ route('revenue.index') }}" @click="openCommon()" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('revenue.*') ? 'bg-white text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                <span>Doanh thu</span>
                            </a>
                        @endcan
                        @if(in_array($activeRole, ['admin', 'super-admin'], true))
                            <a href="{{ route('supplier-payments.index') }}" @click="openCommon()" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('supplier-payments.*') ? 'bg-white text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                <span>Thanh toán NCC</span>
                            </a>
                        @endif
                    </div>
                </div>

                <div class="group rounded-3xl bg-white/5 p-1">
                    <button type="button" class="flex w-full items-center justify-between rounded-2xl px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/10" @click="toggleSystem()">
                        <span>Hệ thống</span>
                        <svg class="h-4 w-4 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div class="mt-1 space-y-1 px-2 pb-2" x-cloak x-show="systemOpen" x-transition>
                        @can('logs.view')
                            <a href="{{ route('logs.index') }}" @click="openSystem()" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('logs.*') ? 'bg-white text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                <span>Nhật ký</span>
                            </a>
                        @endcan
                        @canany(['users.view', 'users.manage'])
                            <a href="{{ route('users.index') }}" @click="openSystem()" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('users.*') ? 'bg-white text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                <span>Tài khoản</span>
                            </a>
                        @endcanany
                        @canany(['permissions.view', 'permissions.manage'])
                            <a href="{{ route('permissions.index') }}" @click="openSystem()" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('permissions.*') ? 'bg-white text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                <span>Phân quyền</span>
                            </a>
                            <a href="{{ route('roles.index') }}" @click="openSystem()" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('roles.*') ? 'bg-white text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                <span>Vai trò</span>
                            </a>
                        @endcanany
                        @can('settings.manage')
                            <a href="{{ route('settings.payment.edit') }}" @click="openSystem()" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('settings.*') ? 'bg-white text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                <span>Cài đặt</span>
                            </a>
                        @endcan
                    </div>
                </div>

                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium text-slate-300 transition hover:bg-white/10 hover:text-white">
                    <span>Hồ sơ</span>
                </a>
            </nav>
        </div>

        <div class="border-t border-white/10 p-4">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="flex w-full items-center justify-center rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-slate-100" onclick="event.preventDefault(); this.closest('form').submit();">
                    Đăng xuất
                </button>
            </form>
        </div>
    </div>
</aside>
