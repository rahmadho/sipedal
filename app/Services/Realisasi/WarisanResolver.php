<?php

declare(strict_types=1);

namespace App\Services\Realisasi;

use App\Support\KodeNormalizer;
use Illuminate\Support\Facades\DB;

/**
 * Penelusuran pewarisan kaji ulang (F11.5).
 *
 * Aturan bisnis:
 *  B1 hanya jenis_revisi = 'SATUKESATU' yang menghasilkan pewarisan
 *  B2 hanya jenis_paket  = 'PENYEDIA'
 *  B3 pewarisan transitif — telusuri sampai ditemukan leluhur berproses
 *  B4 RUP baru yang punya proses sendiri TIDAK mewarisi
 *  B5 bila tidak punya proses sendiri, warisi leluhur terdekat
 *  B6 pasangan kd_rup_lama = kd_rup_baru diabaikan
 *  B7 kedalaman maksimum 30 level
 *
 * Algoritma WAJIB Breadth-First Search: DFS akan menemukan leluhur yang lebih
 * jauh lebih dulu sehingga nilai `depth` salah.
 */
class WarisanResolver
{
    /** @var array<string, array<int, string>> kd_rup_baru => daftar kd_rup_lama */
    private array $chains = [];

    public function __construct(
        private readonly ProsesCollector $collector,
    ) {}

    /**
     * Hitung realisasi: RUP berproses langsung + RUP yang mewarisi.
     *
     * @return array{
     *     proses: array<int, array{kd_rup: string, sumber: string}>,
     *     warisan: array<int, array{kd_rup: string, sumber: string, dari_rup: string, depth: int}>
     * }
     */
    public function resolve(?array $prosesMap = null, ?array $paketCodes = null, ?int $maxDepth = null): array
    {
        $prosesMap ??= $this->prosesMap();
        $paketCodes ??= $this->paketCodes();

        $this->loadChains();

        $proses = [];
        $warisan = [];

        foreach ($paketCodes as $kdRup) {
            // Kunci array PHP mengubah string numerik menjadi int; samakan tipe.
            $kdRup = (string) $kdRup;

            // B4 — punya proses sendiri.
            if (isset($prosesMap[$kdRup])) {
                foreach (array_keys($prosesMap[$kdRup]) as $sumber) {
                    $proses[] = ['kd_rup' => $kdRup, 'sumber' => (string) $sumber];
                }

                continue;
            }

            $found = $this->bfs($kdRup, $prosesMap, $maxDepth);

            if ($found === null) {
                continue;
            }

            $warisan[] = [
                'kd_rup' => $kdRup,
                'sumber' => $found['sumber'],
                'dari_rup' => $found['dari_rup'],
                'depth' => $found['depth'],
            ];
        }

        return ['proses' => $proses, 'warisan' => $warisan];
    }

    /**
     * Peta kd_rup => [sumber => true] dari seluruh sumber proses.
     *
     * @return array<string, array<string, true>>
     */
    public function prosesMap(?array $collected = null): array
    {
        $collected ??= $this->collector->collect();

        $map = [];

        foreach ($collected as $sumber => $codes) {
            foreach ($codes as $code) {
                $map[(string) $code][(string) $sumber] = true;
            }
        }

        return $map;
    }

    /**
     * Seluruh kode RUP pada rec_rup_paket_penyedia (dinormalisasi).
     *
     * @return array<int, string>
     */
    public function paketCodes(): array
    {
        $table = (string) config('inaproc.realisasi.paket_table', 'rec_rup_paket_penyedia');
        $column = (string) config('inaproc.realisasi.paket_kode_column', 'kd_rup');

        if (! DB::getSchemaBuilder()->hasTable($table)) {
            return [];
        }

        $rows = DB::table($table)->select($column)->distinct()->pluck($column);

        $codes = [];

        foreach ($rows as $value) {
            $code = KodeNormalizer::normalize($value);

            if ($code !== '') {
                $codes['k'.$code] = $code;
            }
        }

        return array_values($codes);
    }

    /**
     * Muat rantai kaji ulang: kd_rup_baru => [kd_rup_lama, ...].
     */
    public function loadChains(): void
    {
        $table = (string) config('inaproc.realisasi.kaji_ulang_table', 'rec_rup_history_kaji_ulang');

        $this->chains = [];

        if (! DB::getSchemaBuilder()->hasTable($table)) {
            return;
        }

        $query = DB::table($table)
            ->select(['kd_rup_lama', 'kd_rup_baru'])
            ->whereNotNull('kd_rup_lama')
            ->whereNotNull('kd_rup_baru');

        if (DB::getSchemaBuilder()->hasColumn($table, 'jenis_revisi')) {
            $query->whereRaw("upper(coalesce(jenis_revisi, '')) = 'SATUKESATU'");
        }

        if (DB::getSchemaBuilder()->hasColumn($table, 'jenis_paket')) {
            $query->whereRaw("upper(coalesce(jenis_paket, '')) = 'PENYEDIA'");
        }

        foreach ($query->cursor() as $row) {
            $lama = KodeNormalizer::normalize($row->kd_rup_lama);
            $baru = KodeNormalizer::normalize($row->kd_rup_baru);

            // B6 — pasangan identik diabaikan.
            if ($lama === '' || $baru === '' || $lama === $baru) {
                continue;
            }

            $this->chains[$baru][] = $lama;
        }

        foreach ($this->chains as $key => $list) {
            $this->chains[$key] = array_values(array_unique($list));
        }
    }

    /**
     * Breadth-First Search ke leluhur terdekat yang punya proses.
     *
     * @param  array<string, array<string, true>>  $prosesMap
     * @return array{sumber: string, dari_rup: string, depth: int}|null
     */
    public function bfs(string $kdRup, array $prosesMap, ?int $maxDepth = null): ?array
    {
        $maxDepth = max(1, $maxDepth ?? (int) config('inaproc.realisasi.max_depth', 30));

        $queue = [$kdRup];
        $visited = [$kdRup => true];
        $depth = 0;

        while ($queue !== [] && $depth < $maxDepth) {
            $depth++;
            $nextLevel = [];

            foreach ($queue as $node) {
                foreach ($this->chains[$node] ?? [] as $ancestor) {
                    if (isset($visited[$ancestor])) {
                        continue;
                    }

                    $visited[$ancestor] = true;

                    if (isset($prosesMap[$ancestor])) {
                        // Sumber dipilih deterministik agar hasil stabil.
                        $sumber = $this->pickSource($prosesMap[$ancestor]);

                        return [
                            'sumber' => $sumber,
                            'dari_rup' => $ancestor,
                            'depth' => $depth,
                        ];
                    }

                    $nextLevel[] = $ancestor;
                }
            }

            $queue = $nextLevel;
        }

        return null;
    }

    /**
     * @param  array<string, true>  $sumberSet
     */
    private function pickSource(array $sumberSet): string
    {
        $sources = array_keys($sumberSet);
        sort($sources);

        return (string) ($sources[0] ?? '');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function chains(): array
    {
        return $this->chains;
    }
}
