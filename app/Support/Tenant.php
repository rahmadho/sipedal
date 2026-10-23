<?php

namespace App\Support;

/**
 * Tenant = satu KLPD = satu domain = satu identitas visual.
 *
 * Resolusi dilakukan dari host permintaan. Bila host tidak dikenali
 * (mis. akses lewat IP saat pengembangan), dipakai tenant default dari
 * config. Bila ?klpd= diberikan dan pengguna berperan Super Admin, tenant
 * dapat dipaksa berganti — inilah satu-satunya jalan melihat tampilan
 * KLPD lain tanpa mengubah berkas host.
 */
class Tenant
{
    private array $data;
    private string $kunci;
    private array $tema;

    public function __construct(string $kunci, array $data)
    {
        $this->kunci = $kunci;
        $this->data = $data;
        $this->tema = config('sipedal.tema.' . ($data['tema'] ?? 'biru'), config('sipedal.tema.biru'));
    }

    /** Resolusi dari host, dengan cadangan tenant default. */
    public static function dariHost(?string $host): self
    {
        $host = strtolower(preg_replace('/:\d+$/', '', (string) $host));
        $semua = config('sipedal.tenants', []);

        foreach ($semua as $kunci => $data) {
            if (isset($data['domain']) && strtolower($data['domain']) === $host) {
                return new self($kunci, $data);
            }
        }

        $bawaan = config('sipedal.default_tenant', 'd462');

        return new self($bawaan, $semua[$bawaan] ?? []);
    }

    public static function dariKunci(string $kunci): self
    {
        $semua = config('sipedal.tenants', []);
        $kunci = array_key_exists($kunci, $semua) ? $kunci : config('sipedal.default_tenant', 'd462');

        return new self($kunci, $semua[$kunci] ?? []);
    }

    /* ---------- Identitas ---------- */

    public function kunci(): string { return $this->kunci; }
    public function kode(): string { return $this->data['kode'] ?? 'D000'; }
    public function nama(): string { return $this->data['nama'] ?? 'KLPD'; }
    public function namaPendek(): string { return $this->data['nama_pendek'] ?? $this->nama(); }
    public function instansi(): string { return $this->data['instansi'] ?? ''; }
    public function domain(): string { return $this->data['domain'] ?? ''; }
    public function logo(): string { return $this->data['logo'] ?? 'img/logo/tenant-default.svg'; }
    public function logoPemda(): string { return $this->data['logo_pemda'] ?? 'img/logo/pemda-default.svg'; }

    /** Daftar gambar latar login untuk tenant ini. */
    public function latarLogin(): array
    {
        return $this->data['login_bg'] ?? ['img/login/bg-01.svg'];
    }

    /** Nama tema yang dipakai, mis. "biru". */
    public function namaTema(): string
    {
        return $this->data['tema'] ?? 'biru';
    }

    /** Label tema, mis. "Biru Nusantara". */
    public function labelTema(): string
    {
        return $this->tema['nama'] ?? 'Biru Nusantara';
    }

    /**
     * Token CSS untuk tema ini, siap dicetak sebagai atribut style.
     * Inilah mekanisme yang membuat satu tampilan dapat melayani banyak KLPD.
     */
    public function tokenCss(): string
    {
        $bagian = [];
        foreach (($this->tema['tokens'] ?? []) as $nama => $nilai) {
            $bagian[] = $nama . ':' . $nilai;
        }

        return implode(';', $bagian);
    }

    /** Warna utama tema — dipakai untuk <meta name="theme-color">. */
    public function warnaUtama(): string
    {
        return $this->tema['tokens']['--brand-600'] ?? '#1d4ed8';
    }

    public function keArray(): array
    {
        return [
            'kunci' => $this->kunci,
            'kode' => $this->kode(),
            'nama' => $this->nama(),
            'nama_pendek' => $this->namaPendek(),
            'instansi' => $this->instansi(),
            'domain' => $this->domain(),
            'logo' => $this->logo(),
            'logo_pemda' => $this->logoPemda(),
            'tema' => $this->namaTema(),
            'label_tema' => $this->labelTema(),
        ];
    }
}
