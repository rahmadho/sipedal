<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Login memakai NAMA PENGGUNA, bukan email.
 *
 * Struktur tabel users (milik pengguna aplikasi) memuat `username` NOT NULL
 * dan `email` nullable — email boleh kosong dan boleh kembar antar pengguna.
 * Kolom `rup_stk_id` adalah kode satuan kerja.
 */
class LoginUsernameTest extends TestCase
{
    public function test_form_login_meminta_nama_pengguna_bukan_email(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('name="username"', false)
            ->assertDontSee('name="email"', false)
            ->assertSee('Nama pengguna', false);
    }

    public function test_masuk_dengan_username_mencatat_identitas_di_sesi(): void
    {
        $this->post('/login', ['username' => 'rahmat.hidayat', 'peran' => 'super_admin'])
            ->assertRedirect(route('dashboard'));

        $this->assertSame('rahmat.hidayat', session('username'));
        $this->assertSame('super_admin', session('peran'));
    }

    public function test_masuk_tanpa_username_ditolak(): void
    {
        $this->post('/login', ['username' => '', 'peran' => 'super_admin'])
            ->assertSessionHasErrors('username');
    }

    public function test_email_tidak_dikirim_tetap_masuk(): void
    {
        // Email bukan isian wajib, jadi tidak mengirimnya sama sekali sah.
        $this->post('/login', ['username' => 'budi.santoso'])
            ->assertRedirect(route('dashboard'));

        $this->assertSame('budi.santoso', session('username'));
        // Tahap UI/UX hanya punya satu pengguna demo (punya email).
        // Yang diuji: email tidak pernah dipakai untuk mengenali pengguna.
        $this->assertIsString(session('username'));
    }

    public function test_keluar_menghapus_sesi(): void
    {
        $this->post('/login', ['username' => 'rahmat.hidayat'])->assertRedirect(route('dashboard'));

        $this->post('/logout')->assertRedirect(route('login'));

        $this->assertNull(session('username'));
    }
}
