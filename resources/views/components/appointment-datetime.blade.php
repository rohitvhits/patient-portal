@props([
    'hasSchedule' => false,
    'date' => null,
    'time' => null,
    'telehealth' => null,
])

{{--
    Mirrors the ERP admin's "Appointment Date - Location / Telehealth Date" column
    (patient_list.blade.php): a "Schedule Appointment" badge + date/time when `appointment_date`
    is set, and/or a "Telehealth Appointment" badge + date/time/nurse when telehealth data is
    set. The ERP's own blade branches on `patient_master.type` ("Caregiver" vs "Patient") only to
    pick which raw columns to read the date/time out of — the badge itself is driven purely by
    which of those fields actually has data, which is exactly what `hasSchedule`/`$telehealth`
    already represent here. A row can have a scheduled appointment, a telehealth appointment,
    both, or (rarely) neither.
--}}
@if ($hasSchedule)
    <span class="inline-flex items-center gap-1 rounded-md bg-success-50 px-1.5 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide text-success-700">
        Schedule Appointment
    </span>
    <p class="mt-1 font-semibold text-slate-900">{{ $date }}</p>
    <p class="mt-0.5 flex items-center gap-1 text-xs font-medium text-slate-500">
        <svg class="h-3 w-3 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path stroke-linecap="round" d="M12 7v5l3 2" /></svg>
        {{ $time }}
    </p>
    @if ($telehealth)
        <div class="mt-2 border-t border-slate-100 pt-2">
            <span class="inline-flex items-center gap-1 rounded-md bg-info-50 px-1.5 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide text-info-700">
                <svg class="h-3 w-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2.5" y="6" width="13" height="12" rx="2" /><path stroke-linecap="round" stroke-linejoin="round" d="M15.5 10.5l6-3.5v10l-6-3.5" /></svg>
                Telehealth Appointment
            </span>
            <p class="mt-1 font-semibold text-slate-900">{{ $telehealth['date'] }}</p>
            <p class="mt-0.5 flex items-center gap-1 text-xs font-medium text-slate-500">
                <svg class="h-3 w-3 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path stroke-linecap="round" d="M12 7v5l3 2" /></svg>
                {{ $telehealth['time'] ?: '-' }}
            </p>
            @if (!empty($telehealth['nurse']))
                <p class="mt-0.5 text-xs font-medium text-slate-500">Nurse: {{ $telehealth['nurse'] }}</p>
            @endif
        </div>
    @endif
@elseif ($telehealth)
    <span class="inline-flex items-center gap-1 rounded-md bg-info-50 px-1.5 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide text-info-700">
        <svg class="h-3 w-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2.5" y="6" width="13" height="12" rx="2" /><path stroke-linecap="round" stroke-linejoin="round" d="M15.5 10.5l6-3.5v10l-6-3.5" /></svg>
        Telehealth Appointment
    </span>
    <p class="mt-1 font-semibold text-slate-900">{{ $telehealth['date'] }}</p>
    <p class="mt-0.5 flex items-center gap-1 text-xs font-medium text-slate-500">
        <svg class="h-3 w-3 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path stroke-linecap="round" d="M12 7v5l3 2" /></svg>
        {{ $telehealth['time'] ?: '-' }}
    </p>
    @if (!empty($telehealth['nurse']))
        <p class="mt-0.5 text-xs font-medium text-slate-500">Nurse: {{ $telehealth['nurse'] }}</p>
    @endif
@else
    <p class="font-semibold text-slate-900">{{ $date }}</p>
    <p class="mt-0.5 flex items-center gap-1 text-xs font-medium text-slate-500">
        <svg class="h-3 w-3 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path stroke-linecap="round" d="M12 7v5l3 2" /></svg>
        {{ $time }}
    </p>
@endif
