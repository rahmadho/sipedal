{{--
    Paginasi server-side.
    Info baris/halaman di sisi kiri, navigator tombol di sisi kanan.
    Pemakaian:
    <x-paginasi :halaman="$halaman" :per-halaman="$perHalaman" :total="$total" />
--}}
@props(['halaman' => 1, 'perHalaman' => 25, 'total' => 0])

@php
    $totalHalaman = max(1, (int) ceil($total / max(1, $perHalaman)));
    $halaman = min(max(1, (int) $halaman), $totalHalaman);
    $dari = $total === 0 ? 0 : (($halaman - 1) * $perHalaman) + 1;
    $sampai = min($halaman * $perHalaman, $total);

    // Jendela nomor halaman: maksimal 5, mengikuti posisi aktif.
    $mulai = max(1, min($halaman - 2, $totalHalaman - 4));
    $akhir = min($totalHalaman, $mulai + 4);
@endphp

<div class="paginasi" style="display:flex;align-items:center;justify-content:space-between;width:100%;box-sizing:border-box">
    <div class="paginasi__info" style="margin-right:auto;text-align:left">
        Menampilkan <b>{{ angka($dari) }}–{{ angka($sampai) }}</b> dari <b>{{ angka($total) }}</b> baris
    </div>

    <div class="paginasi__nav" role="navigation" aria-label="Navigasi halaman" style="margin-left:auto;display:flex;align-items:center;gap:3px">
        <a
            class="paginasi__btn"
            href="{{ request()->fullUrlWithQuery(['halaman' => max(1, $halaman - 1), 'per_halaman' => $perHalaman]) }}"
            @if ($halaman <= 1) aria-disabled="true" tabindex="-1" style="pointer-events:none;opacity:.45" @endif
            aria-label="Halaman sebelumnya"
        ><x-ikon nama="chevron-left" :ukuran="14" /></a>

        @for ($i = $mulai; $i <= $akhir; $i++)
            <a
                class="paginasi__btn {{ $i === $halaman ? 'is-active' : '' }}"
                href="{{ request()->fullUrlWithQuery(['halaman' => $i, 'per_halaman' => $perHalaman]) }}"
                @if ($i === $halaman) aria-current="page" @endif
            >{{ angka($i) }}</a>
        @endfor

        <a
            class="paginasi__btn"
            href="{{ request()->fullUrlWithQuery(['halaman' => min($totalHalaman, $halaman + 1), 'per_halaman' => $perHalaman]) }}"
            @if ($halaman >= $totalHalaman) aria-disabled="true" tabindex="-1" style="pointer-events:none;opacity:.45" @endif
            aria-label="Halaman berikutnya"
        ><x-ikon nama="chevron-right" :ukuran="14" /></a>
    </div>
</div>
