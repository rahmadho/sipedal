<?php

declare(strict_types=1);

namespace App\Services\Pull;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Deteksi & evolusi kolom tabel `rec_*` (F6.2, F6.3).
 *
 * Kolom API tidak pernah didefinisikan manual — dibaca dari data hasil tarik.
 * Tabel dan kolom baru dibuat otomatis, dan tipe hanya boleh meluas:
 * bigint → numeric → text. Tidak pernah menyempit.
 */
class SchemaManager
{
    /** Kolom sistem yang selalu ada di setiap tabel rec_* (F6.2). */
    public const SYSTEM_COLUMNS = [
        '_row_id' => 'BIGSERIAL PRIMARY KEY',
        '_kode_klpd' => 'VARCHAR(16)',
        '_tahun' => 'SMALLINT',
        '_source' => 'VARCHAR(128)',
        '_fetched_at' => 'TIMESTAMPTZ',
    ];

    /**
     * Peringkat tipe untuk aturan "tipe hanya meluas".
     *
     * @var array<string, int>
     */
    private const TYPE_RANK = [
        'boolean' => 1,
        'bigint' => 2,
        'numeric' => 3,
        'timestamptz' => 4,
        'text' => 5,
    ];

    /**
     * Pastikan tabel ada, lalu sinkronkan kolom dengan data yang akan ditulis.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, string> Daftar kolom API (tanpa kolom sistem)
     */
    public function ensureTable(string $table, array $rows): array
    {
        if (! Schema::hasTable($table)) {
            $this->createTable($table);
        }

        $this->ensureSystemColumns($table);

        $apiColumns = $this->detectColumns($rows);

        $this->syncColumns($table, $apiColumns);

        return array_keys($apiColumns);
    }

    private function createTable(string $table): void
    {
        $this->assertSafeIdentifier($table);

        $columns = [];

        foreach (self::SYSTEM_COLUMNS as $name => $definition) {
            $columns[] = $name.' '.$definition;
        }

        DB::statement(sprintf('CREATE TABLE IF NOT EXISTS inaproc.%s (%s)', $table, implode(', ', $columns)));

        $this->ensureIndex($table, $table.'_kode_klpd_tahun_index', '(_kode_klpd, _tahun)');
    }

    private function ensureSystemColumns(string $table): void
    {
        $existing = $this->existingColumns($table);

        foreach (self::SYSTEM_COLUMNS as $name => $definition) {
            if (isset($existing[$name])) {
                continue;
            }

            // _row_id hanya bisa ditambahkan bila belum ada kolom lain;
            // untuk tabel yang sudah ada, cukup tambahkan kolom non-PK.
            $definition = $name === '_row_id' ? 'BIGSERIAL' : $definition;

            DB::statement(sprintf('ALTER TABLE inaproc.%s ADD COLUMN IF NOT EXISTS %s %s', $table, $name, $definition));
        }

        $this->ensureIndex($table, $table.'_kode_klpd_tahun_index', '(_kode_klpd, _tahun)');
    }

    /**
     * Deteksi kolom API dari sampel data.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, string> nama kolom => tipe PostgreSQL
     */
    public function detectColumns(array $rows): array
    {
        $detected = [];

        foreach ($rows as $row) {
            foreach ($row as $key => $value) {
                $name = $this->normalizeColumnName((string) $key);

                if ($name === '') {
                    continue;
                }

                $type = $this->inferType($value);
                $current = $detected[$name] ?? null;

                $detected[$name] = $current === null
                    ? $type
                    : $this->widen($current, $type);
            }
        }

        return $detected;
    }

    /**
     * @param  array<string, string>  $apiColumns
     */
    private function syncColumns(string $table, array $apiColumns): void
    {
        $existing = $this->existingColumns($table);

        foreach ($apiColumns as $name => $type) {
            $currentType = $existing[$name] ?? null;

            if ($currentType === null) {
                DB::statement(sprintf(
                    'ALTER TABLE inaproc.%s ADD COLUMN IF NOT EXISTS "%s" %s',
                    $table,
                    $name,
                    $this->postgresType($type),
                ));

                continue;
            }

            // Tipe hanya meluas.
            $current = $this->canonicalType($currentType);

            if ($this->rank($type) > $this->rank($current)) {
                DB::statement(sprintf(
                    'ALTER TABLE inaproc.%s ALTER COLUMN "%s" TYPE %s USING "%s"::%s',
                    $table,
                    $name,
                    $this->postgresType($type),
                    $name,
                    $this->postgresType($type),
                ));
            }
        }
    }

    /**
     * Nama kolom: huruf kecil, non-alfanumerik → _, hapus _ berlebih.
     * Nama yang bentrok dengan kolom sistem diberi prefiks `api_` (F6.2).
     */
    public function normalizeColumnName(string $name): string
    {
        $raw = strtolower(trim($name));

        $normalized = preg_replace('/[^a-z0-9]+/', '_', $raw) ?? '';
        $normalized = trim(preg_replace('/_+/', '_', $normalized) ?? $normalized, '_');

        if ($normalized === '') {
            return '';
        }

        // Kolom API yang bentrok dengan kolom sistem (termasuk yang hanya
        // berbeda pada garis bawah di depan) diberi prefiks `api_` (F6.2).
        $collidesWithSystem = str_starts_with($raw, '_')
            || isset(self::SYSTEM_COLUMNS[$normalized])
            || in_array('_'.$normalized, array_keys(self::SYSTEM_COLUMNS), true);

        if ($collidesWithSystem) {
            $normalized = 'api_'.$normalized;
        }

        // Identifier PostgreSQL maksimum 63 byte.
        return substr($normalized, 0, 63);
    }

    public function inferType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'bigint',
            is_float($value) => 'numeric',
            $value instanceof \DateTimeInterface => 'timestamptz',
            is_string($value) && $this->isIsoDate($value) => 'timestamptz',
            default => 'text',
        };
    }

    public function widen(string $a, string $b): string
    {
        return $this->rank($a) >= $this->rank($b) ? $a : $b;
    }

    public function postgresType(string $type): string
    {
        return match ($type) {
            'boolean' => 'BOOLEAN',
            'bigint' => 'BIGINT',
            'numeric' => 'NUMERIC',
            'timestamptz' => 'TIMESTAMPTZ',
            default => 'TEXT',
        };
    }

    /**
     * @return array<string, string>
     */
    public function existingColumns(string $table): array
    {
        $rows = DB::select(<<<'SQL'
            SELECT column_name, data_type
            FROM information_schema.columns
            WHERE table_schema IN ('public', 'inaproc') AND table_name = ?
        SQL, [$table]);

        $out = [];

        foreach ($rows as $row) {
            $out[$row->column_name] = $row->data_type;
        }

        return $out;
    }

    /**
     * Bandingkan skema aktual dengan hasil tarik (F6.3 penerimaan).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{missing: array<int, string>, extra: array<int, string>}
     */
    public function diff(string $table, array $rows): array
    {
        $hasTable = Schema::hasTable($table) || Schema::hasTable("inaproc.{$table}");
        if (! $hasTable) {
            return ['missing' => array_keys($this->detectColumns($rows)), 'extra' => []];
        }

        $existing = $this->existingColumns($table);
        $detected = array_keys($this->detectColumns($rows));

        $missing = array_values(array_diff($detected, array_keys($existing)));
        $extra = array_values(array_diff(
            array_keys($existing),
            $detected,
            array_keys(self::SYSTEM_COLUMNS),
        ));

        return ['missing' => $missing, 'extra' => $extra];
    }

    private function canonicalType(string $postgresType): string
    {
        return match (true) {
            str_starts_with($postgresType, 'timestamp') => 'timestamptz',
            $postgresType === 'boolean' => 'boolean',
            in_array($postgresType, ['bigint', 'integer', 'smallint'], true) => 'bigint',
            in_array($postgresType, ['numeric', 'double precision', 'real'], true) => 'numeric',
            default => 'text',
        };
    }

    private function rank(string $type): int
    {
        return self::TYPE_RANK[$type] ?? 5;
    }

    private function isIsoDate(string $value): bool
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}([T ]\d{2}:\d{2}(:\d{2})?)?/', $value) !== 1) {
            return false;
        }

        return strtotime($value) !== false;
    }

    private function ensureIndex(string $table, string $name, string $columns): void
    {
        $target = str_contains($table, '.') ? $table : "inaproc.{$table}";
        DB::statement(sprintf('CREATE INDEX IF NOT EXISTS %s ON %s %s', $name, $target, $columns));
    }

    private function assertSafeIdentifier(string $identifier): void
    {
        if (preg_match('/^[a-z_][a-z0-9_]{0,62}$/', $identifier) !== 1) {
            throw new \InvalidArgumentException("Identifier tidak aman: {$identifier}");
        }
    }
}
