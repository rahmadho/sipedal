{{--
    Combobox: select yang bisa dicari.

    Sumber kebenaran adalah <select> native di dalam komponen ini. JavaScript
    membangun antarmuka pencarian di atasnya, jadi tanpa JavaScript pilihan
    tetap tampil dan formulir tetap terkirim seperti biasa.

    Pemakaian:
    <x-combobox name="jenis" label="Jenis" :options="['a' => 'Dinas']" />
    <x-combobox name="satker" :options="$satker" kosong="Pilih satuan kerja"
                :keterangan="true" :wajib="true" hint="..." />
--}}
@props([
    'name' => '',
    'id' => null,
    'label' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => 'Pilih…',
    'hint' => null,
    'kosong' => null,
    'wajib' => false,
    'error' => null,
    'cari' => 'Cari…',
    'keterangan' => false,
    'kelas' => null,
    'disabled' => false,
])

@php
    $id = $id ?: $name;
    $errorId = $id . '-galat';
    $butir = [];

    foreach ($options as $nilai => $opsi) {
        if (is_array($opsi)) {
            $butir[] = [
                'nilai' => (string) ($opsi['nilai'] ?? $nilai),
                'label' => (string) ($opsi['label'] ?? $nilai),
                'keterangan' => (string) ($opsi['keterangan'] ?? ''),
            ];
        } else {
            $butir[] = ['nilai' => (string) $nilai, 'label' => (string) $opsi, 'keterangan' => ''];
        }
    }

    $terpilih = is_array($selected) ? array_map('strval', $selected) : (string) $selected;
@endphp

<div class="field {{ $kelas }}">
    @if ($label)
        <label for="{{ $id }}">
            {{ $label }}@if ($wajib) <span class="wajib" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <select
        class="select js-combobox-asal"
        name="{{ $name }}"
        id="{{ $id }}"
        @if ($wajib) required @endif
        @if ($disabled) disabled @endif
        @if ($error) aria-invalid="true" aria-describedby="{{ $errorId }}" @endif
        data-placeholder="{{ $placeholder }}"
        data-cari="{{ $cari }}"
        data-keterangan="{{ $keterangan ? '1' : '0' }}"
    >
        @if ($kosong !== null)
            <option value="">{{ $kosong }}</option>
        @endif

        @foreach ($butir as $o)
            <option
                value="{{ $o['nilai'] }}"
                @selected($terpilih === $o['nilai'])
                @if ($o['keterangan']) data-keterangan="{{ $o['keterangan'] }}" @endif
            >{{ $o['label'] }}</option>
        @endforeach
    </select>

    @if ($error)
        <div class="field__error" id="{{ $errorId }}">{{ $error }}</div>
    @elseif ($hint)
        <div class="field__hint">{{ $hint }}</div>
    @endif
</div>
