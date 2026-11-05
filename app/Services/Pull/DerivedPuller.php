<?php

declare(strict_types=1);

namespace App\Services\Pull;

use App\Enums\PullStatus;
use App\Models\DerivedProgress;
use App\Models\Endpoint;
use App\Models\PullLog;
use App\Services\Inaproc\Paginator;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Penarikan endpoint turunan (F1.3) dan penanganan berjenjang (F1.4).
 *
 * Perbedaan pokok dengan endpoint scope=klpd:
 *  - simpan APPEND, bukan delete-insert (hasil parsial tidak boleh hilang)
 *  - bukti keberadaan dicatat di derived_progress, bukan dari ada/tidaknya baris
 *  - nilai yang sudah tersimpan untuk KLPD+tahun yang sama dilewati
 */
class DerivedPuller
{
    public function __construct(
        private readonly Paginator $paginator,
        private readonly TableWriter $writer,
    ) {}

    public function pull(
        Endpoint $endpoint,
        string $kodeKlpd,
        int $tahun,
        ?string $token,
        string $credentialKey,
        float $startedAt,
        bool $refresh = false,
    ): PullLog {
        $config = (array) config('inaproc.derived.'.$endpoint->name, []);

        if ($config === []) {
            return $this->log($endpoint, $kodeKlpd, $tahun, PullStatus::NONAKTIF, 0, 0, 'Konfigurasi turunan tidak ditemukan', $startedAt);
        }

        // F1.4 — endpoint berjenjang tidak diaktifkan tanpa revisi PRD.
        if (($config['tree'] ?? false) === true) {
            return $this->log(
                $endpoint,
                $kodeKlpd,
                $tahun,
                PullStatus::NONAKTIF,
                0,
                0,
                'Endpoint berjenjang: struktur pohon belum ditetapkan PRD',
                $startedAt,
            );
        }

        $values = $this->sourceValues($config, $kodeKlpd, $tahun);

        if ($values === []) {
            return $this->log($endpoint, $kodeKlpd, $tahun, PullStatus::KOSONG, 0, 0, null, $startedAt);
        }

        $stored = $this->storedValues($endpoint->name, $kodeKlpd, $tahun);

        $toFetch = $refresh
            ? $values
            : array_values(array_diff($values, $stored));

        $skipped = count($values) - count($toFetch);
        $pulled = 0;
        $failed = 0;
        $pages = 0;
        $table = $endpoint->tableName();

        foreach ($toFetch as $value) {
            try {
                $result = $this->paginator->fetchAll(
                    endpoint: $endpoint,
                    kodeKlpd: $kodeKlpd,
                    tahun: $tahun,
                    token: $token,
                    credentialKey: $credentialKey,
                    derived: [(string) ($config['key'] ?? 'kode_penyedia') => $value],
                );
            } catch (Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('derived fetch gagal', ['err' => $e->getMessage(), 'class' => get_class($e)]);
                $failed++;

                continue;
            }

            $pages += $result['pages'];

            if (! $result['response']->ok()) {
                // Kegagalan per nilai tidak dicatat di derived_progress →
                // otomatis dicoba lagi pada run berikutnya.
                $failed++;

                continue;
            }

            $rows = $result['rows'];

            try {
                if ($rows !== []) {
                    $pulled += $this->writer->append($table, $kodeKlpd, $tahun, $rows, $endpoint->name);
                }

                // Hasil kosong tetap dicatat dengan hasil_rows = 0.
                // derived_progress memakai primary key gabungan (tanpa id),
                // sehingga upsert dilakukan lewat query builder.
                DB::table('derived_progress')->upsert(
                    [[
                        'endpoint' => $endpoint->name,
                        'kode_klpd' => $kodeKlpd,
                        'tahun' => $tahun,
                        'nilai_kunci' => (string) $value,
                        'hasil_rows' => count($rows),
                        'fetched_at' => now(),
                    ]],
                    ['endpoint', 'kode_klpd', 'tahun', 'nilai_kunci'],
                    ['hasil_rows', 'fetched_at'],
                );
            } catch (Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('derived simpan gagal', ['err' => $e->getMessage(), 'class' => get_class($e)]);
                $failed++;
            }
        }

        $message = sprintf(
            'nilai_source=%d, dilewati=%d, ditarik=%d, gagal=%d',
            count($values),
            $skipped,
            count($toFetch) - $failed,
            $failed,
        );

        $status = match (true) {
            $failed === 0 && $pulled > 0 => PullStatus::OK,
            $failed === 0 && count($toFetch) === 0 => PullStatus::KOSONG,
            $failed === count($toFetch) => PullStatus::GAGAL,
            default => $pulled > 0 || $failed < count($toFetch) ? PullStatus::OK : PullStatus::GAGAL,
        };

        return $this->log($endpoint, $kodeKlpd, $tahun, $status, $pulled, $pages, $message, $startedAt);
    }

    /**
     * Nilai unik dari endpoint sumber (F1.3).
     *
     * @param  array<string, mixed>  $config
     * @return array<int, string>
     */
    public function sourceValues(array $config, string $kodeKlpd, int $tahun): array
    {
        $table = $config['source_table'] ?? null;
        $column = $config['source_column'] ?? null;

        if ($table === null || $column === null) {
            return [];
        }

        $fullTable = (str_starts_with($table, 'rec_') && !str_contains($table, '.'))
            ? "inaproc.{$table}"
            : $table;

        if (! DB::getSchemaBuilder()->hasTable($fullTable)) {
            return [];
        }

        $rows = DB::table($table)
            ->select($column)
            ->where('_kode_klpd', $kodeKlpd)
            ->where('_tahun', $tahun)
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->distinct()
            ->pluck($column);

        return $rows
            ->map(static fn ($v): string => trim((string) $v))
            ->filter(static fn (string $v): bool => $v !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function storedValues(string $endpoint, string $kodeKlpd, int $tahun): array
    {
        if (! DB::getSchemaBuilder()->hasTable('derived_progress')) {
            return [];
        }

        return DB::table('derived_progress')
            ->where('endpoint', $endpoint)
            ->where('kode_klpd', $kodeKlpd)
            ->where('tahun', $tahun)
            ->pluck('nilai_kunci')
            ->map(static fn ($v): string => (string) $v)
            ->all();
    }

    private function log(
        Endpoint $endpoint,
        string $kodeKlpd,
        int $tahun,
        PullStatus $status,
        int $rows,
        int $pages,
        ?string $message,
        float $startedAt,
    ): PullLog {
        $payload = [
            'endpoint' => $endpoint->name,
            'grup' => $endpoint->grup,
            'api' => $endpoint->api,
            'kode_klpd' => $kodeKlpd,
            'tahun' => $tahun,
            'status' => $status->value,
            'rows' => $rows,
            'source' => null,
            'pages' => $pages,
            'durasi_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'pesan' => $message === null ? null : mb_substr($message, 0, 255),
        ];

        $schema = DB::getSchemaBuilder();

        if ($schema->hasColumn('pull_log', 'created_at')) {
            $payload['created_at'] = now();
        }

        if ($schema->hasColumn('pull_log', 'waktu')) {
            $payload['waktu'] = now()->toDateTimeString();
        }

        return PullLog::query()->create($payload);
    }
}
