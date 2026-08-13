<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sign in') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-brand-50 antialiased">
    <div class="flex min-h-full flex-col items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="w-full max-w-sm">
            <div class="mb-8 flex flex-col items-center gap-3 text-center">
                <img src="{{ asset('img/logo.png') }}" alt="{{ config('app.name') }}" class="h-12 w-auto">
                <h1 class="text-lg font-semibold text-brand-900">Patient Portal</h1>
                <p class="text-sm text-brand-600">View your appointments and documents</p>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-brand-100 sm:p-8">
                @if (session('status'))
                    <div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-700">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                @yield('content')
            </div>

            <p class="mt-8 text-center text-xs text-brand-400">
                &copy; {{ date('Y') }} NY Best Medical. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
