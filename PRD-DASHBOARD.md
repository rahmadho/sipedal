# PRD — Dashboard Inaproc

**Nama kerja:** `inaproc-dashboard`
**Versi dokumen:** 1.0
**Tanggal:** 2026-10-23
**Status:** Siap eksekusi sebagai proyek baru
**Stack:** Laravel 13 · PHP 8.4 · Livewire 3 · Tailwind CSS · ApexCharts
**Bergantung pada:** `PRD.md` (Inaproc Data Pipeline) — sudah berjalan, lihat `/home/hermes/workspaces/sipedal`

---

## 1. Ringkasan Produk

Aplikasi web untuk membaca, menilai, dan menyajikan hasil pipeline data pengadaan INAPROC. Dashboard ini menjawab tiga pertanyaan yang tidak dapat dijawab pipeline CLI:

1. **Berapa uang yang sudah dipakai, dan lewat jalur apa?** — realisasi RUP per sumber proses, per satker, per metode pengadaan.
2. **Berapa yang belum terpakai?** — 12.361 dari 16.024 paket RUP penyedia belum memiliki proses pengadaan.
3. **Apakah angka ini bisa dipercaya?** — kesegaran data, status tarikan, dan cakupan KLPD.

Sistem ini adalah **lapisan baca**, bukan lapisan tulis. Dashboard tidak pernah mengubah tabel `rec_*`, `ref_rup_*`, maupun `pull_log`. Satu-satunya operasi tulis di sisi pipeline adalah pembacaan; seluruh tabel milik dashboard berada di database terpisah.

### 1.1 Masalah yang Diselesaikan

| Masalah | Akar Sebab | Solusi |
|---|---|---|
| Angka realisasi hanya bisa dilihat lewat query SQL | Pipeline sengaja tanpa antarmuka | Halaman realisasi dengan agregasi siap-pakai |
| Tidak jelas paket mana yang belum terpakai | Relasi RUP→proses hanya tersimpan sebagai himpunan | Penanda `terealisasi`/`belum` di setiap baris paket, plus KPI cakupan |
| Pengguna mengira angka = kondisi nasional | Hanya 1 KLPD yang datanya lengkap | Banner cakupan data yang melekat di setiap halaman |
| Angka bisa basi tanpa peringatan | `_fetched_at` tidak pernah dibandingkan | Indikator kesegaran + ambang peringatan |
| Selisih antar-sumber tidak terlihat | Empat sumber proses berbeda struktur | Perbandingan pagu vs HPS, komposisi per sumber |
| Drill-down rantai warisan RUP sulit | Pewarisan transitif hingga `depth` 6 | Penampil rantai `dari_rup` sebagai breadcrumb |

### 1.2 Prinsip Desain

1. **Baca saja.** Tidak ada satu pun jalur tulis ke database pipeline. Dijamin oleh hak akses PostgreSQL, bukan hanya oleh kode.
2. **Angka harus dapat ditelusuri.** Setiap KPI dapat diklik menuju daftar baris penyusunnya.
3. **Kesegaran lebih penting daripada kelengkapan.** Lebih baik menampilkan "data 3 hari lalu" daripada menyembunyikan bahwa data basi.
4. **Cakupan diucapkan, bukan diasumsikan.** Setiap halaman menyatakan KLPD dan tahun mana yang sedang dilihat.
5. **Filter adalah URL.** Setiap keadaan layar dapat di-bookmark dan dibagikan apa adanya.
6. **Berat di database, ringan di aplikasi.** Agregasi dikerjakan PostgreSQL; PHP tidak pernah memuat 16.000 baris untuk menghitung total.

---

## 2. Tujuan & Ukuran Keberhasilan

| # | Tujuan | Ukuran |
|---|---|---|
| G1 | Pengguna mengetahui posisi realisasi dalam < 15 detik | Halaman Ringkasan menampilkan pagu total, paket terealisasi, dan cakupan % tanpa scroll |
| G2 | Setiap KPI dapat dirinci ke baris penyusunnya | 100% kartu KPI memiliki tautan ke daftar terfilter; jumlah baris daftar = angka KPI |
| G3 | Paket belum terealisasi dapat ditemukan dan dihitung | Daftar paket dengan filter "belum realisasi" menampilkan 12.361 baris untuk D462/2026 |
| G4 | Rantai warisan RUP dapat ditelusuri | Drill-down menampilkan `dari_rup` dan `depth` hingga akar rantai |
| G5 | Kesegaran data selalu terlihat | Setiap halaman menampilkan "terakhir diperbarui" dari `pull_log`; peringatan bila > ambang |
| G6 | Cakupan KLPD tidak menyesatkan | Banner cakupan tampil di semua halaman; daftar KLPD berstatus data |
| G7 | Halaman daftar responsif pada volume penuh | p95 ≤ 1.500 ms pada 348.447 baris; KPI dari materialized view ≤ 300 ms |
| G8 | Rekap dapat dibagikan | Ekspor CSV/Excel mengikuti filter aktif, ≤ 50.000 baris, streaming |
| G9 | Tidak ada kredensial yang tampil | Tidak ada token utuh pada HTML, JSON, log, maupun berkas ekspor |

### 2.1 Angka Acuan (Wajib Lolos)

Angka berikut adalah kondisi basis data `inaproc` untuk **D462 / 2026** pada saat dokumen ini ditulis. Setiap implementasi wajib menghasilkan angka identik pada data yang sama; selisih harus dapat dijelaskan.

| Metrik | Nilai acuan |
|---|---:|
| Paket RUP penyedia (`rec_rup_paket_penyedia`) | 16.024 |
| Total pagu paket penyedia | Rp6.362.688.994.450 |
| Pagu rata-rata | Rp397.518.993 |
| Pagu maksimum | Rp1.500.000.000.000 |
| Kode RUP terealisasi (`v_realisasi_rup` distinct `kd_rup`) | 3.663 |
| **Paket belum terealisasi** | **12.361 (77,1%)** |
| Baris `v_realisasi_rup` | 3.665 |
| `ref_rup_proses` | 3.375 |
| `ref_rup_warisan` | 290 |
| KLPD terdaftar / data lengkap | 20 / 1 (D462) |

Rincian realisasi per sumber:

| Sumber | Langsung | Warisan | Pagu langsung | Pagu warisan |
|---|---:|---:|---:|---:|
| e-katalog | 1.653 | 222 | 1.040.634.896.717 | 91.240.993.325 |
| non-tender | 1.625 | 61 | 203.925.186.682 | 11.560.936.676 |
| pencatatan-non-tender | 7 | 0 | 302.846.100 | 0 |
| tender | 90 | 7 | 70.887.782.870 | 2.951.386.600 |

---

## 3. Ruang Lingkup

### 3.1 Termasuk

- Autentikasi sesi + otorisasi tiga peran (Admin, Operator, Viewer)
- Delapan modul: Ringkasan Eksekutif, Realisasi RUP, Paket & Serapan, Tender & Non-Tender, E-Katalog, Satker, Kualitas Data, Pengaturan
- Global filter KLPD + tahun yang memengaruhi seluruh modul
- Materialized view untuk agregasi berat
- Ekspor CSV/Excel sesuai filter aktif
- Indikator kesegaran data dan banner cakupan

### 3.2 Tidak Termasuk

| Item | Alasan |
|---|---|
| Menjalankan tarik data dari UI | Operasi tulis ke pipeline; tetap wewenang CLI (PRD pipeline F12) |
| Mengubah/menghapus baris `rec_*` | Dashboard read-only, ditegakkan di level hak akses PostgreSQL |
| Manajemen token KLPD | Kredensial sensitif; cukup ditampilkan statusnya (valid/tidak) |
| Prediksi/forecasting realisasi | Belum ada dasar model; data historis baru 1 tahun anggaran |
| Notifikasi email/WhatsApp | Status tersedia secara pull; kebutuhan belum terbukti |
| Aplikasi mobile native | Web responsif sudah memadai untuk operator desktop |
| Rekonsiliasi otomatis vs dataset acuan | Perbandingan tetap manual (PRD pipeline Bab 12) |
| Multi-tenant terisolasi penuh | Satu instansi pengelola, banyak KLPD sebagai dimensi data |

---

## 4. Pengguna & Peran

| Peran | Kebutuhan Utama | Hak |
|---|---|---|
| **Pimpinan / PPK** | Melihat posisi serapan, membandingkan satker | Viewer: baca semua halaman, ekspor |
| **Operator Pengadaan** | Menelusuri paket belum terealisasi, memeriksa tender | Operator: Viewer + akses Kualitas Data |
| **Admin Data** | Menjaga kesegaran data, mengelola pengguna | Admin: semua + Pengaturan & pengguna |
| **Pipeline (sistem)** | Tidak mengakses dashboard | — |

Pembatasan yang berlaku untuk semua peran: tidak ada akses tulis ke database pipeline, tidak ada tampilan token utuh, tidak ada perubahan pada tabel `rec_*`.

---

## 5. Arsitektur

### 5.1 Stack Teknologi

| Komponen | Pilihan | Peran |
|---|---|---|
| Framework | Laravel 13 | Routing, otorisasi, Blade, ekspor |
| Bahasa | PHP 8.4 | Runtime (sesuai server) |
| Interaktivitas | Livewire 3 | Filter, pencarian, paginasi tanpa menulis JavaScript manual |
| Styling | Tailwind CSS | Utilitas, tema terang/gelap |
| Grafik | ApexCharts | Donat, batang, garis; data dikirim sebagai JSON dari server |
| Basis data baca | PostgreSQL 18 (`inaproc`) | Sumber seluruh data analitik |
| Basis data tulis | PostgreSQL 18 (`inaproc_dashboard`) | Users, sesi, filter tersimpan, audit |

#### 5.1.1 Keputusan Arsitektur

| Opsi | Keputusan | Alasan |
|---|---|---|
| **Blade + Livewire** | **Dipilih** | Satu bahasa (PHP) dengan pipeline; tidak perlu Node di server produksi; paginasi & filter server-side bawaan; mudah dirawat oleh tim yang sama |
| Inertia + Vue | Ditolak | Menambah build frontend dan state ganda untuk kebutuhan yang seluruhnya server-side |
| Next.js terpisah | Ditolak | Dua deployment, dua bahasa, dua pipeline CI untuk satu aplikasi baca |
| Grafik berbasis `div` murni | Ditolak | Tidak memadai untuk donat komposisi dan tren bulanan; ApexCharts ringan (~130 KB) dan tidak butuh framework |

### 5.2 Keputusan Basis Data: Dua Koneksi

Dashboard memakai **dua koneksi terpisah**, dan ini bukan pilihan gaya melainkan syarat keamanan.

| Koneksi | Nama di `config/database.php` | Basis data | User | Hak |
|---|---|---|---|---|
| Baca | `inaproc` | `inaproc` | `dash_ro` | `CONNECT`, `USAGE` schema, `SELECT` semua tabel & view |
| Tulis | `dashboard` (default) | `inaproc_dashboard` | `dash_app` | Penuh pada database sendiri |

**Aturan wajib:**

- User `dash_ro` **dibuat khusus** dan **dilarang** memakai `sipedal` (pemilik tabel pipeline).
- `dash_ro` tidak memiliki `INSERT`/`UPDATE`/`DELETE` pada schema `public` database `inaproc`.
- Koneksi `inaproc` disetel `'search_path' => 'public'` dan tidak dipakai untuk migration.
- Migration hanya berjalan pada koneksi `dashboard`.

**Konsekuensi teknis:** PostgreSQL tidak mendukung join antar-database. Karena itu:

| Kebutuhan | Penanganan |
|---|---|
| Data analitik | Seluruhnya di koneksi `inaproc` |
| Data milik dashboard (user, filter, audit) | Di koneksi `dashboard` |
| Join antara keduanya | Dilakukan di level aplikasi (dua query, digabung di PHP), **bukan** `dblink`/`postgres_fdw` |
| Foreign key lintas database | Tidak ada. Integritas dijaga aplikasi |

Contoh nyata: "filter tersimpan milik user X" menyimpan `klpd` dan `tahun` sebagai nilai biasa; ketika dibuka, aplikasi memakai nilai itu untuk query ke koneksi `inaproc`.

### 5.3 Struktur Direktori

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── ExecutiveController.php        # D1
│   │   ├── RealisasiController.php        # D2
│   │   ├── PaketController.php            # D3
│   │   ├── TenderController.php           # D4
│   │   ├── EkatalogController.php         # D5
│   │   ├── SatkerController.php           # D6
│   │   ├── QualityController.php          # D7
│   │   ├── SettingController.php          # D8
│   │   └── ExportController.php
│   └── Middleware/EnsureRole.php
├── Livewire/
│   ├── GlobalFilter.php                   # KLPD + tahun
│   ├── KpiCard.php
│   ├── RealisasiTable.php
│   ├── PaketTable.php
│   ├── TenderTable.php
│   ├── EkatalogTable.php
│   ├── SatkerTable.php
│   └── PullLogTable.php
├── Services/
│   ├── Analytics/
│   │   ├── KpiService.php                 # baca mv_dash_*
│   │   ├── RealisasiQuery.php
│   │   ├── PaketQuery.php
│   │   ├── TenderQuery.php
│   │   ├── EkatalogQuery.php
│   │   ├── SatkerQuery.php
│   │   └── CoverageService.php            # cakupan KLPD & kesegaran
│   ├── Format/
│   │   ├── Rupiah.php
│   │   └── Tanggal.php
│   └── Export/CsvStreamer.php
├── Models/                                # hanya model koneksi `dashboard`
│   ├── User.php
│   ├── SavedFilter.php
│   └── ActivityLog.php
└── View/Components/
    ├── KpiCard.php
    ├── CoverageBanner.php
    └── FreshnessBadge.php
database/
├── migrations/                            # koneksi `dashboard` saja
└── sql/
    ├── 01_role_dash_ro.sql
    ├── 02_materialized_views.sql
    └── 03_indexes.sql
resources/views/
├── layouts/app.blade.php
├── executive/index.blade.php
├── realisasi/index.blade.php
├── paket/index.blade.php
├── tender/index.blade.php
├── ekatalog/index.blade.php
├── satker/{index,show}.blade.php
├── quality/index.blade.php
└── setting/index.blade.php
```

### 5.4 Alur Data

```
Pipeline (CLI)                    Dashboard (web)
     │                                 │
     │ tulis rec_* / ref_*             │ baca saja
     ▼                                 ▼
┌──────────────────────┐      ┌────────────────────┐
│ DB inaproc           │◄─────│ koneksi dash_ro    │
│ 55 tabel rec_*       │ SELECT│ (read-only)       │
│ ref_rup_proses       │      └─────────┬──────────┘
│ ref_rup_warisan      │                │
│ v_realisasi_rup      │                ▼
│ pull_log, endpoints  │      ┌────────────────────┐
│ mv_dash_*  ◄─────────┼──────│ Livewire + Blade   │
└──────────────────────┘      └─────────┬──────────┘
     ▲                                   │ user & audit
     │ REFRESH MATERIALIZED VIEW         ▼
     │ (setelah tarik selesai)  ┌────────────────────┐
     └──────────────────────────│ DB inaproc_dashboard│
                                │ users, sessions     │
                                │ saved_filters       │
                                │ activity_log        │
                                └────────────────────┘
```

### 5.5 Konsekuensi Skema yang Harus Dihadapi

Kolom sistem pada tabel `rec_*` **tidak persis** seperti spesifikasi pipeline. Dashboard wajib menyesuaikan diri:

| Kolom | Spesifikasi PRD pipeline | Kenyataan di `inaproc` | Penanganan di dashboard |
|---|---|---|---|
| `_kode_klpd` | `varchar(16)` | `text` | Cast eksplisit saat filter; tidak diandalkan untuk indeks unik |
| `_tahun` | `smallint` | `bigint` | Cast ke `int` di query |
| `_fetched_at` | `timestamptz` | **`text`** | **Dilarang** membandingkan langsung. Cast: `_fetched_at::timestamptz` |
| `kd_rup` (paket) | `numeric` | `bigint` | Normalisasi ke `text` saat join ke `ref_rup_*` |
| `jenis_pengadaan` | `text` | `text`, dapat multi-nilai (`Barang;Jasa Lainnya`) | Pecah dengan `string_to_array` saat agregasi |

> **Utang teknis yang tercatat:** `_fetched_at` bertipe `text` adalah penyimpangan dari PRD pipeline F6.2. Dashboard tidak memperbaikinya (di luar lingkup) tetapi menambahkan pemeriksaan pada halaman Kualitas Data agar penyimpangan ini terlihat dan dapat dijadwalkan perbaikannya.

---

## 6. Kebutuhan Fungsional

### D0 — Kerangka Umum (berlaku untuk semua modul)

#### D0.1 Global Filter

Filter **KLPD** dan **Tahun Anggaran** berada di header, melekat pada semua halaman.

| Aspek | Ketentuan |
|---|---|
| Sumber daftar KLPD | `klpd_tokens.json` (label instansi) digabung `pull_log` (KLPD yang punya data) |
| Default KLPD | KLPD dengan jumlah baris terbanyak |
| Default tahun | Tahun anggaran terbesar yang ada di `pull_log` untuk KLPD terpilih |
| Penyimpanan keadaan | Query string: `?klpd=D462&tahun=2026` |
| Perilaku | Perubahan filter memuat ulang halaman; filter dipertahankan saat berpindah modul |
| Filter kosong | Bila KLPD tidak punya data: tampilkan keadaan kosong yang menjelaskan sebabnya, bukan halaman error |

#### D0.2 Banner Cakupan Data

Komponen `CoverageBanner` tampil di atas konten setiap halaman.

| Kondisi | Pesan |
|---|---|
| KLPD terpilih punya data lengkap | Hijau: "Data D462 · 2026 · 55 tabel · terakhir diperbarui 22/10/2026 18:56" |
| KLPD terpilih data sebagian | Kuning: "Data D463 belum lengkap — hanya 3 endpoint tertarik" |
| KLPD terpilih tanpa data | Abu: "Belum ada data untuk KLPD ini. Jalankan `inaproc:pull --klpd=...`" |
| Data lebih tua dari ambang | Merah: "Data berumur 3 hari. Angka mungkin sudah berubah." |

Ambang kesegaran: 48 jam (dapat dikonfigurasi `DASH_FRESHNESS_HOURS`).

#### D0.3 Format Angka & Tanggal

| Jenis | Format | Contoh |
|---|---|---|
| Rupiah penuh | `Rp` + pemisah ribuan titik | `Rp6.362.688.994.450` |
| Rupiah ringkas | Satuan T/M/Jt | `Rp6,36 T`, `Rp203,93 M`, `Rp302,85 Jt` |
| Persentase | Satu desimal, koma | `77,1%` |
| Tanggal | `dd/mm/yyyy` | `22/10/2026` |
| Tanggal + waktu | `dd/mm/yyyy HH:mm` | `22/10/2026 18:56` |
| Zona waktu | `Asia/Jakarta` | semua tampilan |
| Angka besar | Pemisah titik, bukan koma | `16.024` |

#### D0.4 Paginasi, Urutan, Pencarian

| Aspek | Ketentuan |
|---|---|
| Lokasi | Server-side (Livewire `WithPagination`) |
| Ukuran halaman | 25 / 50 / 100, default 50 |
| Urutan | Klik header kolom; dua arah; hanya pada kolom yang diizinkan per modul |
| Pencarian | `ILIKE '%kata%'` pada kolom teks terpilih; `statement_timeout` 5.000 ms |
| Batas aman | Bila hasil > 100.000 baris, tampilkan saran mempersempit filter alih-alih memuat |
| Keadaan URL | Halaman, urutan, pencarian, dan filter tercermin di query string |

**Penerimaan:**
- [ ] Filter KLPD/tahun bertahan saat berpindah modul
- [ ] Setiap keadaan layar dapat di-bookmark dan menghasilkan tampilan identik
- [ ] Banner cakupan tampil pada 100% halaman
- [ ] Format rupiah, persen, dan tanggal sesuai tabel D0.3
- [ ] Paginasi tidak pernah memuat seluruh tabel ke memori

---

### D1 — Ringkasan Eksekutif

Halaman pertama setelah login. Menjawab "bagaimana posisi kita" tanpa perlu klik.

#### D1.1 Kartu KPI

| Kartu | Nilai | Sumber |
|---|---|---|
| Total Pagu RUP | Rp6.362.688.994.450 | `mv_dash_kpi.pagu_total` |
| Jumlah Paket | 16.024 | `mv_dash_kpi.paket_total` |
| Paket Terealisasi | 3.663 | `mv_dash_kpi.paket_terealisasi` |
| **Paket Belum Terealisasi** | **12.361 (77,1%)** | turunan |
| Nilai Realisasi | jumlah pagu `v_realisasi_rup` | `mv_dash_kpi.realisasi_pagu` |
| Rata-rata Pagu per Paket | Rp397.518.993 | turunan |

Setiap kartu adalah tautan ke modul terkait dengan filter yang sudah terpasang (G2).

#### D1.2 Grafik

| Grafik | Tipe | Data |
|---|---|---|
| Komposisi realisasi per sumber | Donat | 4 sumber × (langsung, warisan) dari `mv_dash_sumber` |
| Top 5 satker | Batang horizontal | `mv_dash_satker` urut jumlah realisasi |
| Tren paket per bulan | Garis | `rec_rup_paket_penyedia` group by `date_trunc('month', tgl_buat_paket::date)` |
| Pagu per metode pengadaan | Batang | `rec_rup_paket_penyedia` group by `metode_pengadaan` |

Angka acuan grafik donat: e-katalog 1.875, non-tender 1.686, pencatatan-non-tender 7, tender 97 (langsung + warisan).

Angka acuan top 5 satker: DINAS SUMBER DAYA AIR DAN BINA KONSTRUKSI 519; DINAS PERKEBUNAN, TANAMAN PANGAN DAN HOLTIKULTURA 470; DINAS PENDIDIKAN 415; DINAS PERUMAHAN RAKYAT, KAWASAN PERMUKIMAN DAN PERTANAHAN 286; DINAS BINA MARGA, CIPTA KARYA DAN TATA RUANG 241.

Angka acuan pagu per metode: E-Purchasing 7.402; Pengadaan Langsung 7.035; Dikecualikan 1.282; Seleksi 132; Tender 127; Penunjukan Langsung 46.

#### D1.3 Panel Status Data

| Item | Isi |
|---|---|
| Kesegaran | `max(_fetched_at::timestamptz)` per grup tabel |
| Tarikan terakhir | 5 baris terakhir `pull_log` (endpoint, status, rows, waktu) |
| Kegagalan 7 hari | Jumlah `GAGAL` + `FORBIDDEN` per KLPD |

**Penerimaan:**
- [ ] Enam kartu KPI menampilkan angka acuan Bab 2.1 tanpa selisih
- [ ] Klik kartu "Paket Belum Terealisasi" membuka D3 dengan filter `belum` dan jumlah baris = 12.361
- [ ] Donat komposisi sumber menampilkan 4 irisan dengan total 3.665
- [ ] Tren bulanan tidak error saat `tgl_buat_paket` kosong
- [ ] Halaman selesai dimuat ≤ 1.500 ms (p95)

---

### D2 — Realisasi RUP

Modul inti. Menjawab "paket mana yang sudah dipakai, lewat jalur apa".

#### D2.1 Sumber Data

View `v_realisasi_rup` (koneksi `inaproc`), kolom: `kd_rup`, `nama_paket`, `pagu`, `kd_satker`, `nama_satker`, `metode_pengadaan`, `tahun_anggaran`, `sumber`, `jenis`, `dari_rup`, `depth`.

#### D2.2 Daftar

| Kolom | Isi | Urut | Cari |
|---|---|---|---|
| `kd_rup` | kode RUP | ✔ | ✔ |
| `nama_paket` | nama paket | ✔ | ✔ |
| `pagu` | rupiah penuh + ringkas | ✔ | — |
| `nama_satker` | satker | ✔ | ✔ |
| `metode_pengadaan` | teks | ✔ | — |
| `sumber` | badge berwarna | ✔ | — |
| `jenis` | badge: langsung / warisan | ✔ | — |
| `depth` | angka (0 untuk langsung) | ✔ | — |

Filter: `sumber` (multi), `jenis`, `satker`, rentang `pagu` (min/max), `tahun_anggaran`.

#### D2.3 Drill-down Rantai Warisan

Untuk baris dengan `jenis = warisan`, tampilkan panel berisi rantai:

```
kd_rup 67813872 (depth 6)
  ← dari 66651297
  ← ... hingga akar rantai yang punya proses sendiri
```

Rantai diambil dari `ref_rup_warisan` dengan penelusuran berulang `dari_rup` (maksimum 30 level, sesuai PRD pipeline B7). Bila `dari_rup` tidak ditemukan di tabel, tampilkan peringatan "rantai tidak lengkap".

#### D2.4 Agregasi Ringkas

Di atas tabel, tampilkan tiga angka untuk filter aktif: jumlah baris, total pagu, jumlah `kd_rup` unik.

**Penerimaan:**
- [ ] Tanpa filter, jumlah baris = 3.665 untuk D462/2026
- [ ] Filter `sumber=e-katalog` → 1.875 baris; `jenis=langsung` → 1.653
- [ ] Filter `sumber=non-tender` + `jenis=warisan` → 61 baris
- [ ] Drill-down rantai menampilkan `dari_rup` dan `depth` sesuai data
- [ ] Rantai yang putus menampilkan peringatan, bukan halaman error
- [ ] Angka agregat di atas tabel berubah mengikuti filter

---

### D3 — Paket RUP & Serapan

Menjawab "apa yang belum terpakai" — modul dengan nilai operasional tertinggi.

#### D3.1 Sumber Data

`rec_rup_paket_penyedia` (koneksi `inaproc`), di-LEFT JOIN ke himpunan `kd_rup` dari `v_realisasi_rup` untuk menentukan status.

#### D3.2 Kolom Daftar

| Kolom | Isi |
|---|---|
| `kd_rup` | kode RUP |
| `nama_paket` | nama |
| `pagu` | rupiah |
| `metode_pengadaan` | teks |
| `jenis_pengadaan` | teks (bila multi-nilai, tampilkan semua sebagai chip) |
| `nama_satker` | satker |
| `tgl_buat_paket` | tanggal |
| **Status** | badge `Terealisasi` / `Belum` |

#### D3.3 Filter Kunci

| Filter | Nilai |
|---|---|
| Status realisasi | Semua / Terealisasi / Belum |
| Metode pengadaan | E-Purchasing, Pengadaan Langsung, Dikecualikan, Seleksi, Tender, Penunjukan Langsung |
| Jenis pengadaan | Barang, Jasa Lainnya, Pekerjaan Konstruksi, Jasa Konsultansi |
| Rentang pagu | min/max |
| Satker | pilih dari `rec_rup_master_satker` |

#### D3.4 Agregasi

Panel ringkas: jumlah paket, total pagu, pagu terealisasi, pagu belum, persentase cakupan.

**Penerimaan:**
- [ ] Filter `Belum` → 12.361 baris, total pagu sesuai selisih acuan
- [ ] Filter `metode_pengadaan=E-Purchasing` → 7.402 baris
- [ ] Nilai `jenis_pengadaan` berisi `;` ditampilkan sebagai dua chip, bukan teks mentah
- [ ] Persentase cakupan = 3.663 / 16.024 = 22,9%
- [ ] Klik baris membuka detail paket (D3.5)

#### D3.5 Detail Paket

Panel samping berisi seluruh kolom `rec_rup_paket_penyedia` untuk satu `kd_rup`, ditambah:

- Daftar proses pengadaan yang merujuk kode ini (dari `v_realisasi_rup`, semua sumber)
- Riwayat kaji ulang dari `rec_rup_history_kaji_ulang` (`kd_rup_lama`/`kd_rup_baru`, `jenis_revisi`, `alasan_kajiulang`)
- Tautan ke D4/D5 sesuai sumber proses

---

### D4 — Tender & Non-Tender

#### D4.1 Dua Tab

| Tab | Tabel | Kode |
|---|---|---|
| Tender | `rec_tender_pengumuman` | `kd_tender` |
| Non-Tender | `rec_tender_non_tender_pengumuman` | `kd_nontender` |

#### D4.2 Kolom

`nama_paket`, `pagu`, `hps`, selisih pagu−HPS, `status_tender`/`status_nontender`, `mtd_pemilihan`, `jenis_pengadaan`, `nama_satker`, `tgl_pengumuman_tender`/`tgl_pengumuman_nontender`, `url_lpse`.

#### D4.3 Stepper Status

Status ditampilkan sebagai tahapan, bukan sekadar teks:

```
Berlangsung → Selesai
           ↘ Gagal/Batal
```

Sebaran acuan — tender: Selesai 74, Gagal/Batal 19, Berlangsung 1. Non-tender: Selesai 1.400, Gagal/Batal 232, Berlangsung 49.

#### D4.4 Detail

- Perbandingan pagu vs HPS dalam bentuk batang (indikator efisiensi)
- Peserta: `rec_tender_peserta_tender` (kolom sesuai skema aktual)
- Jadwal tahapan: `rec_tender_jadwal_tahapan_tender` / `rec_tender_jadwal_tahapan_non_tender`
- RUP terkait: `kd_rup` dapat berisi beberapa kode dipisah `;` → dipecah dan ditautkan ke D3

**Penerimaan:**
- [ ] Tab Tender menampilkan 94 baris; Non-Tender 1.681 baris untuk D462/2026
- [ ] Stepper mencerminkan status dengan warna berbeda
- [ ] `kd_rup` multi-nilai ditampilkan sebagai beberapa tautan, bukan satu teks panjang
- [ ] Selisih pagu−HPS dihitung benar, termasuk saat HPS kosong (tampil "—")

---

### D5 — E-Katalog

#### D5.1 Sumber Data

`rec_ekatalog_paket_e_purchasing` (5.770 baris), kolom kunci: `order_id`, `rup_code`, `rup_name`, `kode_penyedia`, `nama_satker`, `status`, `total`, `total_qty`, `count_product`, `order_date`, `funding_source`, `mak`, `shipment_status`.

#### D5.2 Sebaran Status

| Status | Jumlah acuan |
|---|---:|
| ON_PROCESS | 3.575 |
| CANCELLED_ON_NEGOTIATION | 671 |
| COMPLETED | 602 |
| PAYMENT_OUTSIDE_SYSTEM | 512 |
| CANCELLED_ON_REVIEW | 196 |
| CANCELLED | 121 |
| ON_NEGOTIATION | 43 |
| WAITING_PPK_REVIEW | 41 |

Ditampilkan sebagai batang bertumpuk dengan label status yang diterjemahkan ke Bahasa Indonesia, nilai asli tetap terlihat pada tooltip.

#### D5.3 Analisis

| Panel | Isi |
|---|---|
| Top penyedia | `kode_penyedia` dengan jumlah transaksi dan nilai terbesar |
| Nilai transaksi | `sum(total)` per bulan dari `order_date` |
| Tautan ke RUP | `rup_code` → D3 detail |
| Produk | `rec_ekatalog_e_purchasing_by_produk` (2.430 baris) untuk rincian item |

**Penerimaan:**
- [ ] Total 8 status = 5.770 baris (tidak ada status yang hilang dari tampilan)
- [ ] Klik status memfilter daftar ke status tersebut
- [ ] Top penyedia menampilkan minimal 10 baris, urut nilai
- [ ] `order_date` kosong tidak menyebabkan error pengurutan

---

### D6 — Satker

#### D6.1 Daftar Satker

Sumber: `rec_rup_master_satker` (669 baris) digabung agregat dari `rec_rup_paket_penyedia` dan `v_realisasi_rup`.

| Kolom | Isi |
|---|---|
| `nama_satker` | nama |
| `jenis_satker` | jenis |
| Jumlah paket | hitung dari paket |
| Total pagu | jumlah pagu paket |
| Nilai realisasi | jumlah pagu realisasi |
| Rasio serapan | realisasi / pagu |

#### D6.2 Detail Satker

- Ringkasan pagu & realisasi
- Daftar paket satker (menuju D3)
- Daftar realisasi satker (menuju D2)
- Perbandingan dengan rata-rata KLPD

**Penerimaan:**
- [ ] Daftar menampilkan 669 satker
- [ ] Top 5 satker berdasarkan jumlah realisasi sesuai angka acuan Bab 2.1
- [ ] Rasio serapan dihitung aman saat pagu = 0 (tampil "—", bukan pembagian nol)
- [ ] Halaman detail dapat dibuka untuk satker tanpa paket (keadaan kosong jelas)

---

### D7 — Kualitas & Operasional Data

Modul pembuktian: memungkinkan pengguna menilai apakah angka D1–D6 layak dipercaya.

#### D7.1 Status Tarikan

Tabel `pull_log` dengan filter endpoint, KLPD, tahun, status, dan rentang waktu.

| Kolom | Isi |
|---|---|
| Waktu | `created_at` |
| Endpoint | `endpoint` |
| Grup / API | `grup`, `api` |
| Status | badge `OK`/`KOSONG`/`GAGAL`/`FORBIDDEN`/`NONAKTIF` |
| Baris | `rows` |
| Halaman | `pages` |
| Durasi | `durasi_ms` diformat (`340 ms`, `1,2 s`) |
| Pesan | `pesan`, dipotong dengan tooltip penuh |

Ringkasan atas: jumlah per status untuk filter aktif.

#### D7.2 Kesegaran Data

| Item | Isi |
|---|---|
| Kesegaran per grup | `max(_fetched_at::timestamptz)` untuk tabel pada grup rup/tender/ekatalog/master |
| Tabel paling basi | 10 tabel teratas berdasarkan jarak waktu |
| Ambang | Peringatan bila > `DASH_FRESHNESS_HOURS` |

**Penting:** `_fetched_at` bertipe `text`. Query wajib memakai cast eksplisit `_fetched_at::timestamptz` dan menangani nilai yang gagal dikonversi.

#### D7.3 Cakupan Endpoint

Matriks `endpoints` (65 baris, 61 aktif) × KLPD:

| Status sel | Arti |
|---|---|
| ✔ + jumlah baris | Tabel ada dan berisi |
| ○ 0 | Tabel ada, kosong |
| ✘ | Tabel belum pernah dibuat |

#### D7.4 Penyimpangan Skema

Membandingkan `information_schema.columns` dengan yang diharapkan PRD pipeline:

| Pemeriksaan | Kondisi normal |
|---|---|
| `_kode_klpd` | `varchar(16)` |
| `_tahun` | `smallint` |
| `_fetched_at` | `timestamptz` |
| Indeks `(_kode_klpd, _tahun)` | ada |

Penyimpangan ditampilkan sebagai daftar temuan, bukan kegagalan halaman. Ini yang menampilkan utang teknis `_fetched_at text`.

**Penerimaan:**
- [ ] Daftar `pull_log` menampilkan 33 baris historis
- [ ] Filter status `GAGAL` menampilkan baris dengan pesan alasan
- [ ] Kesegaran memakai cast `::timestamptz` dan tidak error pada nilai kosong
- [ ] Matriks cakupan menampilkan 61 baris endpoint aktif
- [ ] Pemeriksaan skema mendeteksi `_fetched_at` sebagai `text` dan melaporkannya
- [ ] Halaman tetap tampil walau tabel `rec_*` tertentu belum ada

---

### D8 — Pengaturan

| Bagian | Isi | Peran |
|---|---|---|
| Profil | Nama, email, kata sandi | Semua |
| Filter tersimpan | Simpan kombinasi KLPD+tahun+filter, beri nama | Operator, Admin |
| Pengguna | CRUD pengguna, penetapan peran | Admin |
| Tampilan | Tema terang/gelap, ukuran halaman default | Semua |
| Audit | Daftar ekspor dan perubahan pengguna | Admin |

**Penerimaan:**
- [ ] Viewer tidak dapat membuka halaman Pengguna (403)
- [ ] Filter tersimpan memuat kembali keadaan layar persis
- [ ] Perubahan peran tercatat di `activity_log`
- [ ] Kata sandi disimpan dengan hash, tidak pernah ditampilkan kembali

---

### D9 — Ekspor

| Aspek | Ketentuan |
|---|---|
| Format | CSV (UTF-8, pemisah `;` untuk Excel Indonesia) dan XLSX |
| Cakupan | Mengikuti filter aktif, bukan seluruh tabel |
| Batas | 50.000 baris; lebih dari itu ditolak dengan saran mempersempit |
| Cara | Streaming (`cursor` + chunk 1.000), tidak memuat seluruh hasil ke memori |
| Nama berkas | `<modul>_<klpd>_<tahun>_<tanggal>.csv` |
| Audit | Setiap ekspor dicatat di `activity_log` (user, modul, filter, jumlah baris) |
| Angka | Mentah (tanpa pemisah ribuan) agar dapat diolah |

**Penerimaan:**
- [ ] Ekspor D2 dengan filter `sumber=e-katalog` menghasilkan 1.875 baris data + 1 header
- [ ] Berkas 50.000 baris selesai diunduh dengan memori ≤ 128 MB
- [ ] Ekspor > 50.000 baris ditolak dengan pesan jelas
- [ ] Setiap ekspor meninggalkan satu baris `activity_log`

---

## 7. Kebutuhan Non-Fungsional

| Aspek | Target |
|---|---|
| Waktu muat halaman daftar | p95 ≤ 1.500 ms pada 348.447 baris |
| Waktu muat KPI | ≤ 300 ms (dari materialized view) |
| Waktu muat halaman pertama tabel besar | ≤ 800 ms |
| Memori proses ekspor | ≤ 128 MB untuk 50.000 baris |
| Pengguna bersamaan | 20 tanpa degradasi berarti |
| Keamanan tulis | Tidak ada jalur tulis ke DB `inaproc`; ditegakkan hak akses PostgreSQL |
| Kerahasiaan | Token tidak pernah dirender; `dash_ro` tidak punya akses ke `klpd_credentials` |
| Audit | Semua ekspor dan perubahan pengguna tercatat |
| Aksesibilitas | Kontras WCAG AA (≥ 4,5:1), navigasi keyboard penuh, tabel punya `<caption>` |
| Responsif | Terbaca pada 1.280 px; tabel menggulir horizontal, bukan terpotong |
| Zona waktu | Seluruh tampilan `Asia/Jakarta` |
| Kompatibilitas | Chrome/Edge/Firefox dua versi terakhir |

---

## 8. Model Data & Kontrak Query

### 8.1 Materialized View (koneksi `inaproc`)

Agregasi berat **tidak** dihitung per request. Empat MV berikut dibuat di database `inaproc` oleh user pemilik (bukan `dash_ro`), lalu dibaca dashboard.

#### `mv_dash_kpi`

```sql
CREATE MATERIALIZED VIEW mv_dash_kpi AS
WITH paket AS (
    SELECT _kode_klpd, _tahun,
           count(*) AS paket_total,
           sum(pagu) AS pagu_total,
           avg(pagu) AS pagu_rata,
           max(pagu) AS pagu_maks
      FROM rec_rup_paket_penyedia
     GROUP BY 1, 2
), realisasi AS (
    SELECT p._kode_klpd, p._tahun,
           count(DISTINCT round(v.kd_rup::numeric)) AS paket_terealisasi,
           sum(v.pagu) AS realisasi_pagu
      FROM rec_rup_paket_penyedia p
      JOIN v_realisasi_rup v
        ON v.kd_rup = round(p.kd_rup::numeric)::bigint::text
     GROUP BY 1, 2
)
SELECT paket._kode_klpd, paket._tahun,
       paket.paket_total, paket.pagu_total, paket.pagu_rata, paket.pagu_maks,
       coalesce(realisasi.paket_terealisasi, 0) AS paket_terealisasi,
       coalesce(realisasi.realisasi_pagu, 0)    AS realisasi_pagu,
       paket.paket_total - coalesce(realisasi.paket_terealisasi, 0) AS paket_belum
  FROM paket LEFT JOIN realisasi USING (_kode_klpd, _tahun);
```

#### `mv_dash_sumber`

> **Perhatian:** `v_realisasi_rup` **tidak memiliki** `_kode_klpd`. Kolom yang tersedia hanya `tahun_anggaran` (lihat 8.2). Karena itu KLPD harus diambil lewat join ke `rec_rup_paket_penyedia`. Ini sudah diuji pada data nyata dan menghasilkan 3.665 baris utuh — tidak ada baris view yang kehilangan pasangan.

```sql
CREATE MATERIALIZED VIEW mv_dash_sumber AS
SELECT p._kode_klpd,
       v.tahun_anggaran AS _tahun,
       v.sumber, v.jenis,
       count(*)    AS baris,
       sum(v.pagu) AS pagu
  FROM v_realisasi_rup v
  LEFT JOIN (
        SELECT DISTINCT round(kd_rup::numeric)::bigint::text AS k, _kode_klpd
          FROM rec_rup_paket_penyedia
       ) p ON p.k = v.kd_rup
 GROUP BY 1, 2, 3, 4;
```

#### `mv_dash_satker`

```sql
CREATE MATERIALIZED VIEW mv_dash_satker AS
SELECT p._kode_klpd, p._tahun, p.nama_satker,
       count(*) AS paket,
       sum(p.pagu) AS pagu,
       count(DISTINCT round(v.kd_rup::numeric)) AS terealisasi,
       sum(v.pagu) AS realisasi_pagu
  FROM rec_rup_paket_penyedia p
  LEFT JOIN v_realisasi_rup v
    ON v.kd_rup = round(p.kd_rup::numeric)::bigint::text
 GROUP BY 1, 2, 3;
```

#### `mv_dash_status`

```sql
CREATE MATERIALIZED VIEW mv_dash_status AS
SELECT 'tender' AS jenis, _kode_klpd, _tahun, status_tender AS status, count(*) AS baris, sum(pagu) AS pagu
  FROM rec_tender_pengumuman GROUP BY 1,2,3,4
UNION ALL
SELECT 'non-tender', _kode_klpd, _tahun, status_nontender, count(*), sum(pagu)
  FROM rec_tender_non_tender_pengumuman GROUP BY 1,2,3,4
UNION ALL
SELECT 'e-katalog', _kode_klpd, _tahun, status, count(*), sum(total)
  FROM rec_ekatalog_paket_e_purchasing GROUP BY 1,2,3,4;
```

### 8.2 Kolom `v_realisasi_rup` (hasil pemeriksaan langsung)

| Kolom | Tipe | Catatan |
|---|---|---|
| `kd_rup` | `text` | hasil `round(...)::bigint::text` |
| `nama_paket` | `text` | — |
| `pagu` | `double precision` | bukan `numeric`; waspadai pembulatan saat `sum()` besar |
| `kd_satker` | `bigint` | — |
| `nama_satker` | `text` | — |
| `metode_pengadaan` | `text` | — |
| `tahun_anggaran` | `bigint` | **satu-satunya kolom waktu** |
| `sumber` | `text` | `tender` / `non-tender` / `pencatatan-non-tender` / `e-katalog` |
| `jenis` | `text` | `langsung` / `warisan` |
| `dari_rup` | `text` | `NULL` bila langsung |
| `depth` | `bigint` | 0 untuk langsung |

**Tidak ada** `_kode_klpd`, `_row_id`, `_source`, maupun `_fetched_at` pada view ini. Setiap kebutuhan filter per KLPD pada modul D2 harus melalui join ke `rec_rup_paket_penyedia`, seperti pada `mv_dash_sumber`. Alternatif yang lebih murah adalah menambahkan `_kode_klpd` ke definisi view di pipeline — dicatat sebagai usulan perubahan, di luar lingkup dokumen ini.

Karena `pagu` bertipe `double precision`, tampilan rupiah harus dibulatkan eksplisit (`round(pagu)::bigint`) sebelum diformat, agar tidak muncul nilai seperti `Rp1.040.634.896.717,0001`.

### 8.3 Indeks Pendukung

Indeks yang **sudah ada** pada basis data:

| Tabel | Indeks |
|---|---|
| `rec_rup_paket_penyedia` | `ix_rec_rup_paket_penyedia_filter` |
| `rec_tender_pengumuman` | `ix_rec_pengumuman_filter` |
| `rec_ekatalog_paket_e_purchasing` | `ix_rec_ekatalog_paket_e_purchasing_filter` |

Indeks yang **diusulkan** (dibuat oleh pemilik DB, bukan dashboard):

```sql
CREATE UNIQUE INDEX IF NOT EXISTS mv_dash_kpi_pk    ON mv_dash_kpi    (_kode_klpd, _tahun);
CREATE UNIQUE INDEX IF NOT EXISTS mv_dash_sumber_pk ON mv_dash_sumber (_kode_klpd, _tahun, sumber, jenis);
CREATE INDEX IF NOT EXISTS mv_dash_satker_pk        ON mv_dash_satker (_kode_klpd, _tahun, nama_satker);
CREATE INDEX IF NOT EXISTS mv_dash_status_pk        ON mv_dash_status (_kode_klpd, _tahun, jenis);

CREATE INDEX IF NOT EXISTS ix_paket_kd_rup          ON rec_rup_paket_penyedia (kd_rup);
CREATE INDEX IF NOT EXISTS ix_paket_tgl_buat        ON rec_rup_paket_penyedia (tgl_buat_paket);
CREATE INDEX IF NOT EXISTS ix_ekatalog_rup_code     ON rec_ekatalog_paket_e_purchasing (rup_code);
CREATE INDEX IF NOT EXISTS ix_paket_penyedia_kode   ON rec_rup_paket_penyedia (kode_penyedia);
```

> `v_realisasi_rup` adalah **view**, tidak dapat diindeks. Karena itu KPI dan agregat per sumber/satker dibaca dari MV, bukan dari view secara langsung. Halaman D2 (daftar baris) tetap membaca view dengan filter + paginasi.

### 8.4 Perintah Refresh

```bash
php artisan dash:refresh-views           # semua MV
php artisan dash:refresh-views --only=kpi
```

Implementasi memakai `REFRESH MATERIALIZED VIEW CONCURRENTLY` (butuh indeks unik dari 8.2). Dijadwalkan **setelah** pipeline selesai:

| Waktu | Tugas |
|---|---|
| 04:30 harian | `dash:refresh-views` (setelah `inaproc:build-realisasi` pukul 04:00) |
| Manual | Tombol "Segarkan angka" pada D7 (Admin) |

**Trade-off yang diterima:** angka dashboard dapat tertinggal hingga refresh berikutnya. Karena itu setiap halaman menampilkan "terakhir diperbarui" dari `pull_log` dan MV, sehingga pengguna tahu batas kesegaran angkanya. Dashboard **tidak** melakukan refresh otomatis saat halaman dibuka (akan memblokir request).

### 8.5 Tabel Milik Dashboard (koneksi `dashboard`)

```sql
CREATE TABLE users (
    id            BIGSERIAL PRIMARY KEY,
    name          VARCHAR(128) NOT NULL,
    email         VARCHAR(190) UNIQUE NOT NULL,
    password      VARCHAR(255) NOT NULL,
    role          VARCHAR(16) NOT NULL CHECK (role IN ('admin','operator','viewer')),
    is_active     BOOLEAN NOT NULL DEFAULT TRUE,
    last_login_at TIMESTAMPTZ,
    created_at    TIMESTAMPTZ,
    updated_at    TIMESTAMPTZ
);

CREATE TABLE saved_filters (
    id         BIGSERIAL PRIMARY KEY,
    user_id    BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    nama       VARCHAR(64) NOT NULL,
    modul      VARCHAR(32) NOT NULL,
    params     JSONB NOT NULL,
    created_at TIMESTAMPTZ,
    UNIQUE (user_id, nama, modul)
);

CREATE TABLE activity_log (
    id         BIGSERIAL PRIMARY KEY,
    user_id    BIGINT REFERENCES users(id) ON DELETE SET NULL,
    aksi       VARCHAR(32) NOT NULL,
    modul      VARCHAR(32),
    params     JSONB,
    baris      INTEGER,
    ip         VARCHAR(45),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX ON activity_log (user_id, created_at DESC);
CREATE INDEX ON activity_log (aksi, created_at DESC);
```

### 8.6 Skrip Hak Akses Read-Only

```sql
-- Dijalankan sebagai superuser/pemilik, BUKAN oleh dashboard.
CREATE ROLE dash_ro LOGIN PASSWORD '<disimpan di .env, tidak di dokumen ini>';
GRANT CONNECT ON DATABASE inaproc TO dash_ro;
GRANT USAGE ON SCHEMA public TO dash_ro;
GRANT SELECT ON ALL TABLES IN SCHEMA public TO dash_ro;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT ON TABLES TO dash_ro;

-- Pastikan tidak ada hak tulis.
REVOKE INSERT, UPDATE, DELETE, TRUNCATE ON ALL TABLES IN SCHEMA public FROM dash_ro;
REVOKE CREATE ON SCHEMA public FROM dash_ro;

-- Kredensial KLPD tidak boleh terbaca dashboard.
REVOKE SELECT ON klpd_credentials FROM dash_ro;
```

**Penerimaan:**
- [ ] `dash_ro` berhasil `SELECT` dari `v_realisasi_rup`
- [ ] `dash_ro` gagal `INSERT`/`UPDATE`/`DELETE` (ditolak PostgreSQL)
- [ ] `dash_ro` gagal `SELECT` dari `klpd_credentials`
- [ ] Keempat MV ada dan berisi angka acuan Bab 2.1
- [ ] `dash:refresh-views` selesai < 60 detik pada volume penuh

---

## 9. Rencana Implementasi

| Fase | Lingkup | Kriteria Keluar |
|---|---|---|
| **1. Fondasi** | Proyek Laravel, dua koneksi, role `dash_ro`, autentikasi, layout, global filter, banner cakupan | Login berhasil; `dash_ro` terbukti read-only; filter bertahan antar halaman |
| **2. MV & KPI** | Empat materialized view, `dash:refresh-views`, halaman Ringkasan | Enam KPI menampilkan angka acuan; muat ≤ 300 ms |
| **3. Realisasi** | D2 daftar, filter, drill-down rantai warisan | 3.665 baris; filter per sumber tepat; rantai tampil |
| **4. Paket** | D3 daftar, status realisasi, agregasi, detail paket | Filter "Belum" = 12.361 baris |
| **5. Tender & E-Katalog** | D4 dua tab + stepper, D5 sebaran status | Angka status sesuai acuan |
| **6. Satker & Kualitas** | D6 daftar+detail, D7 pull_log/kesegaran/cakupan/skema | Top 5 satker sesuai acuan; penyimpangan `_fetched_at` terdeteksi |
| **7. Ekspor & Pengaturan** | D9 streaming, D8 pengguna & audit | Ekspor 50.000 baris hemat memori; audit tercatat |
| **8. Pengerasan** | Uji performa, aksesibilitas, dokumentasi operasional | p95 ≤ 1.500 ms; WCAG AA; dokumentasi siap |

Fase 1–4 adalah nilai inti; fase 5–8 dapat berjalan bertahap.

---

## 10. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Dashboard dipakai sebagai sumber kebenaran padahal data 1 KLPD | Tinggi | Banner cakupan wajib; label "data belum lengkap" pada KLPD tanpa data |
| Angka MV tertinggal dari data asli | Sedang | Tampilkan waktu refresh; tombol segarkan untuk Admin; jadwal 04:30 |
| Query lambat pada tabel 194.477 baris (`rec_master_program`) | Sedang | Paginasi server-side, `statement_timeout`, MV untuk agregat |
| `_fetched_at` bertipe `text` menyebabkan error cast | Sedang | Cast eksplisit + penanganan nilai tak valid; dilaporkan di D7 |
| Join `kd_rup` gagal karena beda tipe (bigint vs text) | Tinggi | Selalu normalisasi `round(kd_rup::numeric)::bigint::text` di kedua sisi |
| Dashboard diberi user `sipedal` demi kepraktisan | Tinggi | Dilarang di dokumen ini; verifikasi hak akses pada fase 1 |
| Token KLPD bocor lewat halaman Kualitas Data | Tinggi | `REVOKE SELECT ON klpd_credentials`; hanya status yang ditampilkan |
| Ekspor besar menghabiskan memori | Sedang | Streaming cursor + batas 50.000 baris |
| Rantai warisan putus (data tidak konsisten) | Rendah | Tampilkan peringatan, bukan error; catat di D7 |
| Perbedaan zona waktu membuat tanggal meleset sehari | Sedang | Semua tampilan `Asia/Jakarta`, tanggal disimpan sebagai teks di sumber |

---

## 11. Definisi Selesai

- [ ] Login dan tiga peran berfungsi; Viewer tidak dapat membuka Pengaturan
- [ ] Global filter KLPD+tahun memengaruhi seluruh modul dan bertahan di URL
- [ ] Enam KPI D1 menampilkan angka acuan Bab 2.1 tanpa selisih
- [ ] D2 menampilkan 3.665 baris; filter per sumber/jenis tepat; rantai warisan dapat ditelusuri
- [ ] D3 filter "Belum" menghasilkan 12.361 baris
- [ ] D4 dan D5 menampilkan sebaran status sesuai acuan
- [ ] D6 menampilkan 669 satker; top 5 sesuai acuan
- [ ] D7 mendeteksi data basi dan penyimpangan skema (`_fetched_at text`)
- [ ] Ekspor mengikuti filter, ≤ 50.000 baris, tercatat di audit
- [ ] `dash_ro` terbukti tidak dapat menulis dan tidak dapat membaca `klpd_credentials`
- [ ] p95 halaman ≤ 1.500 ms pada volume penuh
- [ ] Tidak ada token utuh pada HTML, JSON, log, maupun ekspor
- [ ] Dokumentasi operasional tersedia (instalasi, refresh MV, pembuatan user)

---

## Lampiran A — Peta Modul ke Tabel

| Modul | Tabel / View utama | Tabel pendukung |
|---|---|---|
| D1 Ringkasan | `mv_dash_kpi`, `mv_dash_sumber`, `mv_dash_satker` | `pull_log` |
| D2 Realisasi | `v_realisasi_rup` | `ref_rup_warisan`, `ref_rup_proses` |
| D3 Paket | `rec_rup_paket_penyedia` | `rec_rup_master_satker`, `rec_rup_history_kaji_ulang` |
| D4 Tender | `rec_tender_pengumuman`, `rec_tender_non_tender_pengumuman` | `rec_tender_peserta_tender`, `rec_tender_jadwal_tahapan_*` |
| D5 E-Katalog | `rec_ekatalog_paket_e_purchasing` | `rec_ekatalog_e_purchasing_by_produk` |
| D6 Satker | `rec_rup_master_satker` | `mv_dash_satker`, `v_realisasi_rup` |
| D7 Kualitas | `pull_log`, `endpoints` | `information_schema`, `derived_progress` |
| D8 Pengaturan | `users`, `saved_filters`, `activity_log` | — |

---

## Lampiran B — Konfigurasi

| Kunci | Default | Keterangan |
|---|---|---|
| `DB_CONNECTION` | `dashboard` | Koneksi tulis (default aplikasi) |
| `DB_DATABASE` | `inaproc_dashboard` | Basis data milik dashboard |
| `DB_USERNAME` / `DB_PASSWORD` | — | User `dash_app` |
| `INAPROC_DB_HOST` | `103.22.250.210` | Host sumber baca |
| `INAPROC_DB_PORT` | `54320` | Port sumber baca |
| `INAPROC_DB_DATABASE` | `inaproc` | Basis data pipeline |
| `INAPROC_DB_USERNAME` | `dash_ro` | **Wajib** user read-only |
| `INAPROC_DB_PASSWORD` | — | Kata sandi `dash_ro`, hanya di `.env` |
| `DASH_FRESHNESS_HOURS` | `48` | Ambang peringatan data basi |
| `DASH_PAGE_SIZES` | `25,50,100` | Pilihan ukuran halaman |
| `DASH_EXPORT_MAX_ROWS` | `50000` | Batas baris ekspor |
| `DASH_DEFAULT_KLPD` | (otomatis) | KLPD default bila tidak ditentukan |
| `DASH_TZ` | `Asia/Jakarta` | Zona waktu tampilan |

**Catatan:** `INAPROC_DB_PASSWORD` untuk `dash_ro` **tidak** boleh sama dengan `sipedal`, dan hanya disimpan di `.env` server (tidak masuk VCS).

---

## Lampiran C — Catatan Implementasi Kritis

Tiga hal yang paling sering menjadi sumber bug pada proyek ini:

### C.1 Normalisasi `kd_rup` — wajib, meski hasilnya tampak sama

`rec_rup_paket_penyedia.kd_rup` bertipe `bigint`, sedangkan `v_realisasi_rup.kd_rup` bertipe `text` hasil `round(...)::bigint::text`. Gunakan selalu:

```sql
ON v.kd_rup = round(p.kd_rup::numeric)::bigint::text
```

**Hasil pengujian pada data nyata:** join naif `p.kd_rup::text = v.kd_rup` juga menghasilkan 3.665 baris — identik. Artinya pada dataset saat ini belum ada nilai desimal, sehingga kedua bentuk tidak berbeda. **Jangan jadikan itu alasan memakai join naif.** Pipeline tidak menjamin `kd_rup` selalu bulat (PRD pipeline F11.4 secara eksplisit menangani `numeric(38,10)`), sehingga begitu ada satu kode bertipe desimal, join naif akan diam-diam kehilangan baris tanpa error. Bentuk `round(...)::bigint::text` adalah satu-satunya yang aman.

### C.2 Cast `_fetched_at`

Kolom ini bertipe `text` (menyimpang dari PRD pipeline F6.2). Perbandingan langsung seperti `_fetched_at < now() - interval '48 hours'` akan error karena PostgreSQL membandingkan `text` dengan `timestamptz`. Gunakan:

```sql
max(_fetched_at::timestamptz)
```

**Hasil pengujian:** seluruh 16.024 baris `rec_rup_paket_penyedia` berhasil dikonversi (0 nilai gagal). Namun cast tetap harus dibungkus penanganan, karena kolom `text` bebas menerima nilai apa pun pada tarikan berikutnya. Untuk daftar kesegaran, gunakan bentuk aman:

```sql
SELECT max(_fetched_at::timestamptz)
  FROM rec_rup_paket_penyedia
 WHERE _fetched_at ~ '^\d{4}-\d{2}-\d{2}';
```

### C.3 `jenis_pengadaan` multi-nilai

Terdapat 11 baris bernilai `Barang;Jasa Lainnya`. Agregasi per jenis **wajib** memakai `unnest`, bukan `GROUP BY jenis_pengadaan`:

```sql
SELECT trim(j) AS jenis, count(*)
  FROM rec_rup_paket_penyedia, unnest(string_to_array(jenis_pengadaan, ';')) j
 WHERE jenis_pengadaan IS NOT NULL AND trim(j) <> ''
 GROUP BY 1
 ORDER BY 2 DESC;
```

**Hasil pengujian:** Barang 8.326, Jasa Lainnya 3.488, Pekerjaan Konstruksi 2.160, Jasa Konsultansi 2.011.

Perhatikan selisihnya terhadap hitungan mentah: `GROUP BY jenis_pengadaan` menghasilkan Barang 8.315 dan Jasa Lainnya 3.477, karena 11 baris gabungan terhitung sebagai satu kategori tersendiri. Angka 8.326 dan 3.488 adalah yang benar untuk laporan per jenis.
