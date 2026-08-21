@extends('layouts.app')

@section('title', 'My Appointments')

@section('content')
    @php
        $patients = $patientIdentities->values();
        $primaryPatient = $patients->first();
        $firstName = $primaryPatient?->full_name ? explode(' ', trim($primaryPatient->full_name))[0] : 'there';
        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
        $greetingEmoji = $hour < 12 ? '🌅' : ($hour < 18 ? '☀️' : '🌙');
        $todayLabel = now()->format('l, F j, Y');
    @endphp

    <section class="animate-fade-slide-up relative mb-6 overflow-hidden rounded-2xl bg-gradient-to-br from-[#24425a] via-brand-800 to-brand-950 text-white shadow-card">
        {{-- Quiet decorative rings — purely ambient, never covers content --}}
        <div class="pointer-events-none absolute -right-6 -top-10 h-24 w-24 rounded-full border border-white/10" aria-hidden="true"></div>
        <div class="pointer-events-none absolute right-16 top-4 h-10 w-10 rounded-full border border-white/10" aria-hidden="true"></div>

        <div class="relative flex min-w-0 flex-col gap-1.5 px-5 py-2.5 sm:flex-row sm:items-center sm:justify-between sm:gap-3 sm:px-7 sm:py-3">
            <div class="flex min-w-0 items-center gap-2.5">
                <span class="animate-gentle-pulse flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/10 text-sm ring-1 ring-white/15">
                    {{ $greetingEmoji }}
                </span>
                <div class="min-w-0">
                    <p class="truncate text-xs font-semibold tracking-wide text-brand-200">{{ $greeting }}, {{ $firstName }}</p>
                    <p class="mt-0.5 truncate text-xs font-medium text-brand-100/70" data-live-datetime data-fallback-date="{{ $todayLabel }}">{{ $todayLabel }}</p>
                </div>
            </div>
            <h1 class="pl-[2.6rem] text-left text-base font-bold tracking-tight text-white sm:shrink-0 sm:pl-0 sm:text-right sm:text-lg">My Appointments</h1>
        </div>
    </section>

    <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-soft sm:p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Filter appointments</h2>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-xs font-bold text-brand-800 ring-1 ring-inset ring-brand-100">
                <span id="appointments-total-badge" class="text-sm font-black text-brand-700">
                    <span class="inline-block h-3 w-4 animate-pulse rounded bg-brand-200 align-middle"></span>
                </span>
                <span id="appointments-total-label">Appointments</span>
            </span>
        </div>
        <form method="GET" action="{{ route('appointments.index') }}" id="appointmentFilters" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[2fr_1fr_1fr]">
            <div class="relative sm:col-span-2 lg:col-span-1">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                </svg>
                <input id="appointmentSearch" name="search" type="search" value="{{ $filters['search'] }}" placeholder="Search by portal name, agency, or service" aria-label="Search appointments" class="w-full rounded-lg border border-slate-200 bg-slate-50/60 py-2.5 pl-10 pr-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-brand-400 focus:bg-white focus:ring-4 focus:ring-brand-500/10">
            </div>
            <select name="status" aria-label="Filter by status" onchange="this.form.requestSubmit()" class="w-full rounded-lg border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 text-sm text-slate-800 outline-none transition focus:border-brand-400 focus:bg-white focus:ring-4 focus:ring-brand-500/10">
                <option value="">All statuses</option>
                @if ($filters['status'] !== '')
                    <option value="{{ $filters['status'] }}" selected>{{ $filters['status'] }}</option>
                @endif
            </select>
            <select name="agency" aria-label="Filter by agency" onchange="this.form.requestSubmit()" class="w-full rounded-lg border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 text-sm text-slate-800 outline-none transition focus:border-brand-400 focus:bg-white focus:ring-4 focus:ring-brand-500/10">
                <option value="">All agencies</option>
                @if ($filters['agency'] !== '')
                    <option value="{{ $filters['agency'] }}" selected>{{ $filters['agency'] }}</option>
                @endif
            </select>
        </form>
    </section>

    {{-- Filled in by fetch() the instant this shell finishes loading — see the script
         below. Starts out as a skeleton so *something* meaningful is on screen
         immediately, even on a hard refresh / typed URL / bookmark, before the live
         ERP-backed data has come back. --}}
    {{-- Path + query only (not fullUrl()) — behind a load balancer that terminates
         TLS, Laravel can misdetect the scheme as http even though the page itself is
         https, which turns this fetch() into a blocked mixed-content request with no
         visible error except "failed to fetch". A relative URL always inherits the
         current page's real scheme, so it can't hit that trap. --}}
    <div id="appointments-results" data-fragment-url="{{ request()->getRequestUri() }}" aria-live="polite">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft">
            <div class="hidden divide-y divide-slate-100 md:block">
                @for ($i = 0; $i < 5; $i++)
                    <div class="flex items-center gap-4 px-6 py-4">
                        <div class="h-9 w-24 shrink-0 animate-pulse rounded-lg bg-slate-100"></div>
                        <div class="h-8 w-8 shrink-0 animate-pulse rounded-full bg-slate-100"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3 w-1/3 animate-pulse rounded bg-slate-100"></div>
                            <div class="h-2.5 w-1/4 animate-pulse rounded bg-slate-100"></div>
                        </div>
                        <div class="h-6 w-20 shrink-0 animate-pulse rounded-md bg-slate-100"></div>
                    </div>
                @endfor
            </div>
            <div class="space-y-3 p-4 md:hidden">
                @for ($i = 0; $i < 3; $i++)
                    <div class="rounded-2xl border border-slate-100 p-4">
                        <div class="h-3 w-1/2 animate-pulse rounded bg-slate-100"></div>
                        <div class="mt-3 h-2.5 w-1/3 animate-pulse rounded bg-slate-100"></div>
                    </div>
                @endfor
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Progressive enhancement: swap the server-rendered date for "<date> • <patient's local time>",
            // and keep the clock ticking — a small, honest touch of life on the welcome card.
            const dt = document.querySelector('[data-live-datetime]');
            if (dt) {
                const fallbackDate = dt.dataset.fallbackDate;
                const render = () => {
                    const time = new Date().toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
                    dt.textContent = `${fallbackDate} • ${time}`;
                };
                render();
                setInterval(render, 30000);
            }

            // Debounce the free-text search so it submits the filter form shortly after typing
            // stops, instead of on every keystroke (status/agency selects submit immediately).
            const search = document.getElementById('appointmentSearch');
            const form = document.getElementById('appointmentFilters');
            if (search && form) {
                let timer;
                search.addEventListener('input', () => {
                    clearTimeout(timer);
                    timer = setTimeout(() => form.requestSubmit(), 400);
                });
            }

            const results = document.getElementById('appointments-results');
            const badge = document.getElementById('appointments-total-badge');
            const badgeLabel = document.getElementById('appointments-total-label');
            const statusSelect = document.querySelector('select[name="status"]');
            const agencySelect = document.querySelector('select[name="agency"]');
            const currentStatus = statusSelect.value;
            const currentAgency = agencySelect.value;

            const fillOptions = (select, values, current, allLabel) => {
                select.innerHTML = '';
                const optAll = document.createElement('option');
                optAll.value = '';
                optAll.textContent = allLabel;
                select.appendChild(optAll);
                (values || []).forEach((value) => {
                    const opt = document.createElement('option');
                    opt.value = value;
                    opt.textContent = value;
                    if (value === current) opt.selected = true;
                    select.appendChild(opt);
                });
            };

            // The whole desktop row is clickable; the inner "Details" link still works
            // natively (keyboard, new-tab, etc). Bound on the container itself — not the
            // individual <tr> elements — so it keeps working after the innerHTML swap
            // below, and after every later re-fetch (filter change, pagination).
            results.addEventListener('click', (event) => {
                const row = event.target.closest('tr[data-href]');
                if (!row || event.target.closest('a')) return;
                if (window.NYPageLoader) window.NYPageLoader.show(row.dataset.loaderText);
                window.location.href = row.dataset.href;
            });

            // Keep the full-page loader spinning (it's almost certainly already showing —
            // either from the click that navigated here, or shown fresh right here on a
            // plain F5/typed-URL load) for the entire wait, and only clear it once this
            // fetch actually settles — success or failure — never on any fixed timer or
            // on the shell simply having finished loading.
            if (window.NYPageLoader) window.NYPageLoader.show('Loading your appointments…');

            fetch(results.dataset.fragmentUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin',
            })
                .then((response) => {
                    if (!response.ok) throw new Error('bad status');
                    return response.json();
                })
                .then((data) => {
                    results.innerHTML = data.html;
                    badge.textContent = data.total;
                    badgeLabel.textContent = data.total === 1 ? 'Appointment' : 'Appointments';
                    fillOptions(statusSelect, data.statusOptions, currentStatus, 'All statuses');
                    fillOptions(agencySelect, data.agencyOptions, currentAgency, 'All agencies');
                })
                .catch(() => {
                    badge.textContent = '–';
                    results.innerHTML = `
                        <section class="flex items-start gap-3 rounded-2xl border border-warning-200 bg-warning-50 px-5 py-4 text-sm font-medium text-warning-800 shadow-soft">
                            <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86l-8.18 14.18A2 2 0 004 21h16a2 2 0 001.89-2.96L13.71 3.86a2 2 0 00-3.42 0z" />
                            </svg>
                            <span>We couldn't reach the appointment system just now, so nothing is shown below. Please refresh in a moment.</span>
                        </section>
                    `;
                })
                .finally(() => {
                    if (window.NYPageLoader) window.NYPageLoader.hide();
                });
        });
    </script>
@endsection
