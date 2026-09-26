<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light" data-lte-color-mode="off">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'POS negocios') }}</title>

        <link rel="icon" href="/favicon.ico?v=2" sizes="any">
        <link rel="icon" type="image/png" href="{{ asset('favicon-32x32.png') }}?v=2" sizes="32x32">
        <link rel="icon" type="image/png" href="{{ asset('favicon-16x16.png') }}?v=2" sizes="16x16">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v=2">
        <link rel="shortcut icon" href="/favicon.ico?v=2">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=source-sans-3:300,400,500,600,700&display=swap" rel="stylesheet" />

        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
        @inertia
    </body>
</html>
