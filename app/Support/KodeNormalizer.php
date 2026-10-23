<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Utilitas kode RUP (F11.4) dan penyamaran token (F9).
 */
class KodeNormalizer
{
    /**
     * Normalisasi kode RUP ke string bilangan bulat bila nilainya bulat.
     *
     * null            → ""
     * 123.0           → "123"
     * "123.0000000"   → "123"
     * " 123 "         → "123"
     * "123.5"         → "123.5"
     * "123;456"       → "123;456" (tidak dipecah di sini)
     */
    public static function normalize(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return self::fromNumericString(sprintf('%.10F', $value));
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return self::normalize((string) $value);
        }

        $string = trim((string) $value);

        if ($string === '') {
            return '';
        }

        if (is_numeric($string)) {
            return self::fromNumericString($string);
        }

        return $string;
    }

    /**
     * Pecah referensi multi-nilai (F11.3) lalu normalisasi setiap bagian.
     *
     * @return array<int, string>
     */
    public static function split(mixed $value, string $separator = ';'): array
    {
        if ($value === null) {
            return [];
        }

        if (! is_string($value)) {
            $normalized = self::normalize($value);

            return $normalized === '' ? [] : [$normalized];
        }

        if (! str_contains($value, $separator)) {
            $normalized = self::normalize($value);

            return $normalized === '' ? [] : [$normalized];
        }

        $parts = array_map(
            static fn (string $part): string => self::normalize($part),
            explode($separator, $value),
        );

        return array_values(array_unique(array_filter(
            $parts,
            static fn (string $part): bool => $part !== '',
        )));
    }

    /**
     * Samarkan token agar tidak pernah tercatat mentah di log (F9).
     * Contoh: inprc40f...fe02
     */
    public static function maskToken(?string $token): string
    {
        if ($token === null || $token === '') {
            return '(kosong)';
        }

        if (strlen($token) <= 10) {
            return Str::substr($token, 0, 2).'...'.Str::substr($token, -2);
        }

        return Str::substr($token, 0, 7).'...'.Str::substr($token, -4);
    }

    private static function fromNumericString(string $numeric): string
    {
        $numeric = trim($numeric);

        if (str_contains($numeric, '.') || stripos($numeric, 'e') !== false) {
            $float = (float) $numeric;

            if (is_finite($float) && floor($float) === $float && abs($float) < 1.0e18) {
                return number_format($float, 0, '', '');
            }

            $trimmed = rtrim(rtrim($numeric, '0'), '.');

            return $trimmed === '' || $trimmed === '-' ? '0' : $trimmed;
        }

        return ltrim($numeric, '+');
    }
}
