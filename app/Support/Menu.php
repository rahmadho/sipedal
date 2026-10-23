<?php

namespace App\Support;

/**
 * Menyusun menu navigasi sesuai peran pengguna.
 *
 * Menu berasal dari config/sipedal.php, lalu disaring berdasarkan daftar
 * `akses` milik peran. Item yang seluruh anaknya tersaring ikut hilang,
 * sehingga tidak ada submenu kosong.
 */
class Menu
{
    /**
     * @return array<int, array{grup: string, butir: array<int, array>}>
     */
    public static function untuk(string $peran): array
    {
        $akses = self::aksesPeran($peran);
        $hasil = [];

        foreach (config('sipedal.menu', []) as $blok) {
            $butir = [];

            foreach ($blok['butir'] ?? [] as $item) {
                if (! self::boleh($akses, $item['kunci'] ?? '')) {
                    continue;
                }

                if (! empty($item['anak'])) {
                    $anak = array_values(array_filter(
                        $item['anak'],
                        fn ($a) => self::boleh($akses, $a['kunci'] ?? '')
                    ));

                    if ($anak === []) {
                        continue;
                    }

                    $item['anak'] = $anak;
                }

                $butir[] = $item;
            }

            if ($butir !== []) {
                $hasil[] = ['grup' => $blok['grup'] ?? '', 'butir' => $butir];
            }
        }

        return $hasil;
    }

    private static function aksesPeran(string $peran): array
    {
        return config('sipedal.roles.' . $peran . '.akses', []);
    }

    private static function boleh(array $akses, string $kunci): bool
    {
        if ($kunci === '') {
            return false;
        }

        if (in_array('*', $akses, true)) {
            return true;
        }

        return in_array($kunci, $akses, true);
    }

    /** Label peran untuk ditampilkan di antarmuka. */
    public static function labelPeran(string $peran): string
    {
        return config('sipedal.roles.' . $peran . '.label', ucfirst(str_replace('_', ' ', $peran)));
    }

    /** Ringkasan peran, mis. "SA", "PPK". */
    public static function ringkasPeran(string $peran): string
    {
        return config('sipedal.roles.' . $peran . '.ringkas', 'U');
    }

    /** Lingkup data peran, mis. "Satu KLPD". */
    public static function lingkupPeran(string $peran): string
    {
        return config('sipedal.roles.' . $peran . '.lingkup', '—');
    }
}
