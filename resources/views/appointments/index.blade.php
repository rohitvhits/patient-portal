@extends('layouts.app')

@section('title', 'My Appointments')

@section('content')
    @php
        $formatDate = function ($value, $fallback = '-') {
            if (empty($value)) {
                return $fallback;
            }

            try {
                return \Carbon\Carbon::parse($value)->format('m/d/Y');
            } catch (\Throwable $e) {
                return $value;
            }
        };
        $formatDob = fn ($value) => $formatDate($value, 'Not available');

        $statusMeta = function (?string $status) {
            $value = strtolower($status ?? '');

            return match (true) {
                str_contains($value, 'complet') => ['bg' => 'bg-success-50', 'text' => 'text-success-700', 'ring' => 'ring-success-100', 'dot' => 'bg-success-500'],
                str_contains($value, 'cancel') => ['bg' => 'bg-danger-50', 'text' => 'text-danger-700', 'ring' => 'ring-danger-100', 'dot' => 'bg-danger-500'],
                str_contains($value, 'pending') => ['bg' => 'bg-warning-50', 'text' => 'text-warning-700', 'ring' => 'ring-warning-100', 'dot' => 'bg-warning-500'],
                str_contains($value, 'request') => ['bg' => 'bg-info-50', 'text' => 'text-info-700', 'ring' => 'ring-info-100', 'dot' => 'bg-info-500'],
                $value === '' => ['bg' => 'bg-slate-100', 'text' => 'text-slate-500', 'ring' => 'ring-slate-200', 'dot' => 'bg-slate-400'],
                default => ['bg' => 'bg-brand-50', 'text' => 'text-brand-700', 'ring' => 'ring-brand-100', 'dot' => 'bg-brand-500'],
            };
        };
        $initialsOf = function (?string $name) {
            $parts = collect(preg_split('/\s+/', trim((string) $name)))->filter();

            return $parts->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('') ?: 'P';
        };
        // The ERP stores time as either a "16:29:00 - 19:32:00" range (Schedule Appointment)
        // or a bare "08:00 - 08:15" / single value (Telehealth) — always 24-hour, never AM/PM.
        // Reformat every side to the app's 12-hour AM/PM style, single time or range alike.
        $formatTimePart = function (string $t) {
            $t = trim($t);
            if ($t === '') {
                return $t;
            }

            try {
                return \Carbon\Carbon::parse($t)->format('g:i A');
            } catch (\Throwable $e) {
                return $t;
            }
        };
        $formatTimeRange = function (?string $range) use ($formatTimePart) {
            if (empty($range)) {
                return null;
            }

            $parts = array_map('trim', explode(' - ', $range));
            if (count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '') {
                return $formatTimePart($parts[0]).' – '.$formatTimePart($parts[1]);
            }

            return $formatTimePart($range);
        };

        $patients = $patientIdentities->values();
        $primaryPatient = $patients->first();
        $firstName = $primaryPatient?->full_name ? explode(' ', trim($primaryPatient->full_name))[0] : 'there';
        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
        $greetingEmoji = $hour < 12 ? '🌅' : ($hour < 18 ? '☀️' : '🌙');
        $todayLabel = now()->format('l, F j, Y');

        // Build each row's view data once so the desktop table and the mobile card list can share it.
        // Only the current page's appointments — filtering/pagination itself happens server-side in the controller.
        $rows = collect($appointments->items())->map(function ($appointment) use ($patientIdentities, $appointmentTelehealth, $appointmentSchedules, $statusMeta, $initialsOf, $formatDate, $formatDob, $formatTimeRange) {
            $patient = $patientIdentities->get($appointment->erp_appointment_id);
            $patientName = $patient?->full_name ?: 'Patient name unavailable';
            $agencyName = $appointment->agency_name ?: ($patient?->agency_id ? 'Agency #'.$patient->agency_id : 'Not available');
            $telehealth = $appointmentTelehealth->get($appointment->erp_appointment_id);
            $schedule = $appointmentSchedules->get($appointment->erp_appointment_id);
            $timeRange = $formatTimeRange($schedule['timeRange'] ?? null);

            return (object) [
                'appointment' => $appointment,
                'patientName' => $patientName,
                'initials' => $initialsOf($patientName),
                'dob' => $patient ? $formatDob($patient->dob) : 'Not available',
                'agencyName' => $agencyName,
                'hasSchedule' => !empty($appointment->appointment_date),
                'date' => $formatDate($appointment->appointment_date),
                'time' => $timeRange ?: ($appointment->appointment_time ?: '-'),
                'telehealth' => $telehealth ? [
                    'date' => $formatDate($telehealth['date']),
                    'time' => $formatTimeRange($telehealth['time']),
                    'nurse' => $telehealth['nurse'],
                ] : null,
                'status' => $appointment->status,
                'meta' => $statusMeta($appointment->status),
            ];
        });
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

    <script>
        // Progressive enhancement: swap the server-rendered date for "<date> • <patient's local time>",
        // and keep the clock ticking — a small, honest touch of life on the welcome card.
        document.addEventListener('DOMContentLoaded', () => {
            const el = document.querySelector('[data-live-datetime]');
            if (!el) return;
            const fallbackDate = el.dataset.fallbackDate;
            const render = () => {
                const time = new Date().toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
                el.textContent = `${fallbackDate} • ${time}`;
            };
            render();
            setInterval(render, 30000);
        });
    </script>

    @if ($erpUnavailable ?? false)
        <section class="mb-6 flex items-start gap-3 rounded-2xl border border-warning-200 bg-warning-50 px-5 py-4 text-sm font-medium text-warning-800 shadow-soft">
            <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86l-8.18 14.18A2 2 0 004 21h16a2 2 0 001.89-2.96L13.71 3.86a2 2 0 00-3.42 0z" />
            </svg>
            <span>We couldn't reach the appointment system just now, so nothing is shown below. Please refresh in a moment.</span>
        </section>
    @endif

    <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-soft sm:p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Filter appointments</h2>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-xs font-bold text-brand-800 ring-1 ring-inset ring-brand-100">
                <span class="text-sm font-black text-brand-700">{{ $appointments->total() }}</span>
                Total {{ \Illuminate\Support\Str::plural('Appointment', $appointments->total()) }}
            </span>
        </div>
        <form method="GET" action="{{ route('appointments.index') }}" id="appointmentFilters" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[2fr_1fr_1fr]">
            <div class="relative sm:col-span-2 lg:col-span-1">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                </svg>
                <input id="appointmentSearch" name="search" type="search" value="{{ $filters['search'] }}" placeholder="Search by portal name, agency, or service" aria-label="Search appointments" class="w-full rounded-lg border border-slate-200 bg-slate-50/60 py-2.5 pl-10 pr-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-brand-400 focus:bg-white focus:ring-4 focus:ring-brand-500/10">
            </div>
            <select name="status" aria-label="Filter by status" onchange="this.form.submit()" class="w-full rounded-lg border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 text-sm text-slate-800 outline-none transition focus:border-brand-400 focus:bg-white focus:ring-4 focus:ring-brand-500/10">
                <option value="">All statuses</option>
                @foreach ($statusOptions as $status)
                    <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <select name="agency" aria-label="Filter by agency" onchange="this.form.submit()" class="w-full rounded-lg border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 text-sm text-slate-800 outline-none transition focus:border-brand-400 focus:bg-white focus:ring-4 focus:ring-brand-500/10">
                <option value="">All agencies</option>
                @foreach ($agencyOptions as $agency)
                    <option value="{{ $agency }}" @selected($filters['agency'] === $agency)>{{ $agency }}</option>
                @endforeach
            </select>
        </form>
    </section>

    @if ($appointments->isEmpty())
        <section class="rounded-2xl border border-slate-200 bg-white shadow-soft">
            <x-empty-state
                :title="$filters['search'] || $filters['status'] || $filters['agency'] ? 'No appointments match the selected filters.' : 'No appointments found'"
                description="Appointments booked with NY Best Medical will appear here."
            >
                <x-slot:icon>
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <rect x="3.5" y="5" width="17" height="16" rx="2.5" />
                        <path stroke-linecap="round" d="M8 3v4M16 3v4M3.5 10h17" />
                    </svg>
                </x-slot:icon>
            </x-empty-state>
        </section>
    @else
        {{-- Desktop / tablet: data table --}}
        <section class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft md:block">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-4 font-semibold">Date &amp; Time</th>
                        <th class="px-6 py-4 font-semibold">Name</th>
                        <th class="px-6 py-4 font-semibold">Agency</th>
                        <th class="px-6 py-4 font-semibold">Service</th>
                        <th class="px-6 py-4 font-semibold">Status</th>
                        <th class="px-6 py-4 font-semibold"><span class="sr-only">Details</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($rows as $row)
                        <tr
                            class="appointment-row group cursor-pointer transition hover:bg-brand-50/50 hover:shadow-[inset_3px_0_0_var(--color-brand-500)] focus-within:bg-brand-50/50 focus-within:shadow-[inset_3px_0_0_var(--color-brand-500)]"
                            data-href="{{ route('appointments.show', $row->appointment) }}"
                        >
                            <td class="whitespace-nowrap px-6 py-4">
                                <x-appointment-datetime :has-schedule="$row->hasSchedule" :date="$row->date" :time="$row->time" :telehealth="$row->telehealth" />
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-800">{{ $row->initials }}</span>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-900">{{ $row->patientName }}</p>
                                        <p class="mt-0.5 truncate text-xs text-slate-500">ID #{{ $row->appointment->erp_appointment_id }} &middot; DOB {{ $row->dob }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-700">{{ $row->agencyName }}</td>
                            <td class="max-w-xs px-6 py-4 text-slate-700">{{ $row->appointment->service_name ?: 'Not available' }}</td>
                            <td class="px-6 py-4">
                                @if ($row->status)
                                    <span class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $row->meta['bg'] }} {{ $row->meta['text'] }} {{ $row->meta['ring'] }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $row->meta['dot'] }}"></span>
                                        {{ $row->status }}
                                    </span>
                                @else
                                    <span class="text-xs font-medium text-slate-400">Not available</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('appointments.show', $row->appointment) }}" class="-mx-2.5 -my-1.5 inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-sm font-semibold text-brand-700 outline-none transition group-hover:bg-brand-100/60 group-hover:text-brand-900 focus-visible:bg-brand-100/60 focus-visible:ring-2 focus-visible:ring-brand-500/40">
                                    Details
                                    <svg class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-0.5 group-focus-within:translate-x-0.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        {{-- Mobile: stacked card list --}}
        <section class="space-y-3 md:hidden">
            @foreach ($rows as $row)
                <a
                    href="{{ route('appointments.show', $row->appointment) }}"
                    class="appointment-row group block rounded-2xl border border-slate-200 bg-white p-4 shadow-soft outline-none transition hover:-translate-y-0.5 hover:shadow-card active:scale-[0.99] focus-visible:ring-2 focus-visible:ring-brand-500/40"
                >
                    <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2">
                        <div class="min-w-0">
                            <x-appointment-datetime :has-schedule="$row->hasSchedule" :date="$row->date" :time="$row->time" :telehealth="$row->telehealth" />
                        </div>
                        @if ($row->status)
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $row->meta['bg'] }} {{ $row->meta['text'] }} {{ $row->meta['ring'] }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $row->meta['dot'] }}"></span>
                                {{ $row->status }}
                            </span>
                        @endif
                    </div>

                    <div class="mt-3.5 flex items-center gap-3 border-t border-slate-100 pt-3.5">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-800">{{ $row->initials }}</span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $row->patientName }}</p>
                            <p class="truncate text-xs text-slate-500">ID #{{ $row->appointment->erp_appointment_id }}</p>
                        </div>
                    </div>

                    <dl class="mt-3.5 grid grid-cols-2 gap-3 border-t border-slate-100 pt-3.5 text-xs">
                        <div>
                            <dt class="font-semibold uppercase tracking-wide text-slate-400">Agency</dt>
                            <dd class="mt-1 truncate font-medium text-slate-700">{{ $row->agencyName }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold uppercase tracking-wide text-slate-400">Service</dt>
                            <dd class="mt-1 truncate font-medium text-slate-700">{{ $row->appointment->service_name ?: 'Not available' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-3.5 flex items-center justify-end gap-1 border-t border-slate-100 pt-3 text-sm font-semibold text-brand-700">
                        View details
                        <svg class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-0.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </a>
            @endforeach
        </section>

        <div class="mt-5 flex flex-col items-center gap-3">
            <p class="text-center text-xs font-medium text-slate-400">
                Showing {{ $appointments->firstItem() }}–{{ $appointments->lastItem() }} of {{ $appointments->total() }} {{ \Illuminate\Support\Str::plural('appointment', $appointments->total()) }}
            </p>
            {{ $appointments->onEachSide(1)->links() }}
        </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Whole desktop row is clickable; the inner "Details" link still works natively (keyboard, new-tab, etc).
            document.querySelectorAll('tr[data-href]').forEach((row) => {
                row.addEventListener('click', (event) => {
                    if (event.target.closest('a')) return;
                    window.location.href = row.dataset.href;
                });
            });

            // Debounce the free-text search so it submits the filter form shortly after typing
            // stops, instead of on every keystroke (status/agency selects submit immediately).
            const search = document.getElementById('appointmentSearch');
            const form = document.getElementById('appointmentFilters');
            if (search && form) {
                let timer;
                search.addEventListener('input', () => {
                    clearTimeout(timer);
                    timer = setTimeout(() => form.submit(), 400);
                });
            }
        });
    </script>
@endsection
