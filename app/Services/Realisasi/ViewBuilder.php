<?php

declare(strict_types=1);

namespace App\Services\Realisasi;

use Illuminate\Support\Facades\DB;

/**
 * Materialisasi realisasi RUP + view (F11.6, F11.7, F11.8).
 *
 * Kedua tabel ref_* di-replace penuh setiap kali dihitung, masing-masing
 * dalam satu transaksi. Kegagalan di tengah tidak menyisakan tabel kosong.
 */
class ViewBuilder
{
    public const VIEW = 'v_realisasi_rup';

    public function __construct(
        private readonly WarisanResolver $resolver,
    ) {}

    /**
     * @return array{proses: int, warisan: int, view: int}
     */
    public function build(): array
    {
        $result = $this->resolver->resolve();

        $prosesCount = $this->replaceTable('ref_rup_proses', $result['proses'], [
            'kd_rup', 'sumber', 'updated_at',
        ]);

        $warisanCount = $this->replaceTable('ref_rup_warisan', $result['warisan'], [
            'kd_rup', 'sumber', 'dari_rup', 'depth', 'updated_at',
        ]);

        $this->createView();

        return [
            'proses' => $prosesCount,
            'warisan' => $warisanCount,
            'view' => $this->viewCount(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $columns
     */
    private function replaceTable(string $table, array $rows, array $columns): int
    {
        $now = now();
        $chunk = max(1, (int) config('inaproc.chunk_size', 500));

        DB::transaction(function () use ($table, $rows, $columns, $now, $chunk): void {
            DB::table($table)->delete();

            foreach (array_chunk($rows, $chunk) as $batch) {
                $payload = array_map(static function (array $row) use ($columns, $now): array {
                    $row['updated_at'] = $now->format('Y-m-d H:i:sP');

                    return array_intersect_key($row, array_flip($columns));
                }, $batch);

                if ($payload !== []) {
                    DB::table($table)->insert($payload);
                }
            }
        });

        return count($rows);
    }

    /**
     * F11.7 — CREATE OR REPLACE VIEW agar dependensi tidak terputus.
     *
     * kd_rup dinormalisasi ke string bilangan bulat di kedua sisi join.
     */
    public function createView(): void
    {
        // View bergantung pada tabel paket; bila belum pernah ditarik, view
        // tidak dapat dibangun. Ini bukan kegagalan proses realisasi.
        $paketTable = (string) config('inaproc.realisasi.paket_table', 'rec_rup_paket_penyedia');

        $fullTable = (str_starts_with($paketTable, 'rec_') && !str_contains($paketTable, '.'))
            ? "inaproc.{$paketTable}"
            : $paketTable;

        if (! DB::getSchemaBuilder()->hasTable($fullTable)) {
            return;
        }

        $sql = <<<'SQL'
            CREATE OR REPLACE VIEW v_realisasi_rup AS
            WITH sumber_paket AS (
                SELECT kd_rup, sumber, NULL::text AS dari_rup, 0::bigint AS depth
                  FROM ref_rup_proses
                UNION ALL
                SELECT kd_rup, sumber, dari_rup, depth::bigint
                  FROM ref_rup_warisan
            )
            SELECT
                round(p.kd_rup::numeric)::bigint::text AS kd_rup,
                p.nama_paket,
                p.pagu,
                p.kd_satker,
                p.nama_satker,
                p.metode_pengadaan,
                p.tahun_anggaran,
                sp.sumber,
                CASE WHEN sp.depth = 0 THEN 'langsung' ELSE 'warisan' END AS jenis,
                sp.dari_rup,
                sp.depth
            FROM rec_rup_paket_penyedia p
            JOIN sumber_paket sp
              ON sp.kd_rup = round(p.kd_rup::numeric)::bigint::text
        SQL;

        try {
            DB::statement($sql);
        } catch (\Throwable $e) {
            // CREATE OR REPLACE gagal bila daftar kolom berubah atau tipe kolom
            // berubah. Sesuai F11.7: DROP VIEW lalu CREATE VIEW.
            if (str_contains($e->getMessage(), 'cannot change')
                || str_contains($e->getMessage(), 'cannot drop columns from view')) {
                DB::statement('DROP VIEW IF EXISTS '.self::VIEW);
                DB::statement($sql);

                return;
            }

            throw $e;
        }
    }

    public function viewCount(): int
    {
        try {
            return (int) DB::table(self::VIEW)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * @return array<string, array{langsung: int, warisan: int}>
     */
    public function breakdown(): array
    {
        try {
            $rows = DB::table(self::VIEW)
                ->selectRaw("sumber, sum(case when jenis = 'langsung' then 1 else 0 end) as langsung, sum(case when jenis = 'warisan' then 1 else 0 end) as warisan")
                ->groupBy('sumber')
                ->orderBy('sumber')
                ->get();
        } catch (\Throwable) {
            return [];
        }

        $out = [];

        foreach ($rows as $row) {
            $out[(string) $row->sumber] = [
                'langsung' => (int) $row->langsung,
                'warisan' => (int) $row->warisan,
            ];
        }

        return $out;
    }
}
