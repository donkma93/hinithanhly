<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'HINITHANLYKYGUI') }} | Bán hàng</title>

    @include('layouts.partials.no-build-assets')
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    @include('partials.sales-panel')
</body>
</html>