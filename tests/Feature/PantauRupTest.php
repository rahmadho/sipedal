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
}
