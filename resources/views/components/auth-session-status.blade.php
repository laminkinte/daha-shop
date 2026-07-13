@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-2.5 text-sm font-medium']) }}>
        {{ $status }}
    </div>
@endif
