<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sign in') — {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full overflow-x-hidden bg-canvas font-sans text-slate-900 antialiased">
    <div class="relative flex min-h-full flex-col lg:min-h-screen lg:flex-row">

        {{-- Brand panel — a compact header bar on mobile/tablet, a full hero column on desktop --}}
        <div class="relative overflow-hidden bg-brand-900 lg:flex lg:w-[44%] lg:flex-col lg:justify-between xl:w-2/5">
            <div class="pointer-events-none absolute inset-0 opacity-[0.06]" style="background-image: radial-gradient(circle, #fff 1px, transparent 1px); background-size: 20px 20px;"></div>
            <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-brand-700/40 blur-3xl lg:h-80 lg:w-80"></div>
            <div class="pointer-events-none absolute -bottom-20 -left-10 hidden h-72 w-72 rounded-full bg-brand-600/30 blur-3xl lg:block"></div>

            <div class="relative flex items-center justify-center px-6 py-5 sm:px-10 sm:py-6 lg:justify-start lg:px-14 lg:py-12">
                <img src="{{ asset('img/logo-ny.png') }}" alt="NY Best Medical" class="h-10 w-auto object-contain sm:h-12 lg:h-14">
            </div>

            {{-- Hero messaging + feature highlights — desktop only, mirrors the mobile summary card below --}}
            <div class="relative hidden px-14 lg:block">
                <h2 class="text-4xl font-bold leading-tight tracking-tight text-white">
                    Your care,<br>made <span class="text-lime-400">simpler.</span>
                </h2>
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-brand-100/75">
                    Securely access your appointments, medical information, and shared documents — all in one place.
                </p>

                <div class="mt-10 space-y-6">
                    <div class="flex items-start gap-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white/10 text-brand-200">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 1a.75.75 0 01.375.1l7.25 4.146a.75.75 0 01.375.65v5.256c0 4.69-3.023 8.65-7.25 10.05a.75.75 0 01-.5 0C6.023 19.7 3 15.742 3 11.052V5.796a.75.75 0 01.375-.65l7.25-4.147A.75.75 0 0110 1zm3.03 6.28a.75.75 0 00-1.06-1.06l-3.72 3.72-1.22-1.22a.75.75 0 00-1.06 1.06l1.75 1.75a.75.75 0 001.06 0l4.25-4.25z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-white">Secure passwordless sign-in</p>
                            <p class="mt-0.5 text-xs leading-relaxed text-brand-100/65">Safe, fast and easy access without passwords.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white/10 text-brand-200">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-white">Manage your appointments</p>
                            <p class="mt-0.5 text-xs leading-relaxed text-brand-100/65">View and manage upcoming and past appointments.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white/10 text-brand-200">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M4.75 2A1.75 1.75 0 003 3.75v12.5c0 .966.784 1.75 1.75 1.75h10.5A1.75 1.75 0 0017 16.25V7.06a1.75 1.75 0 00-.513-1.237l-3.31-3.31A1.75 1.75 0 0011.94 2H4.75zM6 10.25a.75.75 0 01.75-.75h6.5a.75.75 0 010 1.5h-6.5a.75.75 0 01-.75-.75zm.75 2.75a.75.75 0 000 1.5h4.5a.75.75 0 000-1.5h-4.5z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-white">Access shared documents</p>
                            <p class="mt-0.5 text-xs leading-relaxed text-brand-100/65">View and download your documents anytime.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative hidden px-14 py-10 lg:block">
                <p class="text-xs tracking-wide text-brand-100/50">&copy; {{ date('Y') }} NY Best Medical. All rights reserved.</p>
            </div>
        </div>

        {{-- Content panel --}}
        <div class="relative flex flex-1 flex-col items-center justify-center overflow-hidden px-4 py-10 sm:px-6 lg:px-8 lg:py-12">
            <div class="pointer-events-none absolute -bottom-24 -right-16 h-72 w-72 animate-drift-slow-reverse rounded-full bg-brand-200/50 blur-2xl lg:h-96 lg:w-96"></div>
            <div class="pointer-events-none absolute -left-20 top-1/4 hidden h-56 w-56 animate-drift-slow rounded-full bg-brand-100/60 blur-2xl lg:block"></div>
            <svg class="pointer-events-none absolute bottom-0 right-0 hidden h-64 w-96 text-brand-300/40 lg:block" viewBox="0 0 400 260" fill="none" aria-hidden="true">
                <path d="M-10 180 C 60 140, 120 220, 190 170 S 320 120, 410 160" stroke="currentColor" stroke-width="1.5" />
                <path d="M-10 220 C 70 180, 130 250, 200 210 S 330 160, 410 200" stroke="currentColor" stroke-width="1.5" />
                <path d="M-10 250 C 80 220, 140 280, 210 245 S 340 200, 410 235" stroke="currentColor" stroke-width="1.5" />
            </svg>

            <div class="relative w-full max-w-sm animate-fade-slide-up">
                <div class="mb-8 text-center lg:text-left">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">@yield('heading', 'Patient Portal')</h1>
                    <p class="mt-2 text-sm leading-relaxed text-slate-500">@yield('subtitle', 'View your appointments and documents')</p>
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-lift sm:p-8">
                    @if (session('status'))
                        <div class="mb-6 flex items-start gap-2.5 rounded-lg bg-brand-50 px-4 py-3 text-sm font-medium text-brand-800">
                            <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.25a.75.75 0 00-1.5 0v4.5a.75.75 0 00.375.65l3 1.75a.75.75 0 00.75-1.3l-2.625-1.53V6.75z" clip-rule="evenodd" />
                            </svg>
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-6 flex items-start gap-2.5 rounded-lg bg-danger-50 px-4 py-3 text-sm font-medium text-danger-700">
                            <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.63-1.516 2.63H3.72c-1.347 0-2.189-1.463-1.516-2.63L8.485 2.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                            </svg>
                            {{ $errors->first() }}
                        </div>
                    @endif

                    @yield('content')
                </div>

                <p class="mt-8 text-center text-xs tracking-wide text-slate-400 lg:hidden">
                    &copy; {{ date('Y') }} NY Best Medical. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
