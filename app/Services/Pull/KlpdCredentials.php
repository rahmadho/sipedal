<?php

declare(strict_types=1);

namespace App\Services\Pull;

use App\Support\KodeNormalizer;
use Illuminate\Support\Facades\File;

/**
 * Kredensial KLPD (F8).
 *
 * Satu token mewakili tepat satu KLPD. Berkas kredensial berada di luar
 * `.env` dan wajib masuk `.gitignore`.
 *
 * Format storage/app/klpd_tokens.json:
 * {
 *   "D462": { "token": "inprc...", "label": "Nama instansi" },
 *   "D472": { "token": "",         "label": "Belum ada token" }
 * }
 */
class KlpdCredentials
{
    /**
     * @return array<string, array{token: string, label: string}>
     */
    public function all(): array
    {
        $driver = (string) config('inaproc.credentials_driver', 'file');

        return $driver === 'database' ? $this->fromDatabase() : $this->fromFile();
    }

    /**
     * @return array<string, array{token: string, label: string}>
     */
    private function fromFile(): array
    {
        $path = (string) config('inaproc.klpd_file');

        if ($path === '' || ! File::exists($path)) {
            return [];
        }

        $decoded = json_decode((string) File::get($path), true);

        if (! is_array($decoded)) {
            return [];
        }

        $out = [];

        foreach ($decoded as $kode => $entry) {
            if (! is_string($kode) || $kode === '') {
                continue;
            }

            $token = is_array($entry) ? (string) ($entry['token'] ?? '') : (string) $entry;
            $label = is_array($entry) ? (string) ($entry['label'] ?? '') : '';

            $out[$kode] = ['token' => trim($token), 'label' => $label];
        }

        return $out;
    }

    /**
     * @return array<string, array{token: string, label: string}>
     */
    private function fromDatabase(): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('klpd_credentials')) {
            return [];
        }

        $rows = \Illuminate\Support\Facades\DB::table('klpd_credentials')
            ->where('is_active', true)
            ->get();

        $out = [];

        foreach ($rows as $row) {
            $token = (string) $row->token_encrypted;

            // Nilai terenkripsi Laravel (Crypt) dibuka bila memungkinkan.
            try {
                $token = \Illuminate\Support\Facades\Crypt::decryptString($token);
            } catch (\Throwable) {
                // Biarkan apa adanya bila memang plaintext.
            }

            $out[(string) $row->kode] = ['token' => trim($token), 'label' => (string) ($row->label ?? '')];
        }

        return $out;
    }

    /**
     * @return array{token: string, label: string}|null
     */
    public function for(string $kodeKlpd): ?array
    {
        return $this->all()[$kodeKlpd] ?? null;
    }

    public function hasToken(string $kodeKlpd): bool
    {
        $entry = $this->for($kodeKlpd);

        return $entry !== null && $entry['token'] !== '';
    }

    /**
     * Kunci kredensial untuk rate limiter — tidak pernah token mentah.
     */
    public function credentialKey(string $kodeKlpd, string $token): string
    {
        return $kodeKlpd.'|'.hash('sha256', $token);
    }

    public function maskedToken(string $kodeKlpd): string
    {
        return KodeNormalizer::maskToken($this->for($kodeKlpd)['token'] ?? null);
    }

    /**
     * Pastikan berkas kredensial ada (kosong) dan .gitignore memuatnya.
     */
    public function ensureFileExists(): string
    {
        $path = (string) config('inaproc.klpd_file');

        if (! File::exists($path)) {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, json_encode(new \stdClass, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
        }

        return $path;
    }
}
