@extends('layouts.app')

@section('title', 'Appointment Details')

@section('content')
    @php
        $formatDate = function ($value, $fallback = '-') {
            if (empty($value)) {
                return $fallback;
            }

            try {
                return \Carbon\Carbon::parse($value);
            } catch (\Throwable $e) {
                return $value;
            }
        };
        $formatBytes = function ($bytes) {
            if (empty($bytes)) {
                return 'Not available';
            }

            $units = ['B', 'KB', 'MB', 'GB'];
            $size = (float) $bytes;
            $unit = 0;

            while ($size >= 1024 && $unit < count($units) - 1) {
                $size /= 1024;
                $unit++;
            }

            return round($size, $unit === 0 ? 0 : 1).' '.$units[$unit];
        };
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
        $fileMeta = function (?string $extension) {
            $ext = strtolower($extension ?? '');

            return match (true) {
                str_contains($ext, 'pdf') => ['bg' => 'bg-danger-50', 'text' => 'text-danger-600', 'label' => 'PDF'],
                in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']) => ['bg' => 'bg-info-50', 'text' => 'text-info-600', 'label' => 'IMG'],
                in_array($ext, ['doc', 'docx']) => ['bg' => 'bg-brand-50', 'text' => 'text-brand-600', 'label' => 'DOC'],
                in_array($ext, ['xls', 'xlsx', 'csv']) => ['bg' => 'bg-success-50', 'text' => 'text-success-600', 'label' => 'XLS'],
                default => ['bg' => 'bg-slate-100', 'text' => 'text-slate-500', 'label' => 'FILE'],
            };
        };

        // The ERP stores the Schedule Appointment's precise time range as "16:29:00 - 19:32:00" —
        // reformat each side to match the rest of the app's 12-hour time style.
        $formatTimeRange = function (?string $range) {
            if (empty($range)) {
                return null;
            }

            $parts = array_map('trim', explode(' - ', $range));
            if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
                return $range;
            }

            $part = function (string $t) {
                try {
                    return \Carbon\Carbon::createFromFormat('H:i:s', $t)->format('g:i A');
                } catch (\Throwable $e) {
                    return $t;
                }
            };

            return $part($parts[0]).' – '.$part($parts[1]);
        };

        $meta = $statusMeta($appointment->status);
        $agencyName = $appointmentAgencyName ?: ($patientIdentity?->agency_id ? 'Agency #'.$patientIdentity->agency_id : 'Not available');
        $hasSchedule = !empty($appointment->appointment_date);
        $scheduleTimeRange = $formatTimeRange($schedule['timeRange'] ?? null);
        $locationAddress = $schedule['address'] ?? null;

        // Mirrors the ERP admin's "Schedule Appointment" / "Telehealth Appointment" split —
        // a patient can have either, both, or neither. When only telehealth exists, it leads
        // the hero; when both exist, the schedule leads and telehealth gets its own card below.
        $dateObj = $hasSchedule ? $formatDate($appointment->appointment_date) : ($telehealth ? $formatDate($telehealth['date']) : $formatDate(null));
        $isCarbon = $dateObj instanceof \Carbon\Carbon;
        $heroIsTelehealth = !$hasSchedule && $telehealth;

        $telehealthDateObj = $telehealth ? $formatDate($telehealth['date']) : null;
        $telehealthIsCarbon = $telehealthDateObj instanceof \Carbon\Carbon;
    @endphp

    @if ($appointmentUnavailable ?? false)
        <section class="mb-6 flex items-start gap-3 rounded-2xl border border-warning-200 bg-warning-50 px-5 py-4 text-sm font-medium text-warning-800 shadow-soft">
            <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86l-8.18 14.18A2 2 0 004 21h16a2 2 0 001.89-2.96L13.71 3.86a2 2 0 00-3.42 0z" />
            </svg>
            <span>We couldn't refresh this appointment from the appointment system just now — details below may be out of date.</span>
        </section>
    @endif
    @if ($documentsUnavailable ?? false)
        <section class="mb-6 flex items-start gap-3 rounded-2xl border border-warning-200 bg-warning-50 px-5 py-4 text-sm font-medium text-warning-800 shadow-soft">
            <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86l-8.18 14.18A2 2 0 004 21h16a2 2 0 001.89-2.96L13.71 3.86a2 2 0 00-3.42 0z" />
            </svg>
            <span>We couldn't load documents for this appointment just now, so nothing is shown below. Please refresh in a moment.</span>
        </section>
    @endif

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('appointments.index') }}" class="-mx-2 -my-1 inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-sm font-semibold text-slate-600 outline-none transition hover:bg-slate-100 hover:text-brand-700 focus-visible:ring-2 focus-visible:ring-brand-500/40">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
            </svg>
            Back to appointments
        </a>
        <div class="flex items-center gap-3">
            <span class="text-sm font-medium text-slate-400">Appointment #{{ $appointment->erp_appointment_id }}</span>
            @if ($appointment->status)
                <span class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-semibold ring-1 ring-inset {{ $meta['bg'] }} {{ $meta['text'] }} {{ $meta['ring'] }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $meta['dot'] }}"></span>
                    {{ $appointment->status }}
                </span>
            @endif
        </div>
    </div>

    <section class="animate-fade-slide-up relative mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft">
        <div class="relative flex flex-col gap-3 overflow-hidden bg-gradient-to-br from-[#24425a] via-brand-800 to-brand-950 px-5 py-4 text-white sm:flex-row sm:items-center sm:justify-between sm:px-7 sm:py-4">
            <div class="pointer-events-none absolute -right-10 -top-16 h-56 w-56 rounded-full border border-white/10" aria-hidden="true"></div>
            <div class="pointer-events-none absolute right-16 top-10 h-24 w-24 rounded-full border border-white/10" aria-hidden="true"></div>
            <div class="relative flex min-w-0 items-center gap-3">
                @if ($isCarbon)
                    <div class="flex h-10 w-10 shrink-0 flex-col items-center justify-center rounded-lg bg-white/[0.08] ring-1 ring-white/15">
                        <span class="text-[0.55rem] font-semibold uppercase leading-none tracking-wider text-brand-200">{{ $dateObj->format('M') }}</span>
                        <span class="text-sm font-bold leading-none text-white">{{ $dateObj->format('d') }}</span>
                    </div>
                @endif
                <div class="min-w-0">
                    <p class="flex items-center gap-1.5 text-[0.65rem] font-semibold uppercase tracking-wider text-brand-200">
                        @if ($heroIsTelehealth)
                            <svg class="h-3 w-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><rect x="2.5" y="6" width="13" height="12" rx="2" /><path stroke-linecap="round" stroke-linejoin="round" d="M15.5 10.5l6-3.5v10l-6-3.5" /></svg>
                            Telehealth Appointment
                        @else
                            {{ $isCarbon ? $dateObj->format('l') : 'Appointment date' }}
                        @endif
                    </p>
                    <h1 class="text-lg font-bold leading-tight tracking-tight sm:text-xl">{{ $isCarbon ? $dateObj->format('F j, Y') : $dateObj }}</h1>
                    <p class="mt-0.5 flex items-center gap-1.5 text-xs font-medium text-brand-100/80">
                        <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path stroke-linecap="round" d="M12 7v5l3 2" /></svg>
                        {{ $heroIsTelehealth ? ($telehealth['time'] ?: '-') : ($scheduleTimeRange ?: ($appointment->appointment_time ?: '-')) }}
                        @if ($heroIsTelehealth && !empty($telehealth['nurse']))
                            <span class="text-brand-200">&middot;</span> Nurse: {{ $telehealth['nurse'] }}
                        @endif
                    </p>
                </div>
            </div>

            <dl class="relative grid grid-cols-2 gap-x-6 gap-y-2 border-t border-white/10 pt-3 sm:grid-cols-4 sm:gap-y-0 sm:border-t-0 sm:border-l sm:pl-6 sm:pt-0">
                <div>
                    <dt class="flex items-center gap-1.5 text-[0.65rem] font-semibold uppercase tracking-wider text-brand-200">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21V8l9-5 9 5v13M9 21v-6h6v6" /></svg>
                        Agency
                    </dt>
                    <dd class="truncate text-sm font-semibold text-white">{{ $agencyName }}</dd>
                </div>
                @if ($locationAddress)
                    <div>
                        <dt class="flex items-center gap-1.5 text-[0.65rem] font-semibold uppercase tracking-wider text-brand-200">
                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-6.5 7-11.5a7 7 0 10-14 0C5 14.5 12 21 12 21z" /><circle cx="12" cy="9.5" r="2.25" /></svg>
                            Location
                        </dt>
                        <dd class="truncate text-sm font-semibold text-white" title="{{ $locationAddress }}">{{ $locationAddress }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="flex items-center gap-1.5 text-[0.65rem] font-semibold uppercase tracking-wider text-brand-200">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3.5h6M12 3.5v3M6 20.5h12a2 2 0 002-2v-6a6 6 0 10-16 0v6a2 2 0 002 2z" /></svg>
                        Service
                    </dt>
                    <dd class="truncate text-sm font-semibold text-white">{{ $appointment->service_name ?: 'Not available' }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="text-[0.65rem] font-semibold uppercase tracking-wider text-brand-200">Status</dt>
                    <dd class="mt-0.5">
                        @if ($appointment->status)
                            {{-- No `truncate` here: it clips a badge mid-word instead of ellipsizing
                                 cleanly (`text-overflow` doesn't apply meaningfully to a nested
                                 inline-flex child). Letting it wrap onto a second line instead. --}}
                            <span class="inline-flex max-w-full items-center gap-1.5 whitespace-normal rounded-md px-2 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $meta['bg'] }} {{ $meta['text'] }} {{ $meta['ring'] }}">
                                <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $meta['dot'] }}"></span>
                                {{ $appointment->status }}
                            </span>
                        @else
                            <span class="text-sm font-semibold text-white">Not available</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="flex items-center gap-1.5 text-[0.65rem] font-semibold uppercase tracking-wider text-brand-200">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3.5h7l4 4V20a1 1 0 01-1 1H7a1 1 0 01-1-1V4.5a1 1 0 011-1z" /><path stroke-linecap="round" stroke-linejoin="round" d="M14 3.5V8h4" /></svg>
                        Documents
                    </dt>
                    <dd class="truncate text-sm font-semibold text-white">{{ $documents->count() }} {{ \Illuminate\Support\Str::plural('file', $documents->count()) }}</dd>
                </div>
            </dl>
        </div>
    </section>

    @if ($hasSchedule && $telehealth)
        {{-- Both a scheduled and a telehealth appointment exist on this record — mirrors the
             ERP admin's <hr>-separated "Schedule Appointment" / "Telehealth Appointment" split. --}}
        <section class="mb-6 overflow-hidden rounded-2xl border border-info-100 bg-info-50/40 shadow-soft">
            <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-info-100 text-info-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><rect x="2.5" y="6" width="13" height="12" rx="2" /><path stroke-linecap="round" stroke-linejoin="round" d="M15.5 10.5l6-3.5v10l-6-3.5" /></svg>
                    </span>
                    <div>
                        <span class="inline-flex items-center rounded-md bg-info-100 px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide text-info-700">Telehealth Appointment</span>
                        <p class="mt-1 text-base font-semibold text-slate-900">{{ $telehealthIsCarbon ? $telehealthDateObj->format('F j, Y') : $telehealthDateObj }}</p>
                    </div>
                </div>
                <dl class="flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-info-100 pt-4 sm:border-t-0 sm:pt-0">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-info-600">Time</dt>
                        <dd class="mt-0.5 text-sm font-semibold text-slate-800">{{ $telehealth['time'] ?: '-' }}</dd>
                    </div>
                    @if (!empty($telehealth['nurse']))
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-info-600">Nurse</dt>
                            <dd class="mt-0.5 text-sm font-semibold text-slate-800">{{ $telehealth['nurse'] }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </section>
    @endif

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-8">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Documents</h2>
                <p class="mt-0.5 text-sm text-slate-500">Files available for this appointment.</p>
            </div>
            <span class="w-fit rounded-md bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">{{ $documents->count() }} {{ \Illuminate\Support\Str::plural('document', $documents->count()) }}</span>
        </div>

        @if ($documents->isEmpty())
            <x-empty-state title="No documents are available for this appointment yet." description="Files added by your agency will show up here automatically.">
                <x-slot:icon>
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 3.5h7l4 4V20a1 1 0 01-1 1H7a1 1 0 01-1-1V4.5a1 1 0 011-1z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 3.5V8h4" />
                    </svg>
                </x-slot:icon>
            </x-empty-state>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($documents as $document)
                    @php $file = $fileMeta($document->extension); @endphp
                    <li class="group flex flex-col gap-3 px-6 py-4 transition hover:bg-slate-50/70 hover:shadow-[inset_3px_0_0_var(--color-brand-500)] sm:flex-row sm:items-center sm:justify-between sm:px-8">
                        <div class="flex min-w-0 items-center gap-3.5">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $file['bg'] }} {{ $file['text'] }} text-[0.65rem] font-bold tracking-wide transition-transform duration-200 group-hover:scale-105">
                                {{ $file['label'] }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $document->document_name ?: 'Document' }}</p>
                                <p class="mt-0.5 truncate text-xs text-slate-500">Doc #{{ $document->erp_document_id }} &middot; {{ $formatBytes($document->size_in_bytes) }}</p>
                            </div>
                        </div>
                        <a href="{{ route('documents.download', $document) }}" aria-label="Download {{ $document->document_name ?: 'document' }}" class="inline-flex w-full shrink-0 items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-700 shadow-sm outline-none transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-800 focus-visible:ring-2 focus-visible:ring-brand-500/40 active:scale-[0.97] sm:w-auto sm:py-2">
                            <svg class="h-4 w-4 transition-transform duration-200 group-hover:translate-y-0.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M10 2a.75.75 0 01.75.75v7.19l2.22-2.22a.75.75 0 111.06 1.06l-3.5 3.5a.75.75 0 01-1.06 0l-3.5-3.5a.75.75 0 111.06-1.06l2.22 2.22V2.75A.75.75 0 0110 2z" />
                                <path d="M4 13a.75.75 0 01.75.75v1.5c0 .414.336.75.75.75h9a.75.75 0 00.75-.75v-1.5a.75.75 0 011.5 0v1.5A2.25 2.25 0 0114.5 17.5h-9A2.25 2.25 0 013.25 15.25v-1.5A.75.75 0 014 13z" />
                            </svg>
                            Download
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
