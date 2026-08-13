@extends('layouts.app')

@section('title', 'My Appointments')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-semibold text-gray-900">My Appointments</h1>
    </div>

    @if ($appointments->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
            <p class="text-sm font-medium text-gray-900">No appointments found</p>
            <p class="mt-1 text-sm text-gray-500">Appointments booked with NY Best Medical will appear here.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($appointments as $appointment)
                @php
                    $status = strtolower($appointment->status ?? '');
                    $badge = match (true) {
                        str_contains($status, 'complet') => 'bg-green-50 text-green-700 ring-green-600/20',
                        str_contains($status, 'cancel') => 'bg-red-50 text-red-700 ring-red-600/20',
                        str_contains($status, 'pending') => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                        default => 'bg-gray-50 text-gray-600 ring-gray-500/20',
                    };
                @endphp
                <a
                    href="{{ route('appointments.show', $appointment) }}"
                    class="flex items-center justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 transition hover:ring-brand-200"
                >
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="font-medium text-gray-900">
                                {{ $appointment->appointment_date ?? 'Date TBD' }}
                            </p>
                            @if ($appointment->appointment_time)
                                <span class="text-sm text-gray-500">{{ $appointment->appointment_time }}</span>
                            @endif
                        </div>
                        <p class="mt-1 truncate text-sm text-gray-500">
                            {{ $appointment->doctor_name ?? 'Provider TBD' }}
                            @if ($appointment->location_name)
                                &middot; {{ $appointment->location_name }}
                            @endif
                        </p>
                        @if ($appointment->service_name)
                            <p class="mt-0.5 truncate text-xs text-brand-600">{{ $appointment->service_name }}</p>
                        @endif
                    </div>

                    <div class="flex shrink-0 items-center gap-3">
                        @if ($appointment->status)
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $badge }}">
                                {{ $appointment->status }}
                            </span>
                        @endif
                        <svg class="h-5 w-5 text-gray-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
