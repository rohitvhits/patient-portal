@extends('layouts.guest')

@section('title', 'Verify code')

@section('content')
    <p class="mb-5 text-sm text-gray-600">
        Enter the 6-digit code sent to
        <span class="font-medium text-gray-900">{{ $mobile }}</span>.
    </p>

    <form method="POST" action="{{ route('login.verify-otp') }}" id="otp-form">
        @csrf

        <div class="flex justify-between gap-2" id="otp-boxes" role="group" aria-label="6-digit verification code">
            @for ($i = 0; $i < 6; $i++)
                <input
                    type="text"
                    inputmode="numeric"
                    maxlength="1"
                    autocomplete="one-time-code"
                    aria-label="Digit {{ $i + 1 }} of 6"
                    @if ($i === 0) autofocus @endif
                    class="h-12 w-11 rounded-lg border-0 text-center text-lg font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-brand-500"
                >
            @endfor
        </div>

        <input type="hidden" name="otp" id="otp-value">

        <button
            type="submit"
            class="mt-6 flex w-full items-center justify-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
        >
            Verify &amp; sign in
        </button>
    </form>

    <div class="mt-5 flex items-center justify-between text-sm">
        <a href="{{ route('login') }}" class="font-medium text-gray-500 hover:text-gray-700">&larr; Change number</a>
        <form method="POST" action="{{ route('login.request-otp') }}">
            @csrf
            <input type="hidden" name="mobile" value="{{ $mobile }}">
            <button type="submit" class="font-medium text-brand-600 hover:text-brand-700">Resend code</button>
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
