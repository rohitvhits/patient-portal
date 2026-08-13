@extends('layouts.app')

@section('title', 'Appointment Details')

@section('content')
    <a href="{{ route('appointments.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700">
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
        </svg>
        Back to appointments
    </a>

    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 sm:p-8">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">
                    {{ $appointment->appointment_date ?? 'Date TBD' }}
                    @if ($appointment->appointment_time)
                        <span class="font-normal text-gray-500">at {{ $appointment->appointment_time }}</span>
                    @endif
                </h1>
                <p class="mt-1 text-sm text-gray-500">Appointment #{{ $appointment->erp_appointment_id }}</p>
            </div>
            @if ($appointment->status)
                <span class="inline-flex items-center rounded-full bg-gray-50 px-3 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/20">
                    {{ $appointment->status }}
                </span>
            @endif
        </div>

        <dl class="mt-6 grid grid-cols-1 gap-x-6 gap-y-4 border-t border-gray-100 pt-6 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Provider</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $appointment->doctor_name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Location</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $appointment->location_name ?? '—' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Service</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $appointment->service_name ?? '—' }}</dd>
            </div>
        </dl>
    </div>

    <div class="mt-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 sm:p-8">
        <h2 class="text-sm font-semibold text-gray-900">Documents</h2>

        @if ($documents->isEmpty())
            <p class="mt-3 text-sm text-gray-500">No documents are available for this appointment yet.</p>
        @else
            <ul class="mt-4 divide-y divide-gray-100">
                @foreach ($documents as $document)
                    <li class="flex items-center justify-between gap-4 py-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-900">{{ $document->document_name ?? 'Document' }}</p>
                                @if ($document->extension)
                                    <p class="text-xs uppercase text-gray-400">{{ $document->extension }}</p>
                                @endif
                            </div>
                        </div>

                        <a
                            href="{{ route('documents.download', $document) }}"
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-brand-700"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v6.59l1.95-2.1a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 111.1-1.02l1.95 2.1V3.75A.75.75 0 0110 3zM3.5 12.75a.75.75 0 01.75.75v2.5a.5.5 0 00.5.5h10.5a.5.5 0 00.5-.5v-2.5a.75.75 0 011.5 0v2.5a2 2 0 01-2 2H4.75a2 2 0 01-2-2v-2.5a.75.75 0 01.75-.75z" clip-rule="evenodd" />
                            </svg>
                            Download
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
