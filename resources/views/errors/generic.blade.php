<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title }} - {{ config('app.name', 'HINITHANLYKYGUI') }}</title>

        @include('layouts.partials.no-build-assets')
    </head>
    <body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
        <main class="flex min-h-screen items-center justify-center px-4 py-10">
            <div class="w-full max-w-2xl overflow-hidden rounded-[2rem] border border-white/10 bg-white/5 shadow-2xl backdrop-blur">
                <div class="border-b border-white/10 px-8 py-6">
                    <p class="text-sm font-semibold uppercase tracking-[0.3em] text-slate-400">Thong bao he thong</p>
                    <div class="mt-4">
                        <h1 class="text-2xl font-semibold text-white">{{ $title }}</h1>
                        <p class="mt-2 text-sm text-slate-300">{{ $message }}</p>
                        @if ($status !== 500)
                            <p class="mt-3 text-xs uppercase tracking-[0.25em] text-slate-500">Trang thai: {{ $status }}</p>
                        @endif
                    </div>
                </div>

                <div class="space-y-6 px-8 py-6">
                    @if ($errorUuid)
                        <div class="rounded-2xl bg-slate-900/80 p-4">
                            <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Ma loi tham chieu</p>
                            <p class="mt-2 break-all font-mono text-sm text-amber-300">{{ $errorUuid }}</p>
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-3">
                        <a href="{{ url()->previous() }}" class="rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-slate-100">Quay lai</a>
                        <a href="{{ route('home') }}" class="rounded-2xl border border-white/15 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10">Ve trang chu</a>
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>
