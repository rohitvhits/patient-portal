<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'My Appointments') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-50 antialiased">
    <div class="min-h-full">
        <header class="border-b border-gray-200 bg-white">
            <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-3 sm:px-6">
                <a href="{{ route('appointments.index') }}" class="flex items-center gap-2">
                    <img src="{{ asset('img/logo.png') }}" alt="{{ config('app.name') }}" class="h-8 w-auto">
                    <span class="hidden text-sm font-semibold text-brand-900 sm:inline">Patient Portal</span>
                </a>

                <div class="flex items-center gap-3">
                    @auth('patient')
                        <span class="hidden text-sm text-gray-500 sm:inline">{{ Auth::guard('patient')->user()->mobile }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-lg px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900">
                                Log out
                            </button>
                        </form>
                    @endauth
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
            @if (session('status'))
                <div class="mb-6 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-700">
                    {{ session('status') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
