<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\KodeNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * F11.4 — normalisasi kode RUP.
 */
class KodeNormalizerTest extends TestCase
{
    #[DataProvider('normalizeCases')]
    public function test_normalize(mixed $input, string $expected): void
    {
        $this->assertSame($expected, KodeNormalizer::normalize($input));
    }

    /**
     * @return array<string, array{mixed, string}>
     */
    public static function normalizeCases(): array
    {
        return [
            'null' => [null, ''],
            'float bulat' => [123.0, '123'],
            'decimal string bulat' => ['123.0000000000', '123'],
            'spasi berlebih' => [' 123 ', '123'],
            'desimal bukan bulat' => ['123.5', '123.5'],
            'integer' => [123, '123'],
            'string kosong' => ['', ''],
            'numeric besar' => ['123450.0', '123450'],
        ];
    }

    public function test_split_reference_multi_nilai(): void
    {
        $this->assertSame(['123', '456'], KodeNormalizer::split('123;456'));
        $this->assertSame(['123'], KodeNormalizer::split(' 123 '));
        $this->assertSame(['123', '456'], KodeNormalizer::split('123;456;'));
        $this->assertSame(['123', '456'], KodeNormalizer::split('123;;456'));
        $this->assertSame([], KodeNormalizer::split(null));
        $this->assertSame([], KodeNormalizer::split('   '));
        $this->assertSame(['123'], KodeNormalizer::split(123.0));
    }

    public function test_mask_token_tidak_membocorkan_isi(): void
    {
        $masked = KodeNormalizer::maskToken('inprc40f9a8b7c6d5e4f3a2b1c0d9e8f7a6b5c4d3e2f1fe02');

        $this->assertStringContainsString('...', $masked);
        $this->assertStringStartsNotWith('inprc40f9a8b7c6d5', $masked);
        $this->assertSame('(kosong)', KodeNormalizer::maskToken(null));
        $this->assertSame('(kosong)', KodeNormalizer::maskToken(''));
    }
}
