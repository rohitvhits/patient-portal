@extends('layouts.app')

@section('title', 'Appointment Details')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('appointments.index') }}" data-loader-text="Loading your appointments…" class="-mx-2 -my-1 inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-sm font-semibold text-slate-600 outline-none transition hover:bg-slate-100 hover:text-brand-700 focus-visible:ring-2 focus-visible:ring-brand-500/40">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
            </svg>
            Back to appointments
        </a>
        <span class="text-sm font-medium text-slate-400">Appointment #{{ $appointment->erp_appointment_id }}</span>
    </div>

    {{-- Filled in by fetch() the instant this shell finishes loading — see the script
         below. Starts out as a skeleton so *something* meaningful is on screen
         immediately, even on a hard refresh / typed URL / bookmark, before the live
         ERP-backed detail + documents have come back. --}}
    <div id="appointment-detail-results" data-fragment-url="{{ request()->fullUrl() }}" aria-live="polite">
        <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft">
            <div class="h-24 animate-pulse bg-gradient-to-br from-[#24425a] via-brand-800 to-brand-950 sm:h-20"></div>
        </section>
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft">
            <div class="border-b border-slate-100 px-6 py-5 sm:px-8">
                <div class="h-4 w-32 animate-pulse rounded bg-slate-100"></div>
                <div class="mt-2 h-3 w-56 animate-pulse rounded bg-slate-100"></div>
            </div>
            <div class="divide-y divide-slate-100">
                @for ($i = 0; $i < 2; $i++)
                    <div class="flex items-center gap-3.5 px-6 py-4 sm:px-8">
                        <div class="h-10 w-10 shrink-0 animate-pulse rounded-xl bg-slate-100"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3 w-1/3 animate-pulse rounded bg-slate-100"></div>
                            <div class="h-2.5 w-1/4 animate-pulse rounded bg-slate-100"></div>
                        </div>
                    </div>
                @endfor
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const results = document.getElementById('appointment-detail-results');

            // Keep the full-page loader spinning (it's almost certainly already showing —
            // either from the click that navigated here, or shown fresh right here on a
            // plain F5/typed-URL load) for the entire wait, and only clear it once this
            // fetch actually settles — success or failure — never on any fixed timer or
            // on the shell simply having finished loading.
            if (window.NYPageLoader) window.NYPageLoader.show('Loading appointment details…');

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
                })
                .catch(() => {
                    results.innerHTML = `
                        <section class="flex items-start gap-3 rounded-2xl border border-warning-200 bg-warning-50 px-5 py-4 text-sm font-medium text-warning-800 shadow-soft">
                            <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86l-8.18 14.18A2 2 0 004 21h16a2 2 0 001.89-2.96L13.71 3.86a2 2 0 00-3.42 0z" />
                            </svg>
                            <span>We couldn't reach the appointment system just now. Please refresh in a moment.</span>
                        </section>
                    `;
                })
                .finally(() => {
                    if (window.NYPageLoader) window.NYPageLoader.hide();
                });
        });
    </script>
@endsection
