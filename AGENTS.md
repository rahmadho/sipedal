# AGENTS.md — Aturan Kerja Proyek Sipedal

Dokumen ini mengikat siapa pun (manusia atau agen) yang bekerja di repo ini.

## 1. Konfirmasi sebelum menarik data

**Setiap** aksi yang menyentuh API INAPROC wajib dikonfirmasi ke pemilik proyek
lebih dulu — termasuk yang terlihat ringan atau hanya untuk pengujian:

- `inaproc:pull` (semua varian, termasuk `--dry-run` bila tetap memanggil API)
- `inaproc:probe`
- `inaproc:validate-tokens`
- skrip/`curl`/`tinker` ad-hoc ke `data.inaproc.id`

Sebutkan lebih dulu: endpoint apa, KLPD mana, dan perkiraan jumlah request.

Alasan: kuota per token terbatas dan pengaman jaringan dapat memblokir akses
bila diuji berulang.

## 2. Delegasi pekerjaan besar

Pekerjaan besar atau berjalan lama (tarik penuh 61 endpoint, refactor lintas
berkas, audit menyeluruh, suite test panjang) **didelegasikan ke subagent**
agar sesi utama tetap bebas menerima instruksi lain. Sesi utama hanya
menggabungkan hasil dan melaporkan ringkasan.

## 3. Rahasia

- Token KLPD **tidak pernah** ditulis di chat, log, commit, atau keluaran
  terminal. Selalu disamarkan (`inprc40f...fe02`).
- `storage/app/klpd_tokens.json` berada di luar VCS (lihat `.gitignore`).
- Password basis data hanya di `.env`, tidak di dokumentasi.

## 4. Autentikasi INAPROC — temuan terverifikasi

Gateway **hanya** menerima Bearer. Diuji langsung pada `master-program`
(D462, 2026):

| Header | HTTP | Body |
|---|---:|---|
| `X-API-Key: <token>` | 401 | `{"error":"Authorization field missing"}` |
| `Authorization: Bearer <token>` | 200 | data normal |

Dokumentasi web menyebut kedua metode sebagai alternatif, tetapi kenyataannya
hanya Bearer yang diterima. Konfigurasi yang benar:

```
INAPROC_AUTH_HEADER=Authorization
INAPROC_AUTH_SCHEME=Bearer
```

PRD Bab 13 sendiri hanya menyebut "token pada header permintaan" tanpa nama
header, sehingga tidak bertentangan.

## 5. Basis data

- PostgreSQL 18 adalah satu-satunya driver. Test wajib di PostgreSQL
  (`inaproc_test`), tidak boleh SQLite.
- `inaproc` dipakai bersama dengan data pipeline lama: migration harus
  **idempotent**, tidak boleh men-drop kolom/tabel/data.
- Tabel `rec_*` berskema dinamis, dikelola `SchemaManager`, bukan migration.

## 6. Sebelum menyatakan selesai

- `./vendor/bin/phpunit` hijau.
- Perubahan angka realisasi dibandingkan dengan dataset acuan dan selisihnya
  dijelaskan (PRD Bab 12).
- `php artisan inaproc:endpoints` tetap melaporkan 65 endpoint / 61 aktif.
