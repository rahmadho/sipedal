<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\SatkerFilter;
use Tests\TestCase;

class SatkerFilterTest extends TestCase
{
    public function test_peran_kepala_opd_dan_ppk_wajib_satker(): void
    {
        $this->assertTrue(SatkerFilter::wajib('kepala_opd'));
        $this->assertTrue(SatkerFilter::wajib('ppk'));
    }

    public function test_peran_lain_tidak_wajib_satker(): void
    {
        foreach (['super_admin', 'admin_klpd', 'kepala_pbj', 'pokja', 'operator', 'penyedia'] as $peran) {
            $this->assertFalse(SatkerFilter::wajib($peran), "{$peran} seharusnya tidak wajib");
        }
    }

    public function test_wajib_tanpa_peran_false(): void
    {
        $this->assertFalse(SatkerFilter::wajib(null));
    }

    public function test_pesan_berbahasa_indonesia(): void
    {
        $pesan = SatkerFilter::pesan('kepala_opd');

        $this->assertStringContainsString('satuan kerja', $pesan);
        $this->assertStringContainsString('Kepala OPD', $pesan);
    }

    public function test_berlakunya_butuh_peran_wajib_dan_satker(): void
    {
        $this->assertFalse(SatkerFilter::berlakunya(['peran' => 'kepala_opd']));
        $this->assertFalse(SatkerFilter::berlakunya(['peran' => 'kepala_opd', 'rup_stk_id' => null]));
        $this->assertFalse(SatkerFilter::berlakunya(['peran' => 'super_admin', 'rup_stk_id' => 66001]));
        $this->assertTrue(SatkerFilter::berlakunya(['peran' => 'ppk', 'rup_stk_id' => 66001]));
    }

    public function test_terapkan_menyaring_berdasarkan_satker(): void
    {
        $baris = [
            ['nama' => 'A', 'kd_satker' => 66001],
            ['nama' => 'B', 'kd_satker' => 66002],
            ['nama' => 'C', 'kd_satker' => 66001],
        ];

        $hasil = SatkerFilter::terapkan($baris, 66001);

        $this->assertCount(2, $hasil);
        $this->assertSame(['A', 'C'], array_column($hasil, 'nama'));
    }

    public function test_terapkan_tanpa_satker_mengembalikan_semua(): void
    {
        $baris = [['kd_satker' => 1], ['kd_satker' => 2]];

        $this->assertSame($baris, SatkerFilter::terapkan($baris, null));
    }

    public function test_terapkan_membiarkan_baris_tanpa_kd_satker(): void
    {
        $baris = [['nama' => 'Ringkasan'], ['kd_satker' => 66001]];

        $this->assertCount(2, SatkerFilter::terapkan($baris, 66001));
    }
}
