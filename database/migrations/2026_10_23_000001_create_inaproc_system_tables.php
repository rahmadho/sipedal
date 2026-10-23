<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration sistem — IDEMPOTENT.
 *
 * Basis data `inaproc` sudah berisi tabel hasil pipeline sebelumnya, dengan
 * tipe kolom yang lebih longgar daripada spesifikasi (mis. `_kode_klpd text`,
 * `kd_rup text`, `pull_log.id` tanpa sequence). Migration ini tidak
 * men-drop/mengubah kolom yang sudah ada; ia hanya melengkapi yang belum ada
 * sehingga data lama tetap utuh dan tetap bisa dipakai sebagai dataset acuan.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->createKlpdCredentials();
        $this->createEndpoints();
        $this->createPullLog();
        $this->createRefRupProses();
        $this->createRefRupWarisan();
        $this->createDerivedProgress();
    }

    public function down(): void
    {
        // Tidak menghapus apa pun: tabel ini milik basis data bersama.
    }

    private function createKlpdCredentials(): void
    {
        if (Schema::hasTable('klpd_credentials')) {
            return;
        }

        DB::statement(<<<'SQL'
            CREATE TABLE klpd_credentials (
                kode             VARCHAR(16) PRIMARY KEY,
                label            VARCHAR(128),
                token_encrypted  TEXT NOT NULL,
                is_active        BOOLEAN NOT NULL DEFAULT TRUE,
                updated_at       TIMESTAMPTZ
            )
        SQL);
    }

    private function createEndpoints(): void
    {
        if (Schema::hasTable('endpoints')) {
            return;
        }

        DB::statement(<<<'SQL'
            CREATE TABLE endpoints (
                name    VARCHAR(64) PRIMARY KEY,
                path    VARCHAR(255) NOT NULL,
                api     VARCHAR(16)  NOT NULL CHECK (api IN ('v1','legacy')),
                grup    VARCHAR(32)  NOT NULL,
                scope   VARCHAR(16)  NOT NULL CHECK (scope IN ('klpd','derived','tahun')),
                active  BOOLEAN      NOT NULL DEFAULT TRUE,
                urutan  SMALLINT     NOT NULL DEFAULT 0,
                created_at TIMESTAMPTZ,
                updated_at TIMESTAMPTZ
            )
        SQL);
    }

    private function createPullLog(): void
    {
        if (! Schema::hasTable('pull_log')) {
            DB::statement(<<<'SQL'
                CREATE TABLE pull_log (
                    id          BIGSERIAL PRIMARY KEY,
                    created_at  TIMESTAMPTZ NOT NULL DEFAULT now(),
                    endpoint    VARCHAR(64)  NOT NULL,
                    grup        VARCHAR(32),
                    api         VARCHAR(16),
                    kode_klpd   VARCHAR(16)  NOT NULL,
                    tahun       SMALLINT     NOT NULL,
                    status      VARCHAR(16)  NOT NULL,
                    rows        INTEGER      NOT NULL DEFAULT 0,
                    source      VARCHAR(128),
                    pages       INTEGER      NOT NULL DEFAULT 0,
                    durasi_ms   INTEGER,
                    pesan       VARCHAR(255)
                )
            SQL);
        } else {
            // Tabel lama: `id` BIGINT tanpa sequence, `waktu` TEXT, tanpa `pages`.
            // Tambahkan sequence + kolom yang hilang tanpa menyentuh data.
            $this->ensurePullLogSequence();
            $this->ensureColumn('pull_log', 'pages', 'INTEGER NOT NULL DEFAULT 0');
            $this->ensureColumn('pull_log', 'created_at', 'TIMESTAMPTZ');
            $this->ensureColumn('pull_log', 'waktu', 'TEXT');
        }

        $this->ensureIndex('pull_log', 'pull_log_kode_klpd_tahun_index', '(kode_klpd, tahun)');
        $this->ensureIndex('pull_log', 'pull_log_status_index', '(status)');
        $this->ensureIndex('pull_log', 'pull_log_created_at_index', '(created_at DESC)');
    }

    private function ensurePullLogSequence(): void
    {
        // Bila `id` tidak punya default, jadikan BIGSERIAL secara fungsional.
        $hasDefault = DB::selectOne(<<<'SQL'
            SELECT 1 AS ok
            FROM information_schema.columns
            WHERE table_name = 'pull_log'
              AND column_name = 'id'
              AND column_default IS NOT NULL
        SQL);

        if ($hasDefault) {
            return;
        }

        $max = (int) (DB::selectOne('SELECT COALESCE(MAX(id), 0) AS m FROM pull_log')?->m ?? 0);

        DB::statement('CREATE SEQUENCE IF NOT EXISTS pull_log_id_seq');
        DB::statement("SELECT setval('pull_log_id_seq', GREATEST($max, 1), $max > 0)");
        DB::statement("ALTER TABLE pull_log ALTER COLUMN id SET DEFAULT nextval('pull_log_id_seq')");
        DB::statement("ALTER SEQUENCE pull_log_id_seq OWNED BY pull_log.id");
    }

    private function createRefRupProses(): void
    {
        if (! Schema::hasTable('ref_rup_proses')) {
            DB::statement(<<<'SQL'
                CREATE TABLE ref_rup_proses (
                    kd_rup     VARCHAR(64) NOT NULL,
                    sumber     VARCHAR(32) NOT NULL,
                    updated_at TIMESTAMPTZ NOT NULL,
                    PRIMARY KEY (kd_rup, sumber)
                )
            SQL);

            return;
        }

        $this->ensurePrimaryKey('ref_rup_proses', ['kd_rup', 'sumber']);
    }

    private function createRefRupWarisan(): void
    {
        if (! Schema::hasTable('ref_rup_warisan')) {
            DB::statement(<<<'SQL'
                CREATE TABLE ref_rup_warisan (
                    kd_rup     VARCHAR(64) NOT NULL,
                    sumber     VARCHAR(32) NOT NULL,
                    dari_rup   VARCHAR(64) NOT NULL,
                    depth      SMALLINT    NOT NULL,
                    updated_at TIMESTAMPTZ NOT NULL,
                    PRIMARY KEY (kd_rup, sumber)
                )
            SQL);

            return;
        }

        $this->ensurePrimaryKey('ref_rup_warisan', ['kd_rup', 'sumber']);
    }

    private function createDerivedProgress(): void
    {
        if (! Schema::hasTable('derived_progress')) {
            DB::statement(<<<'SQL'
                CREATE TABLE derived_progress (
                    endpoint    VARCHAR(64) NOT NULL,
                    kode_klpd   VARCHAR(16) NOT NULL,
                    tahun       SMALLINT    NOT NULL,
                    nilai_kunci VARCHAR(128) NOT NULL,
                    hasil_rows  INTEGER     NOT NULL DEFAULT 0,
                    fetched_at  TIMESTAMPTZ NOT NULL,
                    PRIMARY KEY (endpoint, kode_klpd, tahun, nilai_kunci)
                )
            SQL);
        }

        $this->ensureIndex(
            'derived_progress',
            'derived_progress_endpoint_klpd_tahun_index',
            '(endpoint, kode_klpd, tahun)',
        );
    }

    private function ensureColumn(string $table, string $column, string $definition): void
    {
        if (Schema::hasColumn($table, $column)) {
            return;
        }

        DB::statement(sprintf('ALTER TABLE %s ADD COLUMN %s %s', $table, $column, $definition));
    }

    private function ensureIndex(string $table, string $name, string $columns): void
    {
        DB::statement(sprintf('CREATE INDEX IF NOT EXISTS %s ON %s %s', $name, $table, $columns));
    }

    /**
     * Tabel lama dibuat tanpa PRIMARY KEY. Tambahkan bila belum ada, agar
     * replace-penuh tetap aman dan tidak menghasilkan duplikasi.
     *
     * @param  array<int, string>  $columns
     */
    private function ensurePrimaryKey(string $table, array $columns): void
    {
        $exists = DB::selectOne(<<<'SQL'
            SELECT 1 AS ok
            FROM information_schema.table_constraints
            WHERE table_name = ? AND constraint_type = 'PRIMARY KEY'
        SQL, [$table]);

        if ($exists) {
            return;
        }

        // Bersihkan duplikat lebih dulu, kalau ada, agar PK bisa dipasang.
        $grouped = implode(', ', $columns);

        DB::statement(<<<SQL
            DELETE FROM {$table} a
            USING {$table} b
            WHERE a.ctid < b.ctid
              AND ({$this->comparison($columns)})
        SQL);

        DB::statement(sprintf('ALTER TABLE %s ADD PRIMARY KEY (%s)', $table, $grouped));
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function comparison(array $columns): string
    {
        return implode(' AND ', array_map(
            static fn (string $c): string => "a.{$c} IS NOT DISTINCT FROM b.{$c}",
            $columns,
        ));
    }
};
