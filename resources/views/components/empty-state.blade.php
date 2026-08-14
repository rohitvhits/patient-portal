@props([
    'title',
    'description' => null,
    'compact' => false,
])

@if ($compact)
    <div {{ $attributes->merge(['class' => 'px-6 py-10 text-center text-sm font-medium text-slate-500']) }}>
        {{ $title }}
    </div>
@else
    <div {{ $attributes->merge(['class' => 'px-6 py-14 text-center']) }}>
        @isset($icon)
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                {{ $icon }}
            </span>
        @endisset
        <p class="{{ isset($icon) ? 'mt-4' : '' }} text-sm font-semibold text-slate-900">{{ $title }}</p>
        @if ($description)
            <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
        @endif
    </div>
@endif
