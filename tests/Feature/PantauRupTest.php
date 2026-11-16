<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\RupRepository;
use Tests\TestCase;

class PantauRupTest extends TestCase
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

    public function test_halaman_pantau_rup_memerlukan_autentikasi(): void
    {
        $this->get('/report/pantau/rup')->assertRedirect('/login');
    }

    public function test_halaman_pantau_rup_menampilkan_seluruh_komponen_wajib(): void
    {
        $this->withSession($this->authSession())
            ->get('/report/pantau/rup')
            ->assertOk()
            ->assertSee('Pantau RUP (Rencana Umum Pengadaan)')
            ->assertSee('Rekap Status PDN')
            ->assertSee('Berdasarkan Tipe Paket')
            ->assertSee('Jenis Pengadaan')
            ->assertSee('Rekapitulasi Paket Swakelola Berdasarkan Tipe')
            ->assertSee('Rekapitulasi per Satuan Kerja')
            ->assertSee('Daftar Paket RUP Terumumkan')
            ->assertSee('Penyedia & PDS');
    }

    public function test_tab_swakelola_menampilkan_tabel_swakelola(): void
    {
        $this->withSession($this->authSession())
            ->get('/report/pantau/rup?tab=swakelola')
            ->assertOk()
            ->assertSee('Tipe Swakelola')
            ->assertSee('Penyelenggara');
    }

    public function test_rup_repository_menghasilkan_struktur_data_valid(): void
    {
        $ringkasan = RupRepository::ringkasan('D462', 2026);

        $this->assertArrayHasKey('pdn', $ringkasan);
        $this->assertArrayHasKey('tipe_paket', $ringkasan);
        $this->assertArrayHasKey('jenis_pengadaan', $ringkasan);
        $this->assertArrayHasKey('swakelola', $ringkasan);

        $this->assertArrayHasKey('pdn', $ringkasan['pdn']);
        $this->assertArrayHasKey('non_pdn', $ringkasan['pdn']);
        $this->assertArrayHasKey('penyedia', $ringkasan['tipe_paket']);
        $this->assertArrayHasKey('pds', $ringkasan['tipe_paket']);

        $satker = RupRepository::rekapSatker('D462', 2026);
        $this->assertArrayHasKey('baris', $satker);
        $this->assertArrayHasKey('grand_total', $satker);
    }

    public function test_label_proses_pemetaan(): void
    {
        $katalog = RupRepository::labelProses('e-katalog');
        $this->assertSame('E-Katalog', $katalog['label']);
        $this->assertTrue($katalog['diproses']);

        $tender = RupRepository::labelProses('tender');
        $this->assertSame('Tender', $tender['label']);
        $this->assertTrue($tender['diproses']);

        $belum = RupRepository::labelProses(null);
        $this->assertSame('Belum Diproses', $belum['label']);
        $this->assertFalse($belum['diproses']);
    }

    public function test_ajax_serverside_paket_rup_berfungsi(): void
    {
        $response = $this->withSession($this->authSession())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/report/pantau/rup?status_proses=e-katalog&sort=pagu&arah=desc');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'tab',
                'total',
                'halaman',
                'perHalaman',
                'sort',
                'arah',
                'html_tabel',
                'html_paginasi',
            ]);

        $this->assertStringContainsString('E-Katalog', (string) $response->json('html_tabel'));
    }

    public function test_ajax_serverside_mendukung_pengaturan_per_halaman(): void
    {
        $response = $this->withSession($this->authSession())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/report/pantau/rup?per_halaman=50');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'perHalaman' => 50,
            ]);
    }

    public function test_rekap_satker_datatable_dan_thead_grouping(): void
    {
        $response = $this->withSession($this->authSession())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/report/pantau/rup?tabel=satker&per_halaman=10');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'tabel',
                'total',
                'halaman',
                'perHalaman',
                'sort',
                'arah',
                'html_tabel',
                'html_paginasi',
            ]);

        $htmlTabel = (string) $response->json('html_tabel');
        $this->assertStringContainsString('colspan="2"', $htmlTabel);
        $this->assertStringContainsString('Penyedia', $htmlTabel);
        $this->assertStringContainsString('Swakelola', $htmlTabel);
        $this->assertStringContainsString('Penyedia Dalam Swakelola', $htmlTabel);
        $this->assertStringContainsString('Total Paket', $htmlTabel);
        $this->assertStringContainsString('Total Pagu', $htmlTabel);
    }
}
