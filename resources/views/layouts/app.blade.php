<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'HINITHANLYKYGUI') }}</title>

        @include('layouts.partials.no-build-assets')
        @stack('head')
        <style>[x-cloak]{display:none !important;}</style>
    </head>
    <body class="font-sans antialiased bg-slate-100 text-slate-900">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen">
            @include('layouts.navigation')

            <div class="lg:pl-72">
                <div class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6 lg:px-8">
                    <button type="button" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-slate-700 shadow-sm lg:hidden" @click="sidebarOpen = true">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M3 5.25A.75.75 0 013.75 4.5h12.5a.75.75 0 010 1.5H3.75A.75.75 0 013 5.25zm0 4.75A.75.75 0 013.75 9.25h12.5a.75.75 0 010 1.5H3.75A.75.75 0 013 10zm0 4.75a.75.75 0 01.75-.75h12.5a.75.75 0 010 1.5H3.75a.75.75 0 01-.75-.75z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-500">{{ config('app.name', 'HINITHANLYKYGUI') }}</p>
                        <p class="text-base font-semibold text-slate-900">{{ auth()->user()?->name }}</p>
                    </div>

                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none">
                                <span class="rounded-full bg-slate-900 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-white">{{ auth()->user()?->getRoleNames()->first() ?? 'staff' }}</span>
                                <span class="hidden sm:inline">{{ Auth::user()->name }}</span>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>

                <!-- Page Heading -->
                @if (isset($header))
                    <header class="border-b border-slate-200 bg-slate-50/70">
                        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endif

                <main>
                    {{ $slot }}
                </main>
            </div>

            <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden" @click="sidebarOpen = false"></div>
        </div>
        @stack('scripts')
    </body>
</html>
