{{-- Rendered server-side and injected via fetch into #appointments-results (see
     index.blade.php). Needs $appointments, $patientIdentities, $appointmentTelehealth,
     $appointmentSchedules, $filters, $erpUnavailable — all supplied by
     AppointmentController::indexFragment(). --}}
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

        $parts = array_map('trim', preg_split('/\s*-\s*/', trim($range), 2));
        if (count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '') {
            return $formatTimePart($parts[0]).' – '.$formatTimePart($parts[1]);
        }

        return $formatTimePart($range);
    };

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

@if ($erpUnavailable ?? false)
    <section class="mb-6 flex items-start gap-3 rounded-2xl border border-warning-200 bg-warning-50 px-5 py-4 text-sm font-medium text-warning-800 shadow-soft">
        <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86l-8.18 14.18A2 2 0 004 21h16a2 2 0 001.89-2.96L13.71 3.86a2 2 0 00-3.42 0z" />
        </svg>
        <span>We couldn't reach the appointment system just now, so nothing is shown below. Please refresh in a moment.</span>
    </section>
@endif

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
                        data-loader-text="Loading appointment details…"
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
                            <a href="{{ route('appointments.show', $row->appointment) }}" data-loader-text="Loading appointment details…" class="-mx-2.5 -my-1.5 inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-sm font-semibold text-brand-700 outline-none transition group-hover:bg-brand-100/60 group-hover:text-brand-900 focus-visible:bg-brand-100/60 focus-visible:ring-2 focus-visible:ring-brand-500/40">
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
                data-loader-text="Loading appointment details…"
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
