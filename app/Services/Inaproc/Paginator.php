<?php

declare(strict_types=1);

namespace App\Services\Inaproc;

use App\Enums\ApiFamily;
use App\Models\Endpoint;

/**
 * Strategi pengambilan data per keluarga API (F3).
 *
 * v1     — cursor pagination: ulangi sampai has_more false ATAU cursor kosong.
 * legacy — single shot: satu request, seluruh data.
 */
class Paginator
{
    public function __construct(
        private readonly ApiClient $client,
        private readonly EndpointRegistry $registry,
    ) {}

    /**
     * Tarik seluruh baris untuk satu endpoint.
     *
     * @param  array<string, string|int|null>  $derived
     * @return array{rows: array<int, array<string, mixed>>, response: ApiResponse, pages: int}
     */
    public function fetchAll(
        Endpoint $endpoint,
        ?string $kodeKlpd,
        ?int $tahun,
        ?string $token,
        string $credentialKey,
        array $derived = [],
    ): array {
        return $endpoint->family() === ApiFamily::LEGACY
            ? $this->fetchSingleShot($endpoint, $kodeKlpd, $tahun, $token, $credentialKey, $derived)
            : $this->fetchCursor($endpoint, $kodeKlpd, $tahun, $token, $credentialKey, $derived);
    }

    /**
     * F3.2 — legacy single shot.
     *
     * @param  array<string, string|int|null>  $derived
     * @return array{rows: array<int, array<string, mixed>>, response: ApiResponse, pages: int}
     */
    private function fetchSingleShot(
        Endpoint $endpoint,
        ?string $kodeKlpd,
        ?int $tahun,
        ?string $token,
        string $credentialKey,
        array $derived,
    ): array {
        $params = $this->registry->buildParams($endpoint, $kodeKlpd, $tahun, $derived);

        $response = $this->client->get($endpoint->path, $params, $endpoint->family(), $token, $credentialKey);

        return [
            'rows' => $this->normalizeRows($response->data ?? []),
            'response' => $response,
            'pages' => 1,
        ];
    }

    /**
     * F3.1 — v1 cursor pagination.
     *
     * @param  array<string, string|int|null>  $derived
     * @return array{rows: array<int, array<string, mixed>>, response: ApiResponse, pages: int}
     */
    private function fetchCursor(
        Endpoint $endpoint,
        ?string $kodeKlpd,
        ?int $tahun,
        ?string $token,
        string $credentialKey,
        array $derived,
    ): array {
        $rows = [];
        $cursor = null;
        $pages = 0;
        $maxPages = max(1, (int) config('inaproc.max_pages', 10000));
        $lastResponse = null;

        while ($pages < $maxPages) {
            $params = $this->registry->buildParams($endpoint, $kodeKlpd, $tahun, $derived, $cursor);

            $response = $this->client->get($endpoint->path, $params, $endpoint->family(), $token, $credentialKey);
            $pages++;
            $lastResponse = $response;

            // Gagal di halaman mana pun → serahkan ke pemanggil untuk fallback.
            if (! $response->ok()) {
                return [
                    'rows' => $rows,
                    'response' => $response,
                    'pages' => $pages,
                ];
            }

            $pageRows = $this->normalizeRows($response->data ?? []);
            $rows = array_merge($rows, $pageRows);

            // Halaman kosong → berhenti, jangan lanjut.
            if ($pageRows === []) {
                break;
            }

            // Terminasi: has_more false ATAU cursor kosong. Keduanya wajib diperiksa.
            if (! $response->hasMore()) {
                break;
            }

            $cursor = $response->cursor();
        }

        return [
            'rows' => $rows,
            'response' => $lastResponse ?? ApiResponse::failure(0, 'Tidak ada respons'),
            'pages' => $pages,
        ];
    }

    /**
     * @param  array<int|string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRows(array $data): array
    {
        $rows = [];

        foreach ($data as $row) {
            if (is_array($row)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}
