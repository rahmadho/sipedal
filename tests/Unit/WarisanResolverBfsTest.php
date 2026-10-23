<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Realisasi\ProsesCollector;
use App\Services\Realisasi\WarisanResolver;
use Tests\TestCase;

/**
 * F11.5 — BFS pewarisan kaji ulang.
 *
 * Rantai dibuat manual; tidak menyentuh database.
 */
class WarisanResolverBfsTest extends TestCase
{
    /**
     * @param  array<string, array<int, string>>  $chains
     */
    private function resolver(array $chains): WarisanResolver
    {
        $resolver = new class(new ProsesCollector) extends WarisanResolver
        {
            public function __construct(ProsesCollector $c)
            {
                parent::__construct($c);
            }

            /**
             * @param  array<string, array<int, string>>  $chains
             */
            public function seed(array $chains): void
            {
                $ref = new \ReflectionProperty(WarisanResolver::class, 'chains');
                $ref->setValue($this, $chains);
            }
        };

        $resolver->seed($chains);

        return $resolver;
    }

    public function test_rantai_satu_level(): void
    {
        $resolver = $this->resolver(['200' => ['100']]);

        $result = $resolver->bfs('200', ['100' => ['tender' => true]]);

        $this->assertSame(['sumber' => 'tender', 'dari_rup' => '100', 'depth' => 1], $result);
    }

    public function test_rantai_dua_dan_tiga_level_depth_benar(): void
    {
        $resolver = $this->resolver([
            '300' => ['200'],
            '200' => ['100'],
            '400' => ['300'],
        ]);

        $proses = ['100' => ['tender' => true]];

        $this->assertSame(2, $resolver->bfs('300', $proses)['depth']);
        $this->assertSame(3, $resolver->bfs('400', $proses)['depth']);
    }

    public function test_leluhur_terdekat_dimenangkan_bukan_yang_terdalam(): void
    {
        // 10 -> 20 -> 30(proses), dan 10 -> 25(proses). BFS harus memilih 25.
        $resolver = $this->resolver([
            '10' => ['20', '25'],
            '20' => ['30'],
        ]);

        $result = $resolver->bfs('10', [
            '30' => ['tender' => true],
            '25' => ['non-tender' => true],
        ]);

        $this->assertSame('25', $result['dari_rup']);
        $this->assertSame(1, $result['depth']);
        $this->assertSame('non-tender', $result['sumber']);
    }

    public function test_rantai_lima_level(): void
    {
        $resolver = $this->resolver([
            '5' => ['4'],
            '4' => ['3'],
            '3' => ['2'],
            '2' => ['1'],
        ]);

        $result = $resolver->bfs('5', ['1' => ['e-katalog' => true]]);

        $this->assertSame(4, $result['depth']);
        $this->assertSame('1', $result['dari_rup']);
    }

    public function test_siklus_tidak_menyebabkan_loop_tak_berujung(): void
    {
        $resolver = $this->resolver([
            '1' => ['2'],
            '2' => ['1'],
        ]);

        $this->assertNull($resolver->bfs('1', []));
    }

    public function test_batas_kedalaman_30(): void
    {
        // Rantai 40 level, proses hanya di level 1 -> melewati batas 30.
        $chains = [];

        for ($i = 1; $i <= 40; $i++) {
            $chains[(string) ($i + 1)] = [(string) $i];
        }

        $resolver = $this->resolver($chains);

        $this->assertNull($resolver->bfs('41', ['1' => ['tender' => true]], 30));
    }

    public function test_rup_dengan_proses_sendiri_tidak_mewarisi(): void
    {
        $resolver = $this->resolver(['100' => ['90']]);

        $result = $resolver->resolve(
            prosesMap: ['100' => ['tender' => true], '90' => ['non-tender' => true]],
            paketCodes: ['100'],
            maxDepth: 30,
        );

        $this->assertCount(1, $result['proses']);
        $this->assertSame('tender', $result['proses'][0]['sumber']);
        $this->assertCount(0, $result['warisan']);
    }
}
