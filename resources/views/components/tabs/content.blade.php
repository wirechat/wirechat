@props([
    'active' => true,
])

<div
    {{
        $attributes->merge([
            'role' => 'tabpanel',
            'aria-hidden' => $active ? 'false' : 'true',
        ])
    }}
    @if (! $active)
        hidden
    @endif
>
    {{ $slot }}
</div>
