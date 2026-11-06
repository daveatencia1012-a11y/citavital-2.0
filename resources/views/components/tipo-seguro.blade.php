@props(['tipo'])

@php
    $tipo = $tipo instanceof \App\Enums\InsuranceType
        ? $tipo
        : \App\Enums\InsuranceType::from($tipo);
@endphp

<span class="admin-seguro">{{ $tipo->label() }}</span>
