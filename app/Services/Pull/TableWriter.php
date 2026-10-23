<?php

declare(strict_types=1);

namespace App\Services\Pull;

use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Penulisan data ke tabel rec_* (F6).
 *
 * Dua pola:
 *  replace() — delete-insert transaksional per (endpoint, KLPD, tahun).
 *              Gagal kapan pun → rollback → data lama utuh.
 *  append()  — untuk endpoint scope=derived (F1.3). Baris yang sudah ada
 *              tidak dihapus, karena sumbernya banyak dan independen.
 */
class TableWriter
{
    public function __construct(
        private readonly SchemaManager $schema,
    ) {}

    /**
     * Delete-insert transaksional.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return int Jumlah baris tersimpan
     */
    public function replace(string $table, string $kodeKlpd, int $tahun, array $rows, ?string $source = null): int
    {
        if ($rows === []) {
            return 0;
        }

        $columns = $this->schema->ensureTable($table, $rows);
        $chunkSize = max(1, (int) config('inaproc.chunk_size', 500));
        $fetchedAt = now();
        $saved = 0;

        DB::transaction(function () use ($table, $kodeKlpd, $tahun, $rows, $source, $columns, $chunkSize, $fetchedAt, &$saved): void {
            DB::table($table)
                ->where('_kode_klpd', $kodeKlpd)
                ->where('_tahun', $tahun)
                ->delete();

            foreach (array_chunk($rows, $chunkSize) as $chunk) {
                $payload = [];

                foreach ($chunk as $row) {
                    $payload[] = $this->normalizeRow($row, $columns, $kodeKlpd, $tahun, $fetchedAt, $source);
                }

                if ($payload !== []) {
                    DB::table($table)->insert($payload);
                    $saved += count($payload);
                }
            }
        });

        return $saved;
    }

    /**
     * Append tanpa menghapus (F1.3). Baris yang sudah ada dibiarkan.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return int Jumlah baris tersimpan
     */
    public function append(string $table, string $kodeKlpd, int $tahun, array $rows, ?string $source = null): int
    {
        if ($rows === []) {
            return 0;
        }

        $columns = $this->schema->ensureTable($table, $rows);
        $chunkSize = max(1, (int) config('inaproc.chunk_size', 500));
        $fetchedAt = now();
        $saved = 0;

        DB::transaction(function () use ($table, $rows, $columns, $kodeKlpd, $tahun, $fetchedAt, $source, $chunkSize, &$saved): void {
            foreach (array_chunk($rows, $chunkSize) as $chunk) {
                $payload = [];

                foreach ($chunk as $row) {
                    $payload[] = $this->normalizeRow($row, $columns, $kodeKlpd, $tahun, $fetchedAt, $source);
                }

                if ($payload !== []) {
                    DB::table($table)->insert($payload);
                    $saved += count($payload);
                }
            }
        });

        return $saved;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $columns
     * @return array<string, mixed>
     */
    private function normalizeRow(
        array $row,
        array $columns,
        string $kodeKlpd,
        int $tahun,
        mixed $fetchedAt,
        ?string $source,
    ): array {
        $out = [
            '_kode_klpd' => $kodeKlpd,
            '_tahun' => $tahun,
            '_source' => $source,
            '_fetched_at' => $fetchedAt,
        ];

        foreach ($columns as $column) {
            $value = $this->lookup($row, $column);
            $out[$column] = $this->coerce($value);
        }

        return $out;
    }

    /**
     * Cari nilai berdasarkan nama kolom ternormalisasi.
     *
     * @param  array<string, mixed>  $row
     */
    private function lookup(array $row, string $column): mixed
    {
        foreach ($row as $key => $value) {
            if ($this->schema->normalizeColumnName((string) $key) === $column) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Ubah nilai agar dapat ditulis ke PostgreSQL (array/objek → JSON).
     */
    private function coerce(mixed $value): mixed
    {
        if (is_array($value) || is_object($value) && ! $value instanceof \DateTimeInterface) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:sP');
        }

        return $value;
    }

    /**
     * Bantu uji: hitung baris untuk satu filter.
     */
    public function countFor(string $table, string $kodeKlpd, int $tahun): int
    {
        try {
            return (int) DB::table($table)
                ->where('_kode_klpd', $kodeKlpd)
                ->where('_tahun', $tahun)
                ->count();
        } catch (Throwable) {
            return 0;
        }
    }
}
