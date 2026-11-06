<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $table = 'app_settings';

    protected $fillable = [
        'kd_klpd',
        'key',
        'value',
    ];

    public static function get(string $key, ?string $kdKlpd = null, mixed $default = null): mixed
    {
        $kd = $kdKlpd ? strtoupper($kdKlpd) : 'GLOBAL';

        $row = static::where('kd_klpd', $kd)->where('key', $key)->first();
        if ($row !== null) {
            return $row->value;
        }

        if ($kd !== 'GLOBAL') {
            $globalRow = static::where('kd_klpd', 'GLOBAL')->where('key', $key)->first();
            if ($globalRow !== null) {
                return $globalRow->value;
            }
        }

        return $default;
    }

    public static function set(string $key, mixed $value, ?string $kdKlpd = null): void
    {
        $kd = $kdKlpd ? strtoupper($kdKlpd) : 'GLOBAL';

        static::updateOrCreate(
            ['kd_klpd' => $kd, 'key' => $key],
            ['value' => is_scalar($value) || $value === null ? (string) $value : json_encode($value)]
        );
    }
}
