<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">

        <title>{{ $title ?? 'Admin Portal' }} - {{ config('app.name', 'Laundry App') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Tailwind CSS & Vite -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="h-full font-sans antialiased text-slate-800">
        {{ $slot }}
    </body>
</html>

