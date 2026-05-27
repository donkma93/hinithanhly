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

            <nav class="space-y-1">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-white text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                    <span>Dashboard</span>
                </a>
                @can('categories.view')
                    <a href="{{ route('categories.index') }}" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('categories.*') ? 'bg-white text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <span>Danh mục</span>
                    </a>
                @endcan
                @can('suppliers.view')
                    <a href="{{ route('suppliers.index') }}" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('suppliers.*') ? 'bg-white text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <span>Nhà cung cấp</span>
                    </a>
                @endcan
                @can('consignments.view')
                    <a href="{{ route('consignments.index') }}" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('consignments.*') ? 'bg-white text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <span>Phiếu ký gửi</span>
                    </a>
                @endcan
                @can('products.view')
                    <a href="{{ route('products.index') }}" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('products.*') ? 'bg-white text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <span>Sản phẩm</span>
                    </a>
                    <a href="{{ route('product-labels.index') }}" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('product-labels.*') ? 'bg-white text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <span>In Mã hàng</span>
                    </a>
                @endcan
                @can('logs.view')
                    <a href="{{ route('logs.index') }}" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('logs.*') ? 'bg-white text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <span>Nhật ký</span>
                    </a>
                @endcan
                @canany(['users.view', 'users.manage'])
                    <a href="{{ route('users.index') }}" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('users.*') ? 'bg-white text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <span>Tài khoản</span>
                    </a>
                @endcanany
                @canany(['permissions.view', 'permissions.manage'])
                    <a href="{{ route('permissions.index') }}" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('permissions.*') ? 'bg-white text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <span>Phân quyền</span>
                    </a>
                    <a href="{{ route('roles.index') }}" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('roles.*') ? 'bg-white text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <span>Vai trò</span>
                    </a>
                @endcanany
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
