<?php

declare(strict_types=1);

namespace App\Services\Realisasi;

use App\Support\KodeNormalizer;
use Illuminate\Support\Facades\DB;

/**
 * Mengumpulkan kode RUP dari sumber proses pengadaan (F11.2, F11.3).
 *
 * Referensi dapat memuat lebih dari satu kode yang dipisahkan titik koma.
 * Eksploitasi dilakukan di database (unnest + string_to_array), bukan dengan
 * memuat seluruh kolom ke memori aplikasi.
 */
class ProsesCollector
{
    /**
     * @return array<string, array<int, string>> sumber => daftar kd_rup unik
     */
    public function collect(): array
    {
        $out = [];

        foreach ($this->sources() as $label => $source) {
            $out[$label] = $this->codesFor($source['table'], $source['column']);
        }

        return $out;
    }

    /**
     * @return array<string, array{table: string, column: string}>
     */
    public function sources(): array
    {
        /** @var array<string, array{table: string, column: string}> $sumber */
        $sumber = config('inaproc.realisasi.sumber', []);

        return $sumber;
    }

    /**
     * Ambil kode unik dari satu tabel sumber, dipecah dan dinormalisasi.
     *
     * @return array<int, string>
     */
    public function codesFor(string $table, string $column): array
    {
        $fullTable = (str_starts_with($table, 'rec_') && !str_contains($table, '.'))
            ? "inaproc.{$table}"
            : $table;

        if (! DB::getSchemaBuilder()->hasTable($fullTable)) {
            return [];
        }

        if (! DB::getSchemaBuilder()->hasColumn($fullTable, $column)) {
            return [];
        }

        // Eksploitasi multi-nilai dilakukan di sisi PostgreSQL (F11.3).
        $sql = sprintf(
            'SELECT DISTINCT btrim(part) AS kode
               FROM %1$s,
                    unnest(string_to_array(%2$s::text, %3$s)) AS part
              WHERE %2$s IS NOT NULL
                AND %2$s::text <> %4$s
                AND btrim(part) <> %4$s',
            $table,
            '"'.$column.'"',
            "'".';'."'",
            "''",
        );

        $rows = DB::select($sql);

        $codes = [];

        foreach ($rows as $row) {
            $code = KodeNormalizer::normalize($row->kode ?? null);

            if ($code !== '') {
                // Prefiks 'k' mencegah PHP mengubah kunci numerik menjadi int.
                $codes['k'.$code] = $code;
            }
        }

        return array_values($codes);
    }
}
