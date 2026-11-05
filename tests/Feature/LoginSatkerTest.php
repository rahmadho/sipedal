<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Login menyimpan satuan kerja (rup_stk_id) dan menolak peran yang wajib
 * memilikinya bila kosong.
 */
class LoginSatkerTest extends TestCase
{
    public function test_form_login_menawarkan_satuan_kerja(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('name="rup_stk_id"', false)
            ->assertSee('Dinas Pendidikan', false)
            ->assertSee('js-combobox-asal', false);
    }

    public function test_kepala_opd_tanpa_satker_ditolak(): void
    {
        $this->post('/login', [
            'username' => 'rahmat.hidayat',
            'peran' => 'kepala_opd',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('galat');

        $this->assertNull(session('username'));
    }

    public function test_ppk_tanpa_satker_ditolak(): void
    {
        $this->post('/login', [
            'username' => 'rahmat.hidayat',
            'peran' => 'ppk',
        ])->assertSessionHas('galat');
    }

    public function test_kepala_opd_dengan_satker_masuk_dan_tersimpan(): void
    {
        $this->post('/login', [
            'username' => 'rahmat.hidayat',
            'peran' => 'kepala_opd',
            'rup_stk_id' => 66002,
        ])->assertRedirect(route('dashboard'));

        $this->assertSame('kepala_opd', session('peran'));
        $this->assertSame(66002, session('rup_stk_id'));
    }

    public function test_super_admin_tanpa_satker_tetap_masuk(): void
    {
        $this->post('/login', [
            'username' => 'rahmat.hidayat',
            'peran' => 'super_admin',
        ])->assertRedirect(route('dashboard'));

        $this->assertNull(session('rup_stk_id'));
    }

    public function test_pesan_galat_menyebut_peran(): void
    {
        $this->post('/login', [
            'username' => 'rahmat.hidayat',
            'peran' => 'ppk',
        ]);

        $this->assertStringContainsString('Pejabat Pembuat Komitmen', session('galat'));
    }
}
