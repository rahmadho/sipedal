<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class RealisasiRupTest extends TestCase
{
    private function authSession(): array
    {
        return [
            'peran' => 'super_admin',
            'nama' => 'Super Administrator',
            'username' => 'admin',
            'kd_klpd' => 'D462',
            'tahun' => 2026,
        ];
    }

    public function test_halaman_realisasi_dapat_diakses_dengan_data_riil(): void
    {
        $response = $this->withSession($this->authSession())
            ->get('/realisasi');

        $response->assertOk()
            ->assertSee('Realisasi RUP')
            ->assertSee('Daftar Realisasi RUP')
            ->assertSee('dt-realisasi')
            ->assertSee('E-Katalog');
    }

    public function test_ajax_datatable_realisasi_mengembalikan_json(): void
    {
        $response = $this->withSession($this->authSession())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/realisasi?per_halaman=10&sumber=e-katalog');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'total',
                'halaman',
                'perHalaman',
                'sort',
                'arah',
                'html_tabel',
                'html_paginasi',
            ]);

        $this->assertGreaterThan(0, (int) $response->json('total'));
        $this->assertStringContainsString('E-Katalog', (string) $response->json('html_tabel'));
    }

    public function test_ajax_datatable_realisasi_sorting_pagu(): void
    {
        $response = $this->withSession($this->authSession())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/realisasi?sort=pagu&arah=desc&per_halaman=10');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'sort' => 'pagu',
                'arah' => 'desc',
            ]);
    }
}
