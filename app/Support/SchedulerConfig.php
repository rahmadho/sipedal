<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\File;

class SchedulerConfig
{
    private static ?string $path = null;

    public static function path(): string
    {
        return self::$path ?? storage_path('app/scheduler_config.json');
    }

    public static function setPath(?string $path): void
    {
        self::$path = $path;
    }

    public static function default(): array
    {
        return [
            'enabled' => true,
            'jobs' => [
                'pull' => [
                    'label' => 'Tarik Data INAPROC (inaproc:pull)',
                    'desc' => 'Menarik seluruh endpoint aktif untuk seluruh KLPD terdaftar',
                    'enabled' => true,
                    'times' => ['04:00', '16:00'],
                    'command' => 'inaproc:pull --all --sync',
                ],
                'build_realisasi' => [
                    'label' => 'Hitung Ulang Realisasi RUP (inaproc:build-realisasi)',
                    'desc' => 'Membangun ulang ringkasan realisasi RUP dari data mentah',
                    'enabled' => true,
                    'times' => ['05:00', '17:00'],
                    'command' => 'inaproc:build-realisasi',
                ],
                'validate_tokens' => [
                    'label' => 'Validasi Token KLPD (inaproc:validate-tokens)',
                    'desc' => 'Memeriksa keabsahan token INAPROC seluruh KLPD secara berkala',
                    'enabled' => false,
                    'times' => ['00:00'],
                    'command' => 'inaproc:validate-tokens',
                ],
            ],
            'updated_at' => now()->toIso8601String(),
        ];
    }

    public static function get(): array
    {
        $path = self::path();
        if (!File::exists($path)) {
            $default = self::default();
            self::save($default);
            return $default;
        }

        try {
            $data = json_decode(File::get($path), true);
            if (!is_array($data)) {
                return self::default();
            }
            return array_replace_recursive(self::default(), $data);
        } catch (\Throwable) {
            return self::default();
        }
    }

    public static function save(array $config): bool
    {
        $dir = dirname(self::path());
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $config['updated_at'] = now()->toIso8601String();
        return (bool) File::put(self::path(), json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
