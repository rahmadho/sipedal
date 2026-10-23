<?php

/**
 * Helper tampilan — format angka, rupiah, tanggal, dan durasi.
 *
 * Semua keluaran mengikuti kaidah Indonesia: ribuan titik, desimal koma,
 * tanggal dd/mm/yyyy, zona waktu Asia/Jakarta.
 */

if (! function_exists('angka')) {
    function angka(int|float|null $nilai, int $desimal = 0): string
    {
        return number_format((float) ($nilai ?? 0), $desimal, ',', '.');
    }
}

if (! function_exists('rp')) {
    /** Rupiah penuh: Rp6.362.688.994.450 */
    function rp(int|float|null $nilai): string
    {
        return 'Rp' . angka($nilai);
    }
}

if (! function_exists('ringkas')) {
    /** Rupiah ringkas: Rp6,36 T · Rp203,93 M · Rp302,85 Jt */
    function ringkas(int|float|null $nilai): string
    {
        $n = (float) ($nilai ?? 0);
        $a = abs($n);

        return match (true) {
            $a >= 1_000_000_000_000 => 'Rp' . number_format($n / 1_000_000_000_000, 2, ',', '.') . ' T',
            $a >= 1_000_000_000     => 'Rp' . number_format($n / 1_000_000_000, 2, ',', '.') . ' M',
            $a >= 1_000_000         => 'Rp' . number_format($n / 1_000_000, 2, ',', '.') . ' Jt',
            $a >= 1_000             => 'Rp' . number_format($n / 1_000, 1, ',', '.') . ' Rb',
            default                 => 'Rp' . angka($n),
        };
    }
}

if (! function_exists('persen')) {
    function persen(int|float|null $nilai, int $desimal = 1): string
    {
        return number_format((float) ($nilai ?? 0), $desimal, ',', '.') . '%';
    }
}

if (! function_exists('tgl')) {
    /** Tanggal dd/mm/yyyy, opsional dengan jam HH:mm (Asia/Jakarta). */
    function tgl(string|\DateTimeInterface|null $nilai, bool $denganWaktu = false): string
    {
        if ($nilai === null || $nilai === '') {
            return '—';
        }

        try {
            $d = $nilai instanceof \DateTimeInterface
                ? \Illuminate\Support\Carbon::instance($nilai)
                : \Illuminate\Support\Carbon::parse($nilai);
        } catch (\Throwable) {
            return '—';
        }

        return $d->timezone('Asia/Jakarta')
            ->format($denganWaktu ? 'd/m/Y H:i' : 'd/m/Y');
    }
}

if (! function_exists('durasi')) {
    /** Durasi milidetik menjadi bentuk mudah dibaca: 340 ms · 1,2 s · 2 mnt 4 s */
    function durasi(int|float|null $ms): string
    {
        $ms = (int) ($ms ?? 0);

        return match (true) {
            $ms < 1_000           => $ms . ' ms',
            $ms < 60_000          => number_format($ms / 1_000, 1, ',', '.') . ' s',
            default               => intdiv($ms, 60_000) . ' mnt '
                                     . number_format(($ms % 60_000) / 1_000, 0, ',', '.') . ' s',
        };
    }
}

if (! function_exists('inisial')) {
    /** Inisial untuk avatar: "Kepala Dinas PU" -> "KD" */
    function inisial(string $nama): string
    {
        $kata = preg_split('/\s+/', trim($nama)) ?: [];
        $kata = array_values(array_filter($kata, fn ($k) => $k !== '' && ! str_contains($k, '.')));

        $ambil = array_slice($kata, 0, 2);

        return $ambil === []
            ? 'U'
            : strtoupper(implode('', array_map(fn ($k) => mb_substr($k, 0, 1), $ambil)));
    }
}
