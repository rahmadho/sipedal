<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppGateway
{
    /**
     * Konfigurasi WhatsApp Gateway aktif (Baileys / Custom).
     */
    public static function config(?string $kdKlpd = null): array
    {
        $kd = $kdKlpd ?: session('kd_klpd', 'GLOBAL');

        return [
            'provider' => AppSetting::get('wa_provider', $kd, 'baileys'),
            'endpoint_url' => AppSetting::get('wa_endpoint_url', $kd, 'http://127.0.0.1:8090'),
            'api_key' => AppSetting::get('wa_api_key', $kd, ''),
            'device_id' => AppSetting::get('wa_device_id', $kd, 'sipedal-wa'),
            'target_nomor' => AppSetting::get('wa_target_nomor', $kd, ''),
            'notif_sync_selesai' => (bool) AppSetting::get('wa_notif_sync_selesai', $kd, true),
            'notif_sync_gagal' => (bool) AppSetting::get('wa_notif_sync_gagal', $kd, true),
        ];
    }

    /**
     * Memeriksa status sesi & device Baileys.
     */
    public static function status(?string $kdKlpd = null): array
    {
        $cfg = self::config($kdKlpd);
        $baseUrl = rtrim($cfg['endpoint_url'], '/');

        try {
            $resp = Http::timeout(4)->get("{$baseUrl}/session");
            if ($resp->successful()) {
                $data = $resp->json('data', []);
                return [
                    'ok' => true,
                    'connected' => (bool) ($data['connected'] ?? false),
                    'state' => $data['state'] ?? 'unknown',
                    'paired_number' => $data['paired_number'] ?? null,
                    'paired_name' => $data['paired_name'] ?? null,
                    'qr_pending' => (bool) ($data['qr_pending'] ?? false),
                    'auth_dir' => $data['auth_dir'] ?? null,
                ];
            }
        } catch (\Throwable $e) {
            // Service offline
        }

        return [
            'ok' => false,
            'connected' => false,
            'state' => 'offline',
            'pesan' => 'Layanan Baileys lokal belum aktif atau tidak dapat dijangkau.',
        ];
    }

    /**
     * Mengambil QR code (data URL/base64 atau PNG URL).
     */
    public static function getQr(?string $kdKlpd = null): ?string
    {
        $cfg = self::config($kdKlpd);
        $baseUrl = rtrim($cfg['endpoint_url'], '/');

        try {
            $resp = Http::timeout(5)->get("{$baseUrl}/session/qr");
            if ($resp->successful() && $resp->json('data.qr')) {
                return $resp->json('data.qr');
            }
        } catch (\Throwable) {
            //
        }

        return null;
    }

    /**
     * Memulai sesi pairing baru untuk scan QR code.
     */
    public static function requestPair(?string $kdKlpd = null): array
    {
        $cfg = self::config($kdKlpd);
        $baseUrl = rtrim($cfg['endpoint_url'], '/');

        try {
            $resp = Http::timeout(8)->post("{$baseUrl}/session/pair");
            return [
                'ok' => $resp->successful(),
                'data' => $resp->json('data', []),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'pesan' => $e->getMessage(),
            ];
        }
    }

    /**
     * Memutuskan sesi WhatsApp (Logout / Unpair).
     */
    public static function logout(?string $kdKlpd = null): array
    {
        $cfg = self::config($kdKlpd);
        $baseUrl = rtrim($cfg['endpoint_url'], '/');

        try {
            $resp = Http::timeout(8)->delete("{$baseUrl}/session");
            return [
                'ok' => $resp->successful(),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'pesan' => $e->getMessage(),
            ];
        }
    }

    /**
     * Mengirim pesan teks ke WhatsApp tujuan.
     */
    public static function kirimPesan(string $nomor, string $pesan, ?string $kdKlpd = null): array
    {
        $cfg = self::config($kdKlpd);

        if (empty($cfg['endpoint_url'])) {
            return [
                'ok' => false,
                'pesan' => 'Endpoint URL WhatsApp Gateway belum diatur.',
            ];
        }

        $baseUrl = rtrim($cfg['endpoint_url'], '/');

        // Normalisasi nomor ke format internasional (mis. 628xxx)
        $target = preg_replace('/[^0-9]/', '', $nomor);
        if (str_starts_with($target, '0')) {
            $target = '62' . substr($target, 1);
        }

        try {
            if ($cfg['provider'] === 'baileys' || str_contains($baseUrl, '8090')) {
                $payload = [
                    'to' => $target,
                    'text' => $pesan,
                ];
                $resp = Http::timeout(15)->post("{$baseUrl}/v1/messages", $payload);
            } else {
                $headers = ['Content-Type' => 'application/json'];
                if (!empty($cfg['api_key'])) {
                    $headers['Authorization'] = $cfg['api_key'];
                    $headers['api-key'] = $cfg['api_key'];
                }

                $payload = [
                    'target' => $target,
                    'phone' => $target,
                    'number' => $target,
                    'to' => $target,
                    'message' => $pesan,
                    'text' => $pesan,
                    'device_id' => $cfg['device_id'],
                ];

                $resp = Http::withHeaders($headers)
                    ->timeout(15)
                    ->post($baseUrl . '/v1/messages', $payload);
            }

            if ($resp->successful()) {
                return [
                    'ok' => true,
                    'pesan' => 'Pesan WhatsApp berhasil dikirim.',
                    'response' => $resp->json(),
                ];
            }

            return [
                'ok' => false,
                'pesan' => 'Gateway merespons galat (' . $resp->status() . '): ' . $resp->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('Gagal kirim pesan WhatsApp: ' . $e->getMessage());

            return [
                'ok' => false,
                'pesan' => 'Kesalahan koneksi gateway: ' . $e->getMessage(),
            ];
        }
    }
}
