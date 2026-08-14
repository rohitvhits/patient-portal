<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'My Appointments') - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full overflow-x-hidden bg-canvas font-sans text-slate-900 antialiased">
    @php
        $portalUser = Auth::guard('patient')->user();
        $headerPatient = $portalUser?->patients()->orderBy('id')->first();
        $headerName = $headerPatient?->full_name ?: 'Patient';
        $nameParts = preg_split('/\s+/', trim($headerName)) ?: [];
        $initials = collect($nameParts)->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('') ?: 'P';
    @endphp

    <div class="flex min-h-full flex-col">
        <header class="sticky top-0 z-30 border-b border-black/10 bg-brand-900 text-white">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <a href="{{ route('appointments.index') }}" class="flex min-w-0 items-center gap-2.5 transition hover:opacity-90">
                    <img src="{{ asset('img/logo-ny.png') }}" alt="NY Best Medical" class="h-9 w-auto max-w-[200px] object-contain">
                </a>

                @auth('patient')
                    <div class="relative shrink-0" data-account-menu>
                        <button type="button" id="account-trigger" data-account-trigger class="flex items-center gap-2.5 rounded-lg py-1.5 pl-1.5 pr-2.5 transition hover:bg-white/8 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 sm:pr-3" aria-haspopup="true" aria-expanded="false" aria-controls="account-panel">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-600 text-xs font-bold tracking-wide text-white">
                                {{ $initials }}
                            </span>
                            <span class="hidden min-w-0 text-left leading-tight sm:block">
                                <span class="block truncate text-sm font-semibold text-white">{{ $headerName }}</span>
                                {{-- <span class="block truncate text-xs font-medium text-brand-200/80">Patient Portal</span> --}}
                            </span>
                            <svg data-account-chevron class="h-4 w-4 shrink-0 text-brand-200/80 transition-transform duration-200" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div id="account-panel" data-account-panel role="menu" aria-labelledby="account-trigger" class="invisible absolute right-0 z-40 mt-2 w-60 origin-top-right scale-95 rounded-xl border border-slate-100 bg-white p-2 opacity-0 shadow-card transition duration-150 ease-out">
                            <div class="flex items-center gap-3 rounded-lg px-2.5 py-2.5 sm:hidden">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">{{ $initials }}</span>
                                <div class="min-w-0 leading-tight">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $headerName }}</p>
                                    {{-- <p class="truncate text-xs font-medium text-slate-500">Patient Portal</p> --}}
                                </div>
                            </div>
                            <div class="hidden px-2.5 py-2 sm:block">
                                <p class="text-xs font-medium text-slate-400">Signed in</p>
                            </div>
                            <div class="my-1 h-px bg-slate-100 sm:hidden"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" role="menuitem" class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2.5 text-left text-sm font-semibold text-slate-700 transition hover:bg-danger-50 hover:text-danger-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-danger-500/40">
                                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 17.5H4.75A1.25 1.25 0 013.5 16.25V3.75A1.25 1.25 0 014.75 2.5H7.5M13 14l4-4-4-4M17 10H7.5" />
                                    </svg>
                                    Log out
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth
            </div>
            {{-- A quiet accent line — the only "gradient flourish" in the chrome, kept to 2px --}}
            <div class="h-px w-full bg-gradient-to-r from-brand-400/0 via-brand-400/70 to-brand-400/0" aria-hidden="true"></div>
        </header>

        <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 flex items-center gap-2.5 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-semibold text-brand-800 shadow-soft">
                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.25a.75.75 0 00-1.5 0v4.5a.75.75 0 00.375.65l3 1.75a.75.75 0 00.75-1.3l-2.625-1.53V6.75z" clip-rule="evenodd" />
                    </svg>
                    {{ session('status') }}
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="border-t border-slate-200/70 py-6">
            <div class="mx-auto flex max-w-7xl flex-col items-center gap-1 px-4 text-center sm:px-6 lg:px-8">
                <p class="text-xs font-medium text-slate-400">&copy; {{ date('Y') }} NY Best Medical. All rights reserved.</p>
            </div>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const menu = document.querySelector('[data-account-menu]');
            if (!menu) return;
            const trigger = menu.querySelector('[data-account-trigger]');
            const panel = menu.querySelector('[data-account-panel]');
            const chevron = menu.querySelector('[data-account-chevron]');

            const setOpen = (open) => {
                trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
                chevron.classList.toggle('rotate-180', open);
                panel.classList.toggle('invisible', !open);
                panel.classList.toggle('opacity-0', !open);
                panel.classList.toggle('scale-95', !open);
                panel.classList.toggle('opacity-100', open);
                panel.classList.toggle('scale-100', open);
            };

            trigger.addEventListener('click', () => setOpen(panel.classList.contains('invisible')));
            document.addEventListener('click', (event) => {
                if (!menu.contains(event.target)) setOpen(false);
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') setOpen(false);
            });
        });
    </script>
</body>
</html>