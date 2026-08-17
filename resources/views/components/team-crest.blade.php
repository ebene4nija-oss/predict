@props([
    'team' => null,
    'name' => '',
    'size' => 'md',
])

@php
    /**
     * A club badge, or its initials when we hold no crest.
     *
     * Never renders a broken <img>: fixtures ingested before the teams table
     * existed have no club record at all, and some clubs have no crest on the
     * provider, so the initials tile is the normal path rather than an error
     * state.
     */
    $sizes = [
        'sm' => ['box' => 'w-6 h-6', 'text' => 'text-[9px]'],
        'md' => ['box' => 'w-9 h-9', 'text' => 'text-[11px]'],
        'lg' => ['box' => 'w-14 h-14', 'text' => 'text-sm'],
    ];

    $dimensions = $sizes[$size] ?? $sizes['md'];
    $crest = $team?->crest();
    $label = $team?->name ?: $name;

    $initials = $team?->initials() ?: strtoupper(mb_substr(trim((string) $name), 0, 3)) ?: '?';
@endphp

@if($crest)
    <img src="{{ $crest }}"
         alt="{{ $label }} crest"
         loading="lazy"
         class="{{ $dimensions['box'] }} object-contain shrink-0 {{ $attributes->get('class') }}">
@else
    <span title="{{ $label }}"
          class="{{ $dimensions['box'] }} {{ $dimensions['text'] }} shrink-0 rounded-full bg-slate-800 border border-slate-700 text-slate-300 font-extrabold flex items-center justify-center {{ $attributes->get('class') }}">
        {{ $initials }}
    </span>
@endif
