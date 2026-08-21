@extends('layouts.guest')

@section('title', 'Verify code')
@section('heading', 'Verify your number')
@section('subtitle', 'Enter the 6-digit code we sent you')

@section('content')
    <p class="mb-5 text-center text-sm text-slate-600">
        Code sent to <span class="font-semibold text-slate-900">{{ $mobile }}</span>
    </p>

    <form method="POST" action="{{ route('login.verify-otp') }}" id="otp-form" data-button-loader>
        @csrf

        {{-- flex-1 + min-w-0 + a max-width cap means these 6 boxes always share whatever
             width is actually available and can never force horizontal overflow, no matter
             how narrow the phone — fixed pixel widths here broke on sub-390px screens. --}}
        <div class="flex gap-1.5 sm:gap-2" id="otp-boxes" role="group" aria-label="6-digit verification code">
            @for ($i = 0; $i < 6; $i++)
                <input
                    type="text"
                    inputmode="numeric"
                    maxlength="1"
                    autocomplete="one-time-code"
                    aria-label="Digit {{ $i + 1 }} of 6"
                    @if ($i === 0) autofocus @endif
                    class="h-12 min-w-0 max-w-[2.75rem] flex-1 rounded-lg border border-slate-200 bg-slate-50/60 text-center text-lg font-semibold text-slate-900 outline-none transition focus:border-brand-400 focus:bg-white focus:ring-4 focus:ring-brand-500/10"
                >
            @endfor
        </div>

        <input type="hidden" name="otp" id="otp-value">

        <button
            type="submit"
            data-loading-text="Verifying…"
            class="mt-6 flex w-full items-center justify-center gap-2 rounded-lg bg-success-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-success-700 focus:outline-none focus:ring-4 focus:ring-success-500/20 disabled:hover:bg-success-600"
        >
            Verify &amp; sign in
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
            </svg>
        </button>
    </form>

    <div class="mt-5 flex items-center justify-between text-sm">
        <a href="{{ route('login') }}" class="inline-flex items-center gap-1 font-semibold text-slate-500 transition hover:text-slate-700">
            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
            </svg>
            Change number
        </a>
        <form method="POST" action="{{ route('login.request-otp') }}" data-button-loader>
            @csrf
            <input type="hidden" name="mobile" value="{{ $mobile }}">
            <button type="submit" data-loading-text="Resending…" class="font-semibold text-brand-700 transition hover:text-brand-900">Resend code</button>
        </form>
    </div>

    <script>
        (function () {
            const boxes = Array.from(document.querySelectorAll('#otp-boxes input'));
            const hidden = document.getElementById('otp-value');
            const form = document.getElementById('otp-form');

            boxes.forEach((box, index) => {
                box.addEventListener('input', () => {
                    box.value = box.value.replace(/[^0-9]/g, '').slice(-1);
                    if (box.value && index < boxes.length - 1) {
                        boxes[index + 1].focus();
                    }
                });

                box.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !box.value && index > 0) {
                        boxes[index - 1].focus();
                    }
                });

                box.addEventListener('paste', (e) => {
                    e.preventDefault();
                    const digits = (e.clipboardData.getData('text') || '').replace(/[^0-9]/g, '').split('');
                    boxes.forEach((b, i) => { b.value = digits[i] || ''; });
                    const last = Math.min(digits.length, boxes.length) - 1;
                    if (last >= 0) boxes[last].focus();
                });
            });

            form.addEventListener('submit', () => {
                hidden.value = boxes.map((b) => b.value).join('');
            });
        })();
    </script>
@endsection
