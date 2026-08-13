@extends('layouts.guest')

@section('title', 'Sign in')

@section('content')
    <form method="POST" action="{{ route('login.request-otp') }}" class="space-y-5">
        @csrf

        <div>
            <label for="mobile" class="block text-sm font-medium text-gray-700">Mobile number</label>
            <div class="mt-1.5">
                <input
                    type="tel"
                    inputmode="numeric"
                    autocomplete="tel"
                    id="mobile"
                    name="mobile"
                    value="{{ old('mobile') }}"
                    placeholder="(555) 123-4567"
                    required
                    autofocus
                    class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-brand-500 sm:text-sm"
                >
            </div>
            <p class="mt-2 text-xs text-gray-500">Use the mobile number on file with your appointment.</p>
        </div>

        <button
            type="submit"
            class="flex w-full items-center justify-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
        >
            Send verification code
        </button>
    </form>
@endsection
