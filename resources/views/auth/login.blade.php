@extends('layouts.guest')

@section('title', 'Sign in')
@section('heading', 'Welcome back')
@section('subtitle', 'Sign in securely using the mobile number associated with your appointment.')

@section('content')
    <form method="POST" action="{{ route('login.request-otp') }}">
        @csrf

        <div>
            <label for="mobile" class="block text-sm font-semibold text-slate-700">Mobile number</label>
            <div class="mt-2 flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50/60 pl-3.5 pr-1 transition focus-within:border-brand-400 focus-within:bg-white focus-within:ring-4 focus-within:ring-brand-500/10">
                <svg class="h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M2 3.5A1.5 1.5 0 013.5 2h1.148a1.5 1.5 0 011.465 1.175l.716 3.223a1.5 1.5 0 01-.826 1.68l-1.293.616a11.037 11.037 0 006.294 6.294l.617-1.293a1.5 1.5 0 011.68-.826l3.222.716A1.5 1.5 0 0118 15.352V16.5a1.5 1.5 0 01-1.5 1.5H15c-7.18 0-13-5.82-13-13v-1z" />
                </svg>
                <span class="shrink-0 text-sm font-medium text-slate-500">+1</span>
                <span class="h-4 w-px shrink-0 bg-slate-200"></span>
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
                    class="block w-full min-w-0 border-0 bg-transparent py-3 pr-2 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:ring-0"
                >
            </div>
            <p class="mt-2 text-xs leading-relaxed text-slate-500">We'll text a 6-digit verification code to this number.</p>
        </div>

        <div class="my-6 border-t border-slate-100"></div>

        <button
            type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-xl bg-success-600 px-4 py-3 text-sm font-semibold text-white shadow-soft transition hover:bg-success-700 focus:outline-none focus:ring-4 focus:ring-success-500/20"
        >
            Send verification code
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
            </svg>
        </button>

        <p class="mt-4 flex items-center justify-center gap-1.5 text-xs text-slate-400">
            <svg class="h-3.5 w-3.5 shrink-0 text-success-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 1a.75.75 0 01.375.1l7.25 4.146a.75.75 0 01.375.65v5.256c0 4.69-3.023 8.65-7.25 10.05a.75.75 0 01-.5 0C6.023 19.7 3 15.742 3 11.052V5.796a.75.75 0 01.375-.65l7.25-4.147A.75.75 0 0110 1zm3.03 6.28a.75.75 0 00-1.06-1.06l-3.72 3.72-1.22-1.22a.75.75 0 00-1.06 1.06l1.75 1.75a.75.75 0 001.06 0l4.25-4.25z" clip-rule="evenodd" />
            </svg>
            Your information is encrypted and kept private.
        </p>
    </form>
@endsection
