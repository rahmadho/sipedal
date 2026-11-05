<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class ProfileRupStkIdTest extends TestCase
{
    public function test_halaman_profil_menampilkan_pilihan_satker(): void
    {
        $this->withSession([
            'peran' => 'super_admin',
            'username' => 'rahmat.hidayat',
            'nama' => 'Rahmat Hidayat',
        ])
            ->get('/setting/profile')
            ->assertOk()
            ->assertSee('Satuan Kerja', false)
            ->assertSee('name="rup_stk_id"', false);
    }

    public function test_simpan_profil_kepala_opd_tanpa_satker_gagal(): void
    {
        $this->withSession([
            'peran' => 'kepala_opd',
            'username' => 'rahmat.hidayat',
            'nama' => 'Rahmat Hidayat',
        ])
            ->post('/setting/profile', [
                'nama' => 'Rahmat Baru',
                'rup_stk_id' => '',
            ])
            ->assertSessionHasErrors('rup_stk_id');
    }

    public function test_simpan_profil_kepala_opd_dengan_satker_berhasil(): void
    {
        $this->withSession([
            'peran' => 'kepala_opd',
            'username' => 'rahmat.hidayat',
            'nama' => 'Rahmat Hidayat',
        ])
            ->post('/setting/profile', [
                'nama' => 'Rahmat Hidayat',
                'rup_stk_id' => 66005,
            ])
            ->assertRedirect(route('setting.profile'))
            ->assertSessionHas('status');

        $this->assertSame(66005, session('rup_stk_id'));
    }

    public function test_simpan_profil_super_admin_tanpa_satker_berhasil(): void
    {
        $this->withSession([
            'peran' => 'super_admin',
            'username' => 'rahmat.hidayat',
            'nama' => 'Rahmat Hidayat',
        ])
            ->post('/setting/profile', [
                'nama' => 'Rahmat Hidayat',
                'rup_stk_id' => '',
            ])
            ->assertRedirect(route('setting.profile'))
            ->assertSessionHas('status');

        $this->assertNull(session('rup_stk_id'));
    }
}
