<?php

declare(strict_types=1);

namespace App\Services\Inaproc;

use App\Enums\EndpointScope;
use App\Models\Endpoint;
use Illuminate\Support\Collection;

/**
 * Sumber kebenaran definisi endpoint (F1).
 *
 * Definisi tersimpan di tabel `endpoints` (diisi EndpointSeeder). Kelas ini
 * hanya menambahkan turunan yang tidak boleh diketik manual — terutama nama
 * tabel data (F2) dan pembangunan parameter per scope (F1.1).
 */
class EndpointRegistry
{
    /**
     * Turunkan nama tabel data dari api + grup + name (F2).
     *
     * rec_<group>_<slug>          untuk api = v1
     * rec_legacy_<group>_<slug>   untuk api = legacy
     */
    public static function tableNameFor(string $api, string $group, string $name): string
    {
        $base = $name;

        // Buang prefiks "legacy-" dan prefiks grup agar tidak berulang.
        if (str_starts_with($base, 'legacy-')) {
            $base = substr($base, strlen('legacy-'));
        }

        if (str_starts_with($base, $group.'-')) {
            $base = substr($base, strlen($group) + 1);
        }

        $slug = preg_replace('/[^a-z0-9]+/', '_', strtolower($base)) ?? $base;
        $slug = trim(preg_replace('/_+/', '_', $slug) ?? $slug, '_');

        $prefix = $api === 'legacy' ? 'rec_legacy_' : 'rec_';

        $table = $prefix.$group.'_'.$slug;

        // Batas identifier PostgreSQL adalah 63 byte.
        return substr($table, 0, 63);
    }

    /**
     * @return Collection<int, Endpoint>
     */
    public function all(bool $onlyActive = false): Collection
    {
        $query = Endpoint::query()->orderBy('urutan');

        if ($onlyActive) {
            $query->where('active', true);
        }

        return $query->get();
    }

    /**
     * @return Collection<int, Endpoint>
     */
    public function forGroup(?string $group = null, bool $onlyActive = false): Collection
    {
        return $this->all($onlyActive)
            ->when($group !== null, fn (Collection $c) => $c->where('grup', $group))
            ->values();
    }

    /**
     * @return Collection<int, Endpoint>
     */
    public function byNames(array $names): Collection
    {
        return $this->all()->filter(fn (Endpoint $e) => in_array($e->name, $names, true))->values();
    }

    public function find(string $name): ?Endpoint
    {
        return Endpoint::query()->find($name);
    }

    /**
     * Bangun parameter query berdasarkan scope (F1.1).
     *
     * scope=klpd    → kode_klpd + tahun (+ limit, cursor untuk v1)
     * scope=tahun   → tahun saja (kode_klpd TIDAK dikirim)
     * scope=derived → parameter turunan, tanpa kode_klpd/tahun
     *
     * @param  array<string, string|int|null>  $derived
     * @return array<string, string|int>
     */
    public function buildParams(
        Endpoint $endpoint,
        ?string $kodeKlpd,
        ?int $tahun,
        array $derived = [],
        ?string $cursor = null,
        ?int $limit = null,
    ): array {
        $params = [];

        switch ($endpoint->endpointScope()) {
            case EndpointScope::KLPD:
                $params['kode_klpd'] = (string) $kodeKlpd;
                $params['tahun'] = (int) $tahun;
                break;

            case EndpointScope::TAHUN:
                // master-program: nasional, tidak terikat instansi.
                $params['tahun'] = (int) $tahun;
                break;

            case EndpointScope::DERIVED:
                $params = array_filter($derived, static fn ($v) => $v !== null && $v !== '');
                break;
        }

        if ($endpoint->family()->isCursorPaged()) {
            // Batas API v1 adalah 1000 baris per halaman.
            $params['limit'] = min(1000, max(1, $limit ?? (int) config('inaproc.page_size', 1000)));

            if ($cursor !== null && $cursor !== '') {
                $params['cursor'] = $cursor;
            }
        }

        return $params;
    }
}
