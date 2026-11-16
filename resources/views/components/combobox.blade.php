{{--
    Combobox: select yang bisa dicari dengan filter dan dukungan multi-select.

    Sumber kebenaran adalah <select> native di dalam komponen ini. JavaScript
    membangun antarmuka pencarian di atasnya, jadi tanpa JavaScript pilihan
    tetap tampil dan formulir tetap terkirim seperti biasa.

    Pemakaian:
    <x-combobox name="jenis" label="Jenis" :options="['a' => 'Dinas']" />
    <x-combobox name="satker" :options="$satker" :multiple="true" placeholder="Pilih Satuan Kerja..." />
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
    'multiple' => false,
])

@php
    $id = $id ?: str_replace(['[', ']'], '', $name);
    $errorId = $id . '-galat';
    $inputName = $multiple && !str_ends_with($name, '[]') ? $name . '[]' : $name;
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

    if ($multiple) {
        $terpilih = is_array($selected)
            ? array_map('strval', $selected)
            : ($selected !== null && $selected !== '' ? [(string) $selected] : []);
    } else {
        $terpilih = is_array($selected) ? (string) (reset($selected) ?: '') : (string) ($selected ?? '');
    }
@endphp

<div class="field {{ $kelas }}">
    @if ($label)
        <label for="{{ $id }}">
            {{ $label }}@if ($wajib) <span class="wajib" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <select
        class="select js-combobox-asal"
        name="{{ $inputName }}"
        id="{{ $id }}"
        @if ($multiple) multiple data-multi="1" @endif
        @if ($wajib) required @endif
        @if ($disabled) disabled @endif
        @if ($error) aria-invalid="true" aria-describedby="{{ $errorId }}" @endif
        data-placeholder="{{ $placeholder }}"
        data-cari="{{ $cari }}"
        data-keterangan="{{ $keterangan ? '1' : '0' }}"
    >
        @if (! $multiple && $kosong !== null)
            <option value="">{{ $kosong }}</option>
        @endif

        @foreach ($butir as $o)
            @php
                $isAktif = $multiple
                    ? in_array((string) $o['nilai'], $terpilih, true)
                    : ((string) $o['nilai'] === (string) $terpilih);
            @endphp
            <option
                value="{{ $o['nilai'] }}"
                @selected($isAktif)
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
