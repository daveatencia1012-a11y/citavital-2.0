@props(['tamano' => 44])

<svg
    {{ $attributes->merge(['class' => 'logo-citavital']) }}
    width="{{ $tamano }}"
    height="{{ $tamano }}"
    viewBox="0 0 48 48"
    fill="none"
    aria-hidden="true"
    focusable="false"
>
    <rect width="48" height="48" rx="10" fill="#1677C8"/>
    <rect x="10.5" y="12.5" width="27" height="25" rx="6" stroke="white" stroke-width="2.4"/>
    <path d="M10.5 20.5h27" stroke="white" stroke-width="2.4" stroke-linecap="round"/>
    <path d="M17 8.5v7M31 8.5v7" stroke="white" stroke-width="2.4" stroke-linecap="round"/>
    <path d="M16.5 28.5h4l2.2-4.2 3.1 8.2 2.2-4h3.5" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
