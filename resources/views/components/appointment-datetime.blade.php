@props([
    'hasSchedule' => false,
    'date' => null,
    'time' => null,
    'telehealth' => null,
])

{{--
    Mirrors the ERP admin's "Schedule Appointment" / "Telehealth Appointment" split: a row can
    have a scheduled appointment, a telehealth appointment, both, or (rarely) neither.
--}}
@if ($hasSchedule)
    <p class="font-semibold text-slate-900">{{ $date }}</p>
    <p class="mt-0.5 flex items-center gap-1 text-xs font-medium text-slate-500">
        <svg class="h-3 w-3 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path stroke-linecap="round" d="M12 7v5l3 2" /></svg>
        {{ $time }}
    </p>
    @if ($telehealth)
        <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-info-600">
            <svg class="h-3 w-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2.5" y="6" width="13" height="12" rx="2" /><path stroke-linecap="round" stroke-linejoin="round" d="M15.5 10.5l6-3.5v10l-6-3.5" /></svg>
            Telehealth &middot; {{ $telehealth['date'] }}
        </p>
    @endif
@elseif ($telehealth)
    <span class="inline-flex items-center gap-1 rounded-md bg-info-50 px-1.5 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide text-info-700">
        <svg class="h-3 w-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2.5" y="6" width="13" height="12" rx="2" /><path stroke-linecap="round" stroke-linejoin="round" d="M15.5 10.5l6-3.5v10l-6-3.5" /></svg>
        Telehealth
    </span>
    <p class="mt-1 font-semibold text-slate-900">{{ $telehealth['date'] }}</p>
    <p class="mt-0.5 flex items-center gap-1 text-xs font-medium text-slate-500">
        <svg class="h-3 w-3 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path stroke-linecap="round" d="M12 7v5l3 2" /></svg>
        {{ $telehealth['time'] ?: '-' }}
    </p>
@else
    <p class="font-semibold text-slate-900">{{ $date }}</p>
    <p class="mt-0.5 flex items-center gap-1 text-xs font-medium text-slate-500">
        <svg class="h-3 w-3 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path stroke-linecap="round" d="M12 7v5l3 2" /></svg>
        {{ $time }}
    </p>
@endif
