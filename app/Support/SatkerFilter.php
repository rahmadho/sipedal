<?php

declare(strict_types=1);

namespace App\Support;

use App\Support\Mock\SatkerPilihan;

/**
 * Aturan satuan kerja (rup_stk_id) tahap UI/UX.
 *
 * rup_stk_id adalah kode satuan kerja. Dipakai untuk menyaring data: pengguna
 * dengan peran tertentu hanya melihat data satker miliknya.
 *
 * Peran yang WAJIB punya satuan kerja: kepala_opd dan ppk. Selengkapnya di
 * config('sipedal.roles'); kedua kunci itu harus ada di sana.
 */
class SatkerFilter
{
    /** Peran yang wajib mengisi rup_stk_id. */
    public const PERAN_WAJIB = ['kepala_opd', 'ppk'];

    /** Apakah peran ini wajib punya satuan kerja? */
    public static function wajib(?string $peran): bool
    {
        return $peran !== null && in_array($peran, self::PERAN_WAJIB, true);
    }

    /** Pesan galat berbahasa Indonesia untuk peran yang wajib. */
    public static function pesan(?string $peran): string
    {
        $label = config("sipedal.roles.$peran.label", $peran ?? 'Peran ini');

        return "Peran {$label} wajib memilih satuan kerja.";
    }

    /** Apakah data pengguna ini harus disaring per satuan kerja? */
    public static function berlakunya(array $sesi): bool
    {
        return self::wajib($sesi['peran'] ?? null)
            && ! empty($sesi['rup_stk_id']);
    }

    /**
     * Saring baris data Mock berdasarkan satuan kerja.
     *
     * Mendukung baris dengan kd_satker (integer) maupun satker (nama string).
     * Baris tanpa kedua kunci tersebut dianggap cocok (mis. baris ringkasan).
     */
    public static function terapkan(array $baris, ?int $rupStkId): array
    {
        if ($rupStkId === null) {
            return $baris;
        }

        $nama = SatkerPilihan::nama($rupStkId);

        return array_values(array_filter(
            $baris,
            static function (array $b) use ($rupStkId, $nama): bool {
                if (isset($b['kd_satker'])) {
                    return (int) $b['kd_satker'] === $rupStkId;
                }
                if (isset($b['satker']) && $nama !== null) {
                    return $b['satker'] === $nama;
                }

                return true;
            }
        ));
    }
}
