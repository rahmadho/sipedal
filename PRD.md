# PRD — Inaproc Data Pipeline (Laravel)

**Nama kerja:** `inaproc-pipeline`
**Versi dokumen:** 1.0
**Tanggal:** 2026-10-22
**Status:** Siap eksekusi sebagai proyek baru
**Stack:** Laravel 13 · PHP 8.5 · PostgreSQL 18

---

## 1. Ringkasan Produk

Sistem backend untuk menarik data pengadaan dari **INAPROC API Gateway** (`https://data.inaproc.id`) berdasarkan **Kode KLPD** dan **Tahun Anggaran**, menyimpannya secara terstruktur, lalu mengolahnya menjadi **realisasi RUP** — yaitu daftar paket RUP penyedia yang sudah terpakai dalam proses pengadaan, termasuk paket hasil perbaikan yang mewarisi proses dari RUP sebelumnya.

Sistem ini adalah **layanan data**, bukan aplikasi berantarmuka. Semua operasi dikendalikan melalui Artisan command dan scheduler. Konsumsi data oleh pengguna akhir dilakukan oleh aplikasi terpisah (dashboard) yang membaca langsung dari database.

### 1.1 Masalah yang Diselesaikan

| Masalah | Akar Sebab | Solusi |
|---|---|---|
| Data pengadaan tersebar di puluhan endpoint | Endpoint dibagi per topik (RUP, tender, e-katalog) | Registry endpoint terpusat + pipeline tarik otomatis |
| Pagination tidak konsisten | Dua keluarga API: cursor-based dan single-shot | Dua adapter terpisah di lapisan HTTP |
| Akses per-instansi berbeda | 1 API token hanya berlaku untuk 1 KLPD | Manajemen kredensial multi-KLPD + worker paralel |
| Data tarikan bisa rusak sebagian | Penulisan baris per baris tanpa transaksi | Delete-insert dalam satu transaksi per endpoint |
| Tidak tahu kapan RUP sudah dipakai | Relasi paket RUP ke proses pengadaan | Pelacakan realisasi via FK + pewarisan kaji ulang |

### 1.2 Prinsip Desain

1. **Data lama tidak boleh hilang karena kegagalan.** Setiap penulisan bersifat atomik. Gagal berarti rollback.
2. **Idempoten.** Menjalankan tarik dua kali untuk KLPD + tahun yang sama menghasilkan kondisi database yang identik.
3. **Teramati.** Setiap keputusan (sukses, kosong, gagal, fallback, skip) tercatat dan dapat ditelusuri.
4. **Gagal setempat.** Satu endpoint gagal tidak menggagalkan endpoint lain. Satu KLPD gagal tidak menggagalkan KLPD lain.
5. **Konfigurasi di luar kode.** Token, batas laju, dan jumlah worker diatur tanpa mengubah kode.

---

## 2. Tujuan & Ukuran Keberhasilan

| # | Tujuan | Ukuran |
|---|---|---|
| G1 | Menarik seluruh endpoint aktif per KLPD + tahun | 61 endpoint tuntas untuk minimal 2 KLPD |
| G2 | Menyimpan tanpa duplikasi dan tanpa kehilangan data | Tarik ulang tidak menambah baris; injeksi error tidak menghapus data |
| G3 | Menangani fallback otomatis | 4 aturan fallback teruji |
| G4 | Menghitung realisasi RUP secara benar | Hasil lolos validasi dataset acuan (Bab 12) |
| G5 | Mendukung multi-KLPD paralel | 20 KLPD terdaftar; isolasi data antar KLPD terjaga |
| G6 | Menyediakan jejak audit setiap tarikan | 1 baris log per endpoint × KLPD × tahun |
| G7 | Tidak menarik ulang data turunan yang sudah tersimpan | Run kedua tanpa perubahan → 0 request untuk endpoint turunan |

---

## 3. Ruang Lingkup

### 3.1 Termasuk

- Klien HTTP untuk dua keluarga API (`/api/v1/*` dan `/api/legacy/*`)
- Registry definisi endpoint
- Penulisan hasil tarik ke database
- Penanganan fallback
- Pembatasan laju (rate limiting) per token
- Orkestrasi multi-KLPD paralel
- Pencatatan log tarikan
- Pembangunan data realisasi RUP (tabel materialisasi + view)

### 3.2 Tidak Termasuk

| Item | Alasan |
|---|---|
| Antarmuka pengguna / dashboard | Aplikasi terpisah, dibangun setelah pipeline stabil |
| Ekspor Excel / CSV | Kewenangan aplikasi dashboard |
| Autentikasi & manajemen pengguna | Belum ada kebutuhan; operasi via CLI |
| `/api/v1/rup/program` | Cakupan data tumpang tindih dengan `master-program`, pemanfaatan belum jelas |
| `/api/v1/dashboard/*` (56 endpoint) | Merupakan agregat siap-pakai; sistem ini menghitung sendiri dari data mentah |
| `/api/legacy/ekatalog-archive/*` & `/api/v1/ekatalog-archive/*` | Data e-Katalog generasi lama, dibatasi maksimal tahun 2025 |
| Endpoint turunan berbasis `kode_penyedia` | Membutuhkan hak akses khusus dari pengelola API |

Daftar lengkap endpoint yang termasuk dan yang dikecualikan — beserta path, grup, scope, dan nama tabelnya — ada di **Lampiran A**.

---

## 4. Pengguna & Peran

| Peran | Kebutuhan Utama |
|---|---|
| **Operator** | Menjalankan tarik manual, memantau status, menangani kegagalan |
| **Scheduler (sistem)** | Menjalankan tarik berkala tanpa intervensi |
| **Aplikasi dashboard** | Membaca data `rec_*` dan `v_realisasi_rup` secara read-only |

---

## 5. Arsitektur

### 5.1 Stack Teknologi

| Komponen | Pilihan | Peran |
|---|---|---|
| Framework | Laravel 13 | Struktur aplikasi, queue, migration, scheduler |
| Bahasa | PHP 8.5 | Runtime |
| Database | PostgreSQL 18 | Satu-satunya penyimpanan, produksi maupun pengujian |
| HTTP Client | Laravel HTTP Client | Concurrency, retry, timeout |
| Antrean | Redis | Job tarik per KLPD |
| Penjadwalan | Laravel Scheduler | Tarik berkala |

> **Catatan:** PostgreSQL adalah satu-satunya database yang didukung. Tidak ada
> lapisan kompatibilitas untuk mesin database lain. Seluruh kode bebas
> memakai fitur khas PostgreSQL (`unnest`, `string_to_array`, `jsonb`,
> recursive CTE, tipe `numeric`/`timestamptz`). Pengujian otomatis wajib
> memakai instance PostgreSQL, bukan database in-memory.

### 5.2 Struktur Direktori

```
app/
├── Console/Commands/
│   ├── InaprocPullCommand.php          # tarik data
│   ├── InaprocProbeCommand.php         # uji akses endpoint
│   ├── InaprocValidateTokensCommand.php# validasi kredensial
│   └── InaprocBuildRealisasiCommand.php# bangun realisasi RUP
├── Jobs/
│   ├── PullKlpdJob.php                 # tarik seluruh endpoint untuk 1 KLPD
│   └── BuildRealisasiJob.php           # bangun materialisasi + view
├── Services/
│   ├── Inaproc/
│   │   ├── ApiClient.php               # HTTP, retry, deteksi blokir
│   │   ├── EndpointRegistry.php        # sumber kebenaran definisi endpoint
│   │   ├── Paginator.php               # strategi cursor & single-shot
│   │   └── FallbackResolver.php        # aturan fallback
│   ├── Pull/
│   │   ├── PullService.php             # orkestrasi per endpoint
│   │   ├── SchemaManager.php           # deteksi & evolusi kolom
│   │   └── TableWriter.php             # delete-insert transaksional
│   └── Realisasi/
│       ├── ProsesCollector.php         # eksploitasi FK multi-nilai
│       ├── WarisanResolver.php         # penelusuran leluhur (BFS)
│       └── ViewBuilder.php             # view v_realisasi_rup
├── Support/
│   ├── RateLimiter.php                 # token bucket per kredensial
│   └── KodeNormalizer.php              # normalisasi kode RUP
├── Models/
└── Enums/
    ├── ApiFamily.php                   # v1 | legacy
    ├── EndpointGroup.php               # rup | tender | ekatalog | bela | master
    ├── EndpointScope.php               # klpd | derived | tahun
    └── PullStatus.php                  # OK | KOSONG | GAGAL | FORBIDDEN | NONAKTIF
database/
├── migrations/                         # tabel sistem
├── seeders/EndpointSeeder.php          # 65 definisi endpoint
└── schema/                             # tabel rec_* dibuat runtime
```

### 5.3 Alur Eksekusi

```
Operator/Scheduler
        │
        ▼
php artisan inaproc:pull --klpd=D462 --tahun=2026
        │
        ▼
  Muat kredensial KLPD (token)
        │
        ▼
  Untuk setiap endpoint aktif:
        │
        ├── v1    ──► loop halaman via cursor sampai habis
        ├── legacy──► satu request, seluruh data
        │
        ▼
  Evaluasi respons
        │
        ├── 200 + ada data  ──► simpan (delete-insert transaksional)
        ├── 200 + kosong    ──► skip, tanpa menyentuh tabel
        ├── 403             ──► skip endpoint, catat FORBIDDEN
        ├── 429             ──► tunggu & ulangi
        └── non-200 lain    ──► coba fallback (bila ada)
                                  ├── berhasil ──► simpan ke tabel utama
                                  └── gagal    ──► data lama dibiarkan, catat GAGAL
        │
        ▼
  Tulis satu baris pull_log
        │
        ▼
  (Setelah semua KLPD selesai)
  php artisan inaproc:build-realisasi
        │
        ▼
  ref_rup_proses + ref_rup_warisan + view v_realisasi_rup
```

---

## 6. Kebutuhan Fungsional

### F1 — Registry Endpoint

Definisi endpoint disimpan sebagai **satu sumber kebenaran** yang dapat di-query dan diperluas tanpa mengubah kode pipeline.

| Field | Tipe | Keterangan |
|---|---|---|
| `name` | string, unik | Identitas, mis. `paket-penyedia` |
| `path` | string | Path API, mis. `/api/v1/rup/paket-penyedia` |
| `api` | enum | `v1` atau `legacy` |
| `group` | enum | `rup`, `tender`, `ekatalog`, `bela`, `master` |
| `scope` | enum | `klpd` \| `derived` \| `tahun` |
| `active` | boolean | Ikut dalam tarik otomatis |
| `order` | integer | Urutan eksekusi |

**Ketentuan:**
- Total 65 endpoint terdaftar, 61 di antaranya `active`.
- Endpoint `scope = derived` tidak pernah ikut tarik otomatis.

#### F1.1 — Pemetaan Scope ke Parameter

Tidak semua endpoint menerima `kode_klpd` + `tahun`. Parameter ditentukan oleh `scope`:

| Scope | Parameter yang dikirim | Kapan dipakai |
|---|---|---|
| `klpd` | `kode_klpd` + `tahun` (+ `limit`, `cursor` untuk v1) | Mayoritas endpoint (56 dari 61 yang aktif) |
| `tahun` | `tahun` saja — `kode_klpd` **tidak** dikirim | Data bersifat nasional, tidak terikat instansi |
| `derived` | Parameter turunan (`kode_penyedia`, `kd_kategori_*`), tanpa `kode_klpd`/`tahun` | Endpoint yang nilainya berasal dari hasil tarikan endpoint lain |

**Daftar lengkap endpoint yang bukan `scope = klpd`:**

| Endpoint | Scope | Parameter | Aktif |
|---|---|---|---|
| `master-program` | `tahun` | `tahun` | ✔ |
| `ekatalog-penyedia-detail` | `derived` | `kode_penyedia` | ✘ |
| `ekatalog-list-produk-penyedia` | `derived` | `kode_penyedia` | ✘ |
| `ekatalog-list-kategori-produk` | `derived` | berjenjang: level 1 tanpa parameter; level berikutnya `kd_kategori_1`, `kd_kategori_2`, dst. | ✘ |
| `legacy-ekatalog-penyedia-detail` | `derived` | `kode_penyedia` | ✘ |

#### F1.2 — Ketentuan Endpoint Turunan (`derived`)

Nilai parameter turunan **tidak** berasal dari input operator, melainkan dari hasil tarikan endpoint lain:

```
ekatalog-paket-e-purchasing (scope: klpd)
        │ kumpulkan nilai unik dari kolom kode_penyedia
        ▼
penyedia-detail?kode_penyedia=XXX     ← satu request per nilai
```

Konsekuensi yang harus diperhitungkan:

| Aspek | Ketentuan |
|---|---|
| Jumlah request | Satu request per nilai unik. Satu KLPD dapat menghasilkan ratusan nilai → ratusan request |
| Pagination | Tetap mengikuti aturan keluarga API-nya (v1: cursor) |
| Penyimpanan | Menggunakan pola F6 yang sama, ditambah `_source` berisi nama endpoint turunan |
| Kegagalan satu nilai | Tidak menggagalkan keseluruhan; dicatat di `pull_log` per nilai |
| Batas laju | Tunduk pada `RateLimiter` yang sama |

Pada versi ini seluruh endpoint `derived` berstatus **nonaktif**. Implementasi wajib menyediakan jalur eksekusinya (bukan hanya menandai nonaktif), sehingga pengaktifan cukup dengan mengubah `active = true` setelah hak akses tersedia.

**Penerimaan:**
- [ ] 65 endpoint tercatat dengan atribut benar
- [ ] Parameter dibangun dari `scope`, bukan hardcode per endpoint
- [ ] `master-program` terbukti tidak mengirim `kode_klpd`
- [ ] Endpoint `derived` dapat dipanggil dengan parameter turunan
- [ ] Dapat difilter per grup dan status aktif
- [ ] Endpoint baru dapat ditambahkan tanpa menyentuh `PullService`

#### F1.3 — Incremental Endpoint Turunan

Endpoint turunan dapat menghasilkan **ratusan hingga ribuan nilai** per KLPD (contoh nyata: 1.540 `kode_penyedia` unik untuk satu KLPD). Menarik seluruhnya setiap kali dijalankan berarti ribuan request untuk data yang kemungkinan sudah tersimpan.

**Aturan:** nilai turunan yang **sudah ada di database untuk KLPD dan tahun yang sama dilewati** — tidak diminta ulang ke API.

```
nilai_permintaan = himpunan nilai dari endpoint sumber
nilai_tersimpan = SELECT nilai_kunci FROM derived_progress
                  WHERE endpoint = ? AND kode_klpd = ? AND tahun = ?
nilai_ditarik   = nilai_permintaan - nilai_tersimpan
```

**Ketentuan per endpoint:**

| Endpoint | Kolom kunci | Sumber nilai |
|---|---|---|
| `ekatalog-penyedia-detail` | `kode_penyedia` | `rec_ekatalog_paket_e_purchasing.kode_penyedia` |
| `ekatalog-list-produk-penyedia` | `kode_penyedia` | `rec_ekatalog_paket_e_purchasing.kode_penyedia` |
| `legacy-ekatalog-penyedia-detail` | `kode_penyedia` | `rec_legacy_ekatalog_paket_e_purchasing.kode_penyedia` |

**Ketentuan operasional:**

| Aspek | Ketentuan |
|---|---|
| Cakupan filter | `_kode_klpd` + `_tahun` — nilai yang sama untuk KLPD lain **tetap ditarik** |
| Penyimpanan | **Append**, bukan delete-insert. Baris yang sudah ada tidak dihapus |
| Bukti keberadaan | Baris pada tabel `derived_progress` (Bab 8.1), bukan sekadar keberadaan data — karena satu nilai bisa sah menghasilkan 0 baris |
| Hasil kosong | Tetap dicatat di `derived_progress` dengan `hasil_rows = 0`, agar tidak dicoba ulang setiap run |
| Kegagalan per nilai | Tidak dicatat di `derived_progress` → otomatis dicoba lagi pada run berikutnya |
| Log | `pull_log` mencatat ringkasan: jumlah nilai diminta, dilewati, ditarik, gagal |
| Paksa tarik ulang | Flag `--refresh-derived` mengabaikan cache dan menarik seluruh nilai |

**Konsekuensi yang diterima (keputusan produk):**

> Data turunan yang sudah tersimpan **tidak pernah diperbarui** pada tarik normal. Bila detail penyedia berubah di sisi API, perubahan tersebut tidak akan tercermin. Pembaruan hanya terjadi melalui `--refresh-derived`, atau dengan menghapus baris terkait lebih dulu.

**Perbedaan dengan endpoint `scope = klpd`:**

| | Scope `klpd` | Endpoint turunan |
|---|---|---|
| Pola simpan | Delete-insert per KLPD+tahun | **Append** per nilai kunci |
| Data hilang saat gagal | Ya, seluruh filter | Tidak, hanya nilai yang gagal |
| Alasan | Sumber tunggal, selalu lengkap | Sumber banyak, tiap nilai independen |

> **Catatan penting:** bila endpoint turunan memakai delete-insert seperti F6, hasil tarik yang gagal di tengah jalan akan **menghapus** data yang diperoleh sebelumnya. Karena itu append wajib, dan `pull_log` harus mencatat nilai mana yang gagal agar run berikutnya mengulanginya.

**Penerimaan:**
- [ ] Run kedua tanpa perubahan data sumber → **0 request** ke endpoint turunan
- [ ] Run kedua mencatat di log: `nilai_source=N, dilewati=N, ditarik=0`
- [ ] Nilai baru pada sumber → hanya nilai tersebut yang ditarik
- [ ] Data KLPD lain dengan `kode_penyedia` sama tetap ditarik terpisah
- [ ] Request yang gagal tidak tercatat tersimpan dan diulang pada run berikutnya
- [ ] `--refresh-derived` menarik ulang seluruh nilai
- [ ] Unit test dengan HTTP fake membuktikan jumlah request sesuai jumlah nilai yang belum tersimpan

---

#### F1.4 — Penanganan Khusus `ekatalog-list-kategori-produk`

`ekatalog-list-kategori-produk` **tidak** memakai pola F1.3 secara langsung karena strukturnya **berjenjang (pohon)**, bukan daftar datar:

```
level 1: tanpa parameter            → daftar kategori induk
level 2: ?kd_kategori_1=A          → anak dari kategori A
level 3: ?kd_kategori_1=A&kd_kategori_2=B  → turunan B
```

Konsekuensinya: **mengambil kategori induk tidak berarti turunannya sudah diambil.** Karena itu penanda keberadaan di `derived_progress` saja tidak cukup untuk menyatakan sebuah cabang tuntas.

**Aturan minimal yang wajib dipatuhi:**

| Aspek | Ketentuan |
|---|---|
| Kunci progres | `derived_progress` menyimpan **parameter penuh** (mis. `kd_kategori_1=A&kd_kategori_2=B`), bukan hanya kode kategori |
| Penanda tuntas | Hanya dicatat setelah respons sukses, termasuk bila kosong |
| Penelusuran | Bila cabang sudah tuntas, **tidak ditelusuri ulang** pada run berikutnya |
| Kedalaman | Batas kedalaman wajib ditetapkan sebelum implementasi agar tidak terjadi loop |

**Keputusan yang ditunda:** struktur pohon penuh (rekam cabang, kedalaman maksimum, strategi pembaruan) **belum ditetapkan** di PRD ini karena pemanfaatan endpoint-nya belum diperlukan. Endpoint tetap nonaktif.

**Penerimaan:**
- [ ] Tidak diaktifkan tanpa revisi PRD yang menetapkan struktur pohonnya
- [ ] Bila diaktifkan kelak, run kedua tidak mengulang cabang yang sudah tuntas

### F2 — Penamaan Tabel Data

Setiap endpoint memetakan ke satu tabel tetap menurut pola:

```
rec_<group>_<slug>          untuk api = v1
rec_legacy_<group>_<slug>   untuk api = legacy
```

`<slug>` = nama endpoint dengan spasi/karakter non-alfanumerik diganti `_`, seluruhnya huruf kecil.

Contoh: `rup` + `paket-penyedia` → `rec_rup_paket_penyedia`; `legacy` + `tender` + `non-tender-selesai` → `rec_legacy_tender_non_tender_selesai`.

**Penerimaan:**
- [ ] Pemetaan deterministik dan unik
- [ ] Panjang nama tidak melampaui batas identifier PostgreSQL (63 karakter)
- [ ] Tidak menggunakan kata kunci SQL

---

### F3 — Klien API & Pagination

#### F3.1 Keluarga v1 — Cursor Pagination

```
GET {path}?kode_klpd={klpd}&tahun={tahun}&limit=1000[&cursor={cursor}]
```

```json
{
  "meta": { "cursor": "...", "has_more": true, "limit": 1000, "last_update_ref": "..." },
  "success": true,
  "data": [ ... ]
}
```

**Aturan terminasi** — loop berhenti bila **salah satu** terpenuhi:
1. `meta.has_more` bernilai `false`
2. `meta.cursor` kosong, `null`, atau tidak ada

> Kedua syarat wajib diperiksa. API dapat mengirim `has_more: true` bersamaan dengan `cursor` kosong; mengandalkan satu syarat saja menyebabkan crash pada halaman terakhir.

**Penerimaan:**
- [ ] Lanjut ke halaman berikutnya selama `has_more` true **dan** cursor terisi
- [ ] Berhenti tanpa exception saat cursor kosong
- [ ] Batas pengaman 10.000 halaman per endpoint mencegah loop tak berujung
- [ ] `limit` dibatasi maksimum 1000 (batas API)

#### F3.2 Keluarga legacy — Single Shot

- Satu request mengembalikan **seluruh data** sebagai array JSON (tanpa pembungkus `meta`).
- Wajib menyertakan `kode_klpd` dan `tahun`.
- Tidak ada pagination; tidak ada cursor.

**Penerimaan:**
- [ ] Tepat satu request per endpoint per tarik
- [ ] Respons berupa array langsung tertangani
- [ ] Respons berisi objek tunggal tetap tertangani

#### F3.3 Ketahanan Respons

**Penerimaan:**
- [ ] Respons bukan JSON (halaman HTML error) tidak menyebabkan exception
- [ ] Timeout mengikuti konfigurasi, bukan default tak terbatas
- [ ] Ukuran respons besar (puluhan MB) diproses dengan aman

---

### F4 — Klasifikasi Status HTTP

| Kondisi | Tindakan | Status log |
|---|---|---|
| `200` + data tidak kosong | Simpan (delete-insert) | `OK` |
| `200` + data kosong (`[]`) | Lewati; tabel tidak disentuh; tidak fallback | `KOSONG` |
| `403` | Lewati endpoint ini; lanjut endpoint berikutnya | `FORBIDDEN` |
| `429` | Tunggu `Retry-After` / backoff, lalu ulangi | tidak dicatat gagal bila akhirnya berhasil |
| `5xx`, timeout, koneksi terputus | Retry terbatas, lalu fallback bila tersedia | `GAGAL` bila semua gagal |
| Non-200 lainnya | Fallback bila tersedia | sesuai hasil |

**Penerimaan:**
- [ ] `403` tidak menghentikan proses keseluruhan
- [ ] `429` tidak tercatat `GAGAL` selama berhasil setelah retry
- [ ] Setiap keputusan tercatat di `pull_log`

---

### F5 — Fallback

Dua pasangan fallback:

| Endpoint utama | Endpoint fallback |
|---|---|
| `paket-penyedia` | `paket-penyedia-terumumkan` |
| `paket-swakelola` | `paket-swakelola-terumumkan` |

**Aturan wajib:**

| # | Aturan |
|---|---|
| R1 | Fallback **hanya** dipicu saat endpoint utama membalas **HTTP non-200** |
| R2 | Endpoint utama `200` dengan data kosong → **lewati**, jangan fallback |
| R3 | Utama **dan** fallback gagal → **data lama tidak dihapus** |
| R4 | Hasil fallback ditulis ke **tabel endpoint utama**, bukan tabel terpisah, karena struktur respons identik. Asal data ditandai pada kolom `_source` |

**Penerimaan:**
- [ ] R1–R4 masing-masing memiliki test
- [ ] `_source` pada baris fallback berisi nama endpoint fallback
- [ ] Log mencatat alasan fallback dipicu

---

### F6 — Penyimpanan Data

#### F6.1 Pola Delete-Insert Transaksional

Untuk setiap kombinasi (endpoint, KLPD, tahun):

```
BEGIN
  DELETE FROM <tabel> WHERE _kode_klpd = :klpd AND _tahun = :tahun
  INSERT INTO <tabel> (...) VALUES (...), (...)   -- batch
COMMIT
```

- **Tidak menggunakan upsert.**
- Kegagalan kapan pun sebelum `COMMIT` → rollback total → data lama utuh.

> **Pengecualian:** endpoint `scope = derived` **tidak** mengikuti pola ini. Lihat F1.3 — endpoint turunan memakai **append** per nilai, karena delete-insert akan menghapus hasil parsial bila tarikan terputus di tengah (potensi ribuan nilai).

**Penerimaan:**
- [ ] Tarik ulang tidak menghasilkan duplikasi
- [ ] Kegagalan insert di tengah proses tidak mengubah isi tabel
- [ ] Data KLPD lain tidak tersentuh
- [ ] Insert dilakukan per batch (500–1000 baris) untuk performa

#### F6.2 Kolom Internal

Setiap tabel `rec_*` memiliki kolom sistem:

| Kolom | Tipe | Keterangan |
|---|---|---|
| `_row_id` | `bigserial` | Primary key |
| `_kode_klpd` | `varchar(16)` | Kunci partisi data |
| `_tahun` | `smallint` | Kunci partisi data |
| `_source` | `varchar(128)` | Endpoint asal (untuk fallback) |
| `_fetched_at` | `timestamptz` | Waktu tarik |

Indeks wajib: `(_kode_klpd, _tahun)`.

**Penerimaan:**
- [ ] Kelima kolom selalu ada, ditambahkan otomatis saat tabel dibuat
- [ ] Nama kolom API yang bentrok dengan kolom sistem diberi prefiks `api_`

#### F6.3 Evolusi Skema

Kolom API **dideteksi dari data**, bukan didefinisikan manual.

| Aturan | Keterangan |
|---|---|
| Normalisasi nama | huruf kecil, non-alfanumerik → `_`, hapus `_` berlebih |
| Inferensi tipe | `bigint` untuk bilangan bulat, `numeric` untuk desimal, `boolean` untuk bool, `timestamptz` untuk tanggal ISO, `text` untuk lainnya |
| Kolom baru | Muncul di tarikan berikutnya → `ALTER TABLE ADD COLUMN` otomatis |
| Tipe meluas | `bigint` → `numeric` → `text` bila diperlukan; tidak pernah menyempit otomatis |
| Tabel baru | Dibuat otomatis saat endpoint pertama kali ditarik |

**Penerimaan:**
- [ ] Endpoint baru langsung dapat ditarik tanpa migration manual
- [ ] Perubahan tipe aman (tidak menghilangkan data)
- [ ] Ada command verifikasi untuk membandingkan skema aktual dengan hasil tarik

---

### F7 — Pembatasan Laju (Rate Limiting)

Satu token mewakili tepat satu KLPD; penghitungan laju bersifat **per token**.

| Keluarga API | Batas |
|---|---|
| `v1` | 300 request/menit |
| `legacy` | 90 request/menit |

**Empat lapisan wajib:**

| # | Lapisan | Perilaku |
|---|---|---|
| L1 | Token bucket | Menjaga laju sesuai konfigurasi per token |
| L2 | Adaptif header | Membaca `x-ratelimit-remaining`; sisa < 10% → perlambat; hampir habis → tunggu sampai `reset` |
| L3 | Retry 429 | Hormati `Retry-After`, backoff eksponensial, maksimum 6 percobaan |
| L4 | Circuit breaker | 429 berulang → parkir kredensial sementara (default 120 detik); KLPD lain tetap berjalan |

**Penerimaan:**
- [ ] Dua KLPD paralel tidak saling mengurangi kuota
- [ ] `429` tidak menyebabkan job gagal permanen
- [ ] Semua ambang dapat diatur via konfigurasi
- [ ] Ada unit test untuk L1–L4

---

### F8 — Multi-KLPD

- Kredensial disimpan **di luar `.env`**, pada berkas terpisah berformat JSON:

```json
{
  "D462": { "token": "inprc...", "label": "Nama instansi" },
  "D472": { "token": "",         "label": "Belum ada token" }
}
```

- Berkas kredensial **wajib** masuk `.gitignore`.
- KLPD tanpa token **tetap terdaftar** namun dilewati, dilaporkan sebagai `KOSONG`.
- Jumlah KLPD diproses bersamaan dikonfigurasi (`workers`, default 4).
- Isolasi data antar KLPD dijamin oleh `_kode_klpd`.

**Penerimaan:**
- [ ] Semua KLPD terdaftar dilaporkan (siap / tanpa token)
- [ ] Tarik paralel tidak menimbulkan race condition pada tabel
- [ ] Ringkasan per KLPD: jumlah endpoint OK / KOSONG / GAGAL

---

### F9 — Deteksi Blokir & Diagnosa Kegagalan

Kegagalan akses harus dibedakan penyebabnya.

| Kondisi | Indikator | Status | Pesan |
|---|---|---|---|
| Token tidak berlaku | `403` + body JSON | `FORBIDDEN` | `Ditolak: token tidak valid untuk KLPD ini` |
| Diblokir pengaman | `403`/`503` + `Content-Type: text/html` atau body memuat penanda blokir | `FORBIDDEN` | `Diblokir: akses ditolak oleh pengaman jaringan, ulangi nanti` |
| Endpoint belum diizinkan | `403` berulang khusus endpoint tertentu | `FORBIDDEN` | `Akses endpoint belum diizinkan` |

**Penerimaan:**
- [ ] Blokir jaringan tidak pernah dilaporkan sebagai "token salah"
- [ ] Pesan pada log membedakan ketiga kondisi
- [ ] Token tidak pernah muncul mentah di log (bentuk tersamar: `inprc40f...fe02`)

---

### F10 — Log Tarikan

Satu baris per kombinasi (endpoint × KLPD × tahun).

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint | PK |
| `created_at` | timestamptz | Waktu mulai |
| `endpoint` | varchar | Nama endpoint |
| `grup` | varchar | Grup endpoint |
| `api` | varchar | `v1` / `legacy` |
| `kode_klpd` | varchar(16) | |
| `tahun` | smallint | |
| `status` | varchar(16) | `OK` / `KOSONG` / `GAGAL` / `FORBIDDEN` / `NONAKTIF` |
| `rows` | integer | Baris tersimpan |
| `source` | varchar(128) | Endpoint asal (fallback) |
| `pages` | integer | Jumlah request |
| `durasi_ms` | integer | Durasi |
| `pesan` | varchar(255) | Alasan gagal |

**Penerimaan:**
- [ ] Tepat satu baris per endpoint per tarik
- [ ] `pesan` dipotong 255 karakter tanpa error
- [ ] Log dapat difilter per KLPD, tahun, status, endpoint

---

### F11 — Realisasi RUP

Bagian paling kritis. Menentukan paket RUP mana yang **sudah dipakai** dalam proses pengadaan.

#### F11.1 Definisi

Realisasi RUP = himpunan paket dari tabel `rec_rup_paket_penyedia` yang kode RUP-nya muncul sebagai referensi pada salah satu sumber proses pengadaan, beserta penanda sumber dan sifat keterkaitannya (langsung atau warisan).

#### F11.2 Sumber Proses

| Label `sumber` | Tabel | Kolom referensi |
|---|---|---|
| `tender` | `rec_tender_pengumuman` | `kd_rup` |
| `non-tender` | `rec_tender_non_tender_pengumuman` | `kd_rup` |
| `pencatatan-non-tender` | `rec_tender_pencatatan_non_tender` | `kd_rup` |
| `e-katalog` | `rec_ekatalog_paket_e_purchasing` | `rup_code` |

#### F11.3 Tantangan A — Referensi Multi-Nilai

Satu kolom referensi dapat memuat **lebih dari satu kode RUP** yang dipisahkan titik koma, contoh: `"123450;678900"`. Join langsung tidak dimungkinkan.

**Penyelesaian — wajib di sisi database (PostgreSQL):**

```sql
SELECT trim(kode) AS kd_rup
FROM rec_tender_pengumuman,
     unnest(string_to_array(kd_rup, ';')) AS kode
WHERE kd_rup IS NOT NULL AND kd_rup <> '';
```

Eksploitasi dilakukan di query, bukan dengan memuat seluruh kolom ke memori aplikasi. Konsekuensinya: ribuan baris dengan referensi `;` selesai dalam satu pernyataan tanpa loop PHP.

**Penerimaan:**
- [ ] Setiap kode hasil eksploitasi dinormalisasi sebelum digunakan
- [ ] Nilai kosong, `null`, dan spasi berlebih dibuang
- [ ] Satu baris sumber dengan 3 kode menghasilkan 3 entri proses

#### F11.4 Tantangan B — Normalisasi Kode

Tipe kolom kode RUP berbeda antar tabel: `integer`, `text`, dan `numeric(38,10)`. PostgreSQL dapat mengembalikan nilai desimal untuk kode yang sebenarnya bilangan bulat.

**Aturan `KodeNormalizer::normalize()`:**

| Masukan | Keluaran |
|---|---|
| `null` | `""` |
| `123.0` (float bulat) | `"123"` |
| `Decimal("123.0000000000")` | `"123"` |
| `" 123 "` | `"123"` |
| `"123.5"` (bukan bulat) | `"123.5"` |

**Penerimaan:**
- [ ] Kode dari keempat sumber dapat dicocokkan dengan `rec_rup_paket_penyedia` pada PostgreSQL, termasuk kode bertipe `numeric` yang dikembalikan sebagai `Decimal`
- [ ] Unit test mencakup seluruh kasus pada tabel di atas

#### F11.5 Tantangan C — Pewarisan Kaji Ulang

Setiap perbaikan RUP menghasilkan **RUP baru**; jejaknya tercatat pada `rec_rup_history_kaji_ulang` dengan pasangan `kd_rup_lama` → `kd_rup_baru`.

**Aturan bisnis:**

| # | Aturan |
|---|---|
| B1 | Hanya `jenis_revisi = 'SATUKESATU'` yang menghasilkan pewarisan. Jenis revisi lain belum dapat dipastikan mewarisi proses |
| B2 | Hanya `jenis_paket = 'PENYEDIA'` |
| B3 | Pewarisan bersifat **transitif**, bukan satu langkah: telusuri hingga ditemukan leluhur yang punya proses |
| B4 | Bila RUP baru **punya proses sendiri**, gunakan prosesnya sendiri — jangan warisi leluhur |
| B5 | Bila tidak punya proses sendiri, warisi proses **leluhur terdekat** |
| B6 | Pasangan dengan `kd_rup_lama = kd_rup_baru` diabaikan |
| B7 | Kedalaman penelusuran dibatasi 30 level (proteksi siklus data) |

**Algoritma — wajib Breadth-First Search:**

```
untuk setiap kd_rup pada rec_rup_paket_penyedia:
    bila kd_rup ∈ peta_proses:
        catat (kd_rup, sumber, jenis = langsung, depth = 0)
        lanjut
    antrean ← [kd_rup]; dikunjungi ← {kd_rup}
    depth ← 0
    selama antrean tidak kosong dan depth < 30:
        depth ← depth + 1
        level_berikut ← []
        untuk setiap simpul pada antrean:
            untuk setiap leluhur pada rantai[simpul]:
                bila leluhur ∈ dikunjungi: lanjut
                tandai dikunjungi
                bila leluhur ∈ peta_proses:
                    catat (kd_rup, warisi dari leluhur, jenis = warisan, depth)
                    HENTIKAN penelusuran untuk kd_rup ini
                selain itu:
                    tambahkan leluhur ke level_berikut
        antrean ← level_berikut
```

> **DFS tidak boleh digunakan.** Penelusuran mendalam akan menemukan leluhur yang lebih jauh lebih dulu dan menghasilkan nilai `depth` yang salah.

**Penerimaan:**
- [ ] Rantai 2 level dan 3 level menghasilkan `depth` sesuai jarak sebenarnya
- [ ] RUP baru yang punya proses sendiri tidak dianggap warisan
- [ ] Siklus pada data tidak menyebabkan loop tak berujung
- [ ] Uji dengan rantai buatan minimal 5 level

#### F11.6 Materialisasi

**Tabel `ref_rup_proses`** — RUP yang punya proses sendiri

| Kolom | Tipe | Kunci |
|---|---|---|
| `kd_rup` | varchar(64) | PK |
| `sumber` | varchar(32) | PK |
| `updated_at` | timestamptz | |

**Tabel `ref_rup_warisan`** — RUP yang mewarisi proses

| Kolom | Tipe | Keterangan |
|---|---|---|
| `kd_rup` | varchar(64) | PK bersama |
| `sumber` | varchar(32) | PK bersama |
| `dari_rup` | varchar(64) | Leluhur yang memiliki proses |
| `depth` | smallint | Jarak rantai (≥ 1) |
| `updated_at` | timestamptz | |

Kedua tabel di-**replace penuh** setiap kali dihitung, masing-masing dalam satu transaksi (`DELETE` seluruh isi → `INSERT` hasil baru).

**Penerimaan:**
- [ ] Perhitungan ulang menghasilkan isi identik bila data sumber tidak berubah
- [ ] Kegagalan di tengah tidak menyisakan tabel kosong (rollback)
- [ ] Satu kode RUP dapat muncul benar pada kedua tabel dengan sumber berbeda

#### F11.7 View `v_realisasi_rup`

Dibangun menggunakan `CREATE OR REPLACE VIEW` (didukung penuh PostgreSQL) agar pembaruan tidak menghapus dependensi. Bila daftar kolom berubah, lakukan `DROP VIEW IF EXISTS` lalu `CREATE VIEW`.

| Kolom | Sumber |
|---|---|
| `kd_rup` | Kode RUP dari `rec_rup_paket_penyedia`, dinormalisasi ke string bilangan bulat |
| `nama_paket`, `pagu`, `kd_satker`, `nama_satker`, `metode_pengadaan`, `tahun_anggaran` | `rec_rup_paket_penyedia` |
| `sumber` | `tender` / `non-tender` / `pencatatan-non-tender` / `e-katalog` |
| `jenis` | `langsung` (depth 0) atau `warisan` (depth > 0) |
| `dari_rup` | Leluhur (null bila langsung) |
| `depth` | Kedalaman rantai |

Logika: gabungkan `ref_rup_proses` (depth 0) dan `ref_rup_warisan` ke dalam satu himpunan `sumber_paket`, lalu join ke `rec_rup_paket_penyedia` dengan kode yang telah dinormalisasi di kedua sisi.

**Penerimaan:**
- [ ] Satu paket dapat muncul beberapa kali dengan `sumber` berbeda
- [ ] `jenis` konsisten dengan nilai `depth`
- [ ] View dapat dibaca aplikasi dashboard tanpa transformasi tambahan

#### F11.8 Urutan Eksekusi

Realisasi dibangun **setelah** seluruh KLPD selesai ditarik, sekali saja — bukan per KLPD — karena view bersifat global lintas KLPD.

**Penerimaan:**
- [ ] Kegagalan membangun realisasi tidak membatalkan data tarik yang sudah tersimpan
- [ ] Dapat dijalankan ulang kapan saja

---

### F12 — Antarmuka Operasional (Artisan Command)

| Command | Fungsi |
|---|---|
| `inaproc:pull` | Menarik data. Tanpa argumen → mode interaktif (menanyakan KLPD → tahun → pilihan endpoint → konfirmasi) |
| `inaproc:pull --klpd=D462 --tahun=2026` | Menarik seluruh endpoint aktif untuk satu KLPD |
| `inaproc:pull --all --tahun=2026` | Menarik seluruh KLPD terdaftar secara paralel |
| `inaproc:pull --dry-run` | Menampilkan rencana tanpa memanggil API |
| `inaproc:pull --refresh-derived` | Mengabaikan cache F1.3; menarik ulang seluruh nilai turunan |
| `inaproc:probe --klpd=D462 --tahun=2026` | Menguji akses tiap endpoint (1 request per endpoint) |
| `inaproc:validate-tokens` | Memeriksa validitas seluruh token terdaftar |
| `inaproc:build-realisasi` | Menghitung ulang `ref_rup_proses`, `ref_rup_warisan`, dan view |

**Penerimaan:**
- [ ] Mode interaktif berjalan tanpa argumen
- [ ] `--dry-run` tidak menyentuh API
- [ ] Exit code ≠ 0 bila terdapat kegagalan (untuk monitoring)
- [ ] Ringkasan akhir menampilkan jumlah OK / KOSONG / GAGAL / FORBIDDEN

---

## 7. Kebutuhan Non-Fungsional

| Aspek | Target |
|---|---|
| Idempotensi | Tarik 2× pada KLPD+tahun sama → kondisi database identik |
| Keamanan data | Kegagalan di titik mana pun tidak menghapus data lama |
| Volume | ±350.000 baris per KLPD per tahun; 20 KLPD ≈ 7 juta baris |
| Ukuran penyimpanan | ±175 MB per KLPD/tahun; ±3,5 GB untuk 20 KLPD |
| Durasi tarik | Ditentukan rate limit API (puluhan menit per KLPD), bukan CPU |
| Memori proses tarik | < 512 MB per worker |
| Concurrency | Minimal 4 KLPD paralel tanpa saling mengganggu |
| Observabilitas | Seluruh keputusan tercatat di `pull_log` |
| Kerahasiaan | Token tidak pernah tercatat mentah; berkas kredensial di luar VCS |
| Basis data | PostgreSQL 18 — satu-satunya mesin database yang didukung |

---

## 8. Skema Database

### 8.1 Tabel Sistem

```sql
-- Kredensial KLPD (alternatif berkas JSON; pilih salah satu, jangan keduanya)
CREATE TABLE klpd_credentials (
    kode             VARCHAR(16) PRIMARY KEY,
    label            VARCHAR(128),
    token_encrypted  TEXT NOT NULL,
    is_active        BOOLEAN NOT NULL DEFAULT TRUE,
    updated_at       TIMESTAMPTZ
);

-- Registry endpoint
CREATE TABLE endpoints (
    name    VARCHAR(64) PRIMARY KEY,
    path    VARCHAR(255) NOT NULL,
    api     VARCHAR(16)  NOT NULL CHECK (api IN ('v1','legacy')),
    grup    VARCHAR(32)  NOT NULL,
    scope   VARCHAR(16)  NOT NULL CHECK (scope IN ('klpd','derived','tahun')),
    active  BOOLEAN      NOT NULL DEFAULT TRUE,
    urutan  SMALLINT     NOT NULL DEFAULT 0
);

-- Log tarikan
CREATE TABLE pull_log (
    id          BIGSERIAL PRIMARY KEY,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now(),
    endpoint    VARCHAR(64)  NOT NULL,
    grup        VARCHAR(32),
    api         VARCHAR(16),
    kode_klpd   VARCHAR(16)  NOT NULL,
    tahun       SMALLINT     NOT NULL,
    status      VARCHAR(16)  NOT NULL,
    rows        INTEGER      NOT NULL DEFAULT 0,
    source      VARCHAR(128),
    pages       INTEGER      NOT NULL DEFAULT 0,
    durasi_ms   INTEGER,
    pesan       VARCHAR(255)
);
CREATE INDEX ON pull_log (kode_klpd, tahun);
CREATE INDEX ON pull_log (status);
CREATE INDEX ON pull_log (created_at DESC);

-- Materialisasi realisasi
CREATE TABLE ref_rup_proses (
    kd_rup     VARCHAR(64) NOT NULL,
    sumber     VARCHAR(32) NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL,
    PRIMARY KEY (kd_rup, sumber)
);

CREATE TABLE ref_rup_warisan (
    kd_rup     VARCHAR(64) NOT NULL,
    sumber     VARCHAR(32) NOT NULL,
    dari_rup   VARCHAR(64) NOT NULL,
    depth      SMALLINT    NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL,
    PRIMARY KEY (kd_rup, sumber)
);

-- Penanda nilai turunan yang sudah diambil (F1.3).
-- Diperlukan karena tabel data turunan dapat kosong untuk nilai tertentu
-- (mis. penyedia tanpa detail), sehingga keberadaan baris tidak bisa
-- dijadikan satu-satunya bukti.
CREATE TABLE derived_progress (
    endpoint    VARCHAR(64) NOT NULL,
    kode_klpd   VARCHAR(16) NOT NULL,
    tahun       SMALLINT    NOT NULL,
    nilai_kunci VARCHAR(128) NOT NULL,
    hasil_rows  INTEGER     NOT NULL DEFAULT 0,
    fetched_at  TIMESTAMPTZ NOT NULL,
    PRIMARY KEY (endpoint, kode_klpd, tahun, nilai_kunci)
);
CREATE INDEX ON derived_progress (endpoint, kode_klpd, tahun);
```

### 8.2 Tabel Data (Dibuat Runtime)

```sql
CREATE TABLE rec_rup_paket_penyedia (
    _row_id     BIGSERIAL PRIMARY KEY,
    _kode_klpd  VARCHAR(16) NOT NULL,
    _tahun      SMALLINT    NOT NULL,
    _source     VARCHAR(128),
    _fetched_at TIMESTAMPTZ NOT NULL,
    -- kolom dinamis hasil deteksi respons API
    kd_rup      NUMERIC,
    nama_paket  TEXT
);
CREATE INDEX ON rec_rup_paket_penyedia (_kode_klpd, _tahun);
```

### 8.3 Keputusan Arsitektur: Tabel per Endpoint vs Tabel Tunggal + JSONB

**Opsi A — Tabel per endpoint (direkomendasikan)**

| Kelebihan | Kekurangan |
|---|---|
| Query per kolom langsung dan cepat | Skema tidak tercatat di migration |
| View realisasi RUP sederhana | Evolusi kolom harus otomatis |
| Indeks tipe-spesifik | Tidak ada foreign key |

**Opsi B — Tabel tunggal `records` + kolom `data JSONB`**

| Kelebihan | Kekurangan |
|---|---|
| Satu migrasi untuk semua endpoint | Query per kolom butuh indeks ekspresi |
| Skema terlihat dan terkontrol | View realisasi RUP jauh lebih kompleks |
| Mudah menambah endpoint | Volume JSON memperbesar storage |

**Rekomendasi:** Opsi A. Skema `rec_*` dikelola aplikasi melalui `SchemaManager`, dan didokumentasikan sebagai skema dinamis yang tidak memakai migration manual.

---

## 9. Rencana Implementasi

| Fase | Lingkup | Kriteria Keluar |
|---|---|---|
| **1. Fondasi** | Setup Laravel, koneksi PostgreSQL, migration tabel sistem, seeder 65 endpoint | `endpoints` berisi 65 baris; test suite berjalan di PostgreSQL |
| **2. Lapisan HTTP** | `ApiClient`, `Paginator`, `RateLimiter`, deteksi blokir | Unit test F3–F5 & F7 lulus dengan HTTP fake |
| **3. Penulisan Data** | `SchemaManager`, `TableWriter` | 1 endpoint dapat ditarik 2× tanpa duplikasi |
| **4. Orkestrasi** | `PullService`, fallback, `pull_log`, command interaktif | Tarik penuh 1 KLPD, log lengkap |
| **5. Realisasi RUP** | `ProsesCollector`, `WarisanResolver`, `ViewBuilder` | Hasil lolos validasi dataset acuan (Bab 12) |
| **6. Multi-KLPD** | Berkas kredensial, job paralel, laporan | 2 KLPD paralel, data terisolasi |
| **7. Pengerasan** | Uji beban, dokumentasi operasional, prosedur pemulihan | Tidak ada deviasi terhadap acuan |

Fase 1–5 merupakan pekerjaan inti; fase 6–7 relatif ringan.

**Aturan lingkungan pengujian:**

- Seluruh test (unit maupun integrasi) berjalan terhadap instance PostgreSQL.
- Gunakan basis data terpisah (`inaproc_test`) dan `RefreshDatabase`.
- Dilarang menambah konfigurasi driver database lain. Test yang hanya lulus
  di atas SQLite dianggap tidak sah, karena logika kunci (eksploitasi `;`,
  cast `numeric`, view) memakai fitur khas PostgreSQL.

---

## 10. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Hasil perhitungan menyimpang dari acuan | Tinggi | Bandingkan dengan dataset acuan pada setiap fase |
| Pengaman jaringan memblokir pengujian berulang | Sedang | Uji dengan HTTP fake; batasi request nyata; bedakan status blokir |
| Token belum berhak atas suatu endpoint | Rendah | Skip + catat `FORBIDDEN`, jangan gagalkan proses |
| Satu tabel mendominasi volume data | Sedang | Pastikan penulisan batch; pantau durasi per endpoint |
| Data legacy menggandakan volume | Sedang | Putuskan di awal apakah legacy ikut ditarik |
| Skema dinamis sulit dilacak | Sedang | Dokumentasi + command verifikasi skema |
| Perbedaan representasi tipe `numeric` vs `text` antar tabel | Sedang | `KodeNormalizer` + cast eksplisit di query; uji dengan data bertipe campuran |
| Salah tafsir rate limit | Sedang | Unit test untuk 429, sisa kuota, dan breaker |
| Perubahan format respons API | Sedang | Validasi struktur respons; gagal cepat dengan pesan jelas |

---

## 11. Definisi Selesai

- [ ] Seluruh 61 endpoint aktif dapat ditarik untuk minimal 2 KLPD
- [ ] Tarik ulang tidak menimbulkan duplikasi
- [ ] Kegagalan tidak pernah menghapus data lama (dibuktikan dengan injeksi error)
- [ ] Empat aturan fallback terimplementasi dan teruji
- [ ] `403` di-skip tanpa menghentikan proses
- [ ] `pull_log` terisi untuk setiap endpoint × KLPD × tahun
- [ ] Hasil realisasi RUP identik dengan dataset acuan
- [ ] Token tidak pernah tercatat mentah di log
- [ ] Endpoint turunan tidak menarik ulang nilai yang sudah tersimpan (F1.3)
- [ ] Data turunan hasil tarikan yang terputus tidak hilang saat run berikutnya
- [ ] Dokumentasi operasional tersedia (tarik, penjadwalan, pemulihan)

---

## 12. Dataset Acuan (Validasi)

Dataset acuan adalah hasil tarik **satu KLPD untuk satu tahun anggaran** yang telah diverifikasi konsisten secara internal. Gunakan sebagai pembanding pada setiap fase pengembangan.

**Skala data acuan:**

| Metrik | Nilai |
|---|---|
| Tabel data | 55 |
| Total baris | 348.447 |
| Endpoint terdaftar | 65 (61 aktif) |
| Rentang karakter terlebar pada satu tabel | 58 kolom |

**Hasil realisasi RUP acuan:**

| Item | Nilai |
|---|---|
| `ref_rup_proses` | **3.362** baris |
| `ref_rup_warisan` | **291** baris |
| `v_realisasi_rup` | **3.637** baris |

**Rincian `v_realisasi_rup` per sumber:**

| Sumber | Langsung | Warisan |
|---|---:|---:|
| e-katalog | 1.653 | 222 |
| non-tender | 1.598 | 62 |
| pencatatan-non-tender | 5 | 0 |
| tender | 90 | 7 |

**Aturan validasi:** setiap selisih, sekecil apa pun, harus dapat dijelaskan sumbernya sebelum fase dinyatakan selesai.

---

## 13. Referensi API

**Dokumentasi:** `https://data.inaproc.id/docs/spesifikasi-api`

**Autentikasi:** token pada header permintaan. Satu token hanya berlaku untuk satu Kode KLPD.

**Keluarga endpoint:**

| Keluarga | Prefix | Pola akses |
|---|---|---|
| v1 | `/api/v1/*` | Pagination berbasis cursor, maksimum 1000 baris per halaman |
| legacy | `/api/legacy/*` | Satu request mengembalikan seluruh data |

**Parameter umum:** `kode_klpd`, `tahun`, `limit`, `cursor` (khusus v1).

**Contoh respons v1:**

```json
{
  "meta": {
    "cursor": "eyJpZCI6LTc3MzMyNDMwOTQxMzI2NjgxNzF9",
    "has_more": true,
    "limit": 1000,
    "last_update_ref": "45f85ca6-..."
  },
  "success": true,
  "data": []
}
```

---

## Lampiran A — Daftar Lengkap Endpoint

Ini adalah **sumber kebenaran** untuk isi `EndpointSeeder`. Total **65 endpoint**, **61 aktif**, **4 nonaktif**.

Kolom `Tabel` diturunkan otomatis menurut aturan F2 (`rec_<grup>_<slug>` untuk v1, `rec_legacy_<grup>_<slug>` untuk legacy) — tidak perlu diketik manual.

Legenda kolom **Aktif**: `✔` = ikut tarik otomatis · `✘` = dilewati (lihat catatan di akhir lampiran).

### A.1 — Ringkasan

| Keluarga | Grup | Jumlah | Aktif |
|---|---|---:|---:|
| v1 | ekatalog | 5 | 2 |
| v1 | master | 1 | 1 |
| v1 | rup | 10 | 10 |
| v1 | tender | 16 | 16 |
| legacy | bela | 1 | 1 |
| legacy | ekatalog | 2 | 1 |
| legacy | rup | 11 | 11 |
| legacy | tender | 20 | 20 |
| **Total** | | **65** | **61** |

### A.2 — `/api/v1` — Grup `ekatalog` (5)

| Aktif | Nama | Path | Scope | Tabel |
|:---:|---|---|---|---|
| ✔ | `ekatalog-e-purchasing-by-produk` | `/api/v1/ekatalog/e-purchasing-by-produk` | klpd | `rec_ekatalog_e_purchasing_by_produk` |
| ✘ | `ekatalog-list-kategori-produk` | `/api/v1/ekatalog/list-kategori-produk` | derived | — |
| ✘ | `ekatalog-list-produk-penyedia` | `/api/v1/ekatalog/list-produk-penyedia` | derived | — |
| ✔ | `ekatalog-paket-e-purchasing` | `/api/v1/ekatalog/paket-e-purchasing` | klpd | `rec_ekatalog_paket_e_purchasing` |
| ✘ | `ekatalog-penyedia-detail` | `/api/v1/ekatalog/penyedia-detail` | derived | — |

### A.3 — `/api/v1` — Grup `master` (1)

| Aktif | Nama | Path | Scope | Tabel |
|:---:|---|---|---|---|
| ✔ | `master-program` | `/api/v1/master/program` | tahun | `rec_master_program` |

### A.4 — `/api/v1` — Grup `rup` (10)

| Aktif | Nama | Path | Scope | Tabel |
|:---:|---|---|---|---|
| ✔ | `history-kaji-ulang` | `/api/v1/rup/history-kaji-ulang` | klpd | `rec_rup_history_kaji_ulang` |
| ✔ | `master-satker` | `/api/v1/rup/master-satker` | klpd | `rec_rup_master_satker` |
| ✔ | `paket-anggaran-penyedia` | `/api/v1/rup/paket-anggaran-penyedia` | klpd | `rec_rup_paket_anggaran_penyedia` |
| ✔ | `paket-anggaran-swakelola` | `/api/v1/rup/paket-anggaran-swakelola` | klpd | `rec_rup_paket_anggaran_swakelola` |
| ✔ | `paket-penyedia` | `/api/v1/rup/paket-penyedia` | klpd | `rec_rup_paket_penyedia` |
| ✔ | `paket-penyedia-terumumkan` | `/api/v1/rup/paket-penyedia-terumumkan` | klpd | `rec_rup_paket_penyedia_terumumkan` |
| ✔ | `paket-swakelola` | `/api/v1/rup/paket-swakelola` | klpd | `rec_rup_paket_swakelola` |
| ✔ | `paket-swakelola-terumumkan` | `/api/v1/rup/paket-swakelola-terumumkan` | klpd | `rec_rup_paket_swakelola_terumumkan` |
| ✔ | `program-master` | `/api/v1/rup/program-master` | klpd | `rec_rup_program_master` |

> **Catatan A.4:** `/api/v1/rup/program` **tidak** termasuk daftar ini — sengaja dikecualikan (lihat Bab 3.2).

### A.5 — `/api/v1` — Grup `tender` (16)

| Aktif | Nama | Path | Scope | Tabel |
|:---:|---|---|---|---|
| ✔ | `jadwal-tahapan-non-tender` | `/api/v1/tender/jadwal-tahapan-non-tender` | klpd | `rec_tender_jadwal_tahapan_non_tender` |
| ✔ | `jadwal-tahapan-tender` | `/api/v1/tender/jadwal-tahapan-tender` | klpd | `rec_tender_jadwal_tahapan_tender` |
| ✔ | `non-tender-ekontrak` | `/api/v1/tender/non-tender-ekontrak` | klpd | `rec_tender_non_tender_ekontrak` |
| ✔ | `non-tender-ekontrak-kontrak` | `/api/v1/tender/non-tender-ekontrak-kontrak` | klpd | `rec_tender_non_tender_ekontrak_kontrak` |
| ✔ | `non-tender-pengumuman` | `/api/v1/tender/non-tender-pengumuman` | klpd | `rec_tender_non_tender_pengumuman` |
| ✔ | `non-tender-selesai` | `/api/v1/tender/non-tender-selesai` | klpd | `rec_tender_non_tender_selesai` |
| ✔ | `pencatatan-non-tender` | `/api/v1/tender/pencatatan-non-tender` | klpd | `rec_tender_pencatatan_non_tender` |
| ✔ | `pencatatan-non-tender-realisasi` | `/api/v1/tender/pencatatan-non-tender-realisasi` | klpd | `rec_tender_pencatatan_non_tender_realisasi` |
| ✔ | `pencatatan-swakelola` | `/api/v1/tender/pencatatan-swakelola` | klpd | `rec_tender_pencatatan_swakelola` |
| ✔ | `pencatatan-swakelola-realisasi` | `/api/v1/tender/pencatatan-swakelola-realisasi` | klpd | `rec_tender_pencatatan_swakelola_realisasi` |
| ✔ | `pengumuman` | `/api/v1/tender/pengumuman` | klpd | `rec_tender_pengumuman` |
| ✔ | `peserta-tender` | `/api/v1/tender/peserta-tender` | klpd | `rec_tender_peserta_tender` |
| ✔ | `tender-ekontrak` | `/api/v1/tender/tender-ekontrak` | klpd | `rec_tender_tender_ekontrak` |
| ✔ | `tender-ekontrak-kontrak` | `/api/v1/tender/tender-ekontrak-kontrak` | klpd | `rec_tender_tender_ekontrak_kontrak` |
| ✔ | `tender-selesai` | `/api/v1/tender/tender-selesai` | klpd | `rec_tender_tender_selesai` |
| ✔ | `tender-selesai-nilai` | `/api/v1/tender/tender-selesai-nilai` | klpd | `rec_tender_tender_selesai_nilai` |

### A.6 — `/api/legacy` — Grup `bela` (1)

| Aktif | Nama | Path | Scope | Tabel |
|:---:|---|---|---|---|
| ✔ | `legacy-bela-toko-daring-realisasi` | `/api/legacy/bela/toko-daring-realisasi` | klpd | `rec_legacy_bela_toko_daring_realisasi` |

### A.7 — `/api/legacy` — Grup `ekatalog` (2)

| Aktif | Nama | Path | Scope | Tabel |
|:---:|---|---|---|---|
| ✔ | `legacy-ekatalog-paket-e-purchasing` | `/api/legacy/ekatalog/paket-e-purchasing` | klpd | `rec_legacy_ekatalog_paket_e_purchasing` |
| ✘ | `legacy-ekatalog-penyedia-detail` | `/api/legacy/ekatalog/penyedia-detail` | derived | — |

### A.8 — `/api/legacy` — Grup `rup` (11)

| Aktif | Nama | Path | Scope | Tabel |
|:---:|---|---|---|---|
| ✔ | `legacy-rup-kegiatan-master` | `/api/legacy/rup/kegiatan-master` | klpd | `rec_legacy_rup_kegiatan_master` |
| ✔ | `legacy-rup-master-satker` | `/api/legacy/rup/master-satker` | klpd | `rec_legacy_rup_master_satker` |
| ✔ | `legacy-rup-paket-anggaran-penyedia` | `/api/legacy/rup/paket-anggaran-penyedia` | klpd | `rec_legacy_rup_paket_anggaran_penyedia` |
| ✔ | `legacy-rup-paket-anggaran-swakelola` | `/api/legacy/rup/paket-anggaran-swakelola` | klpd | `rec_legacy_rup_paket_anggaran_swakelola` |
| ✔ | `legacy-rup-paket-penyedia-lokasi` | `/api/legacy/rup/paket-penyedia-lokasi` | klpd | `rec_legacy_rup_paket_penyedia_lokasi` |
| ✔ | `legacy-rup-paket-penyedia-terumumkan` | `/api/legacy/rup/paket-penyedia-terumumkan` | klpd | `rec_legacy_rup_paket_penyedia_terumumkan` |
| ✔ | `legacy-rup-paket-swakelola-lokasi` | `/api/legacy/rup/paket-swakelola-lokasi` | klpd | `rec_legacy_rup_paket_swakelola_lokasi` |
| ✔ | `legacy-rup-paket-swakelola-terumumkan` | `/api/legacy/rup/paket-swakelola-terumumkan` | klpd | `rec_legacy_rup_paket_swakelola_terumumkan` |
| ✔ | `legacy-rup-program-master` | `/api/legacy/rup/program-master` | klpd | `rec_legacy_rup_program_master` |
| ✔ | `legacy-rup-struktur-anggaran-pd` | `/api/legacy/rup/struktur-anggaran-pd` | klpd | `rec_legacy_rup_struktur_anggaran_pd` |
| ✔ | `legacy-rup-sub-kegiatan-master` | `/api/legacy/rup/sub-kegiatan-master` | klpd | `rec_legacy_rup_sub_kegiatan_master` |

### A.9 — `/api/legacy` — Grup `tender` (20)

| Aktif | Nama | Path | Scope | Tabel |
|:---:|---|---|---|---|
| ✔ | `legacy-tender-jadwal-tahapan-non-tender` | `/api/legacy/tender/jadwal-tahapan-non-tender` | klpd | `rec_legacy_tender_jadwal_tahapan_non_tender` |
| ✔ | `legacy-tender-jadwal-tahapan-tender` | `/api/legacy/tender/jadwal-tahapan-tender` | klpd | `rec_legacy_tender_jadwal_tahapan_tender` |
| ✔ | `legacy-tender-non-tender-ekontrak-bapbast` | `/api/legacy/tender/non-tender-ekontrak-bapbast` | klpd | `rec_legacy_tender_non_tender_ekontrak_bapbast` |
| ✔ | `legacy-tender-non-tender-ekontrak-kontrak` | `/api/legacy/tender/non-tender-ekontrak-kontrak` | klpd | `rec_legacy_tender_non_tender_ekontrak_kontrak` |
| ✔ | `legacy-tender-non-tender-ekontrak-spmkspp` | `/api/legacy/tender/non-tender-ekontrak-spmkspp` | klpd | `rec_legacy_tender_non_tender_ekontrak_spmkspp` |
| ✔ | `legacy-tender-non-tender-ekontrak-sppbj` | `/api/legacy/tender/non-tender-ekontrak-sppbj` | klpd | `rec_legacy_tender_non_tender_ekontrak_sppbj` |
| ✔ | `legacy-tender-non-tender-pengumuman` | `/api/legacy/tender/non-tender-pengumuman` | klpd | `rec_legacy_tender_non_tender_pengumuman` |
| ✔ | `legacy-tender-non-tender-selesai` | `/api/legacy/tender/non-tender-selesai` | klpd | `rec_legacy_tender_non_tender_selesai` |
| ✔ | `legacy-tender-pencatatan-non-tender` | `/api/legacy/tender/pencatatan-non-tender` | klpd | `rec_legacy_tender_pencatatan_non_tender` |
| ✔ | `legacy-tender-pencatatan-non-tender-realisasi` | `/api/legacy/tender/pencatatan-non-tender-realisasi` | klpd | `rec_legacy_tender_pencatatan_non_tender_realisasi` |
| ✔ | `legacy-tender-pencatatan-swakelola` | `/api/legacy/tender/pencatatan-swakelola` | klpd | `rec_legacy_tender_pencatatan_swakelola` |
| ✔ | `legacy-tender-pencatatan-swakelola-realisasi` | `/api/legacy/tender/pencatatan-swakelola-realisasi` | klpd | `rec_legacy_tender_pencatatan_swakelola_realisasi` |
| ✔ | `legacy-tender-pengumuman` | `/api/legacy/tender/pengumuman` | klpd | `rec_legacy_tender_pengumuman` |
| ✔ | `legacy-tender-peserta-tender` | `/api/legacy/tender/peserta-tender` | klpd | `rec_legacy_tender_peserta_tender` |
| ✔ | `legacy-tender-tender-ekontrak-bapbast` | `/api/legacy/tender/tender-ekontrak-bapbast` | klpd | `rec_legacy_tender_tender_ekontrak_bapbast` |
| ✔ | `legacy-tender-tender-ekontrak-kontrak` | `/api/legacy/tender/tender-ekontrak-kontrak` | klpd | `rec_legacy_tender_tender_ekontrak_kontrak` |
| ✔ | `legacy-tender-tender-ekontrak-spmkspp` | `/api/legacy/tender/tender-ekontrak-spmkspp` | klpd | `rec_legacy_tender_tender_ekontrak_spmkspp` |
| ✔ | `legacy-tender-tender-ekontrak-sppbj` | `/api/legacy/tender/tender-ekontrak-sppbj` | klpd | `rec_legacy_tender_tender_ekontrak_sppbj` |
| ✔ | `legacy-tender-tender-selesai` | `/api/legacy/tender/tender-selesai` | klpd | `rec_legacy_tender_tender_selesai` |
| ✔ | `legacy-tender-tender-selesai-nilai` | `/api/legacy/tender/tender-selesai-nilai` | klpd | `rec_legacy_tender_tender_selesai_nilai` |

### A.10 — Endpoint Nonaktif (4)

| Nama | Path | Alasan nonaktif |
|---|---|---|
| `ekatalog-penyedia-detail` | `/api/v1/ekatalog/penyedia-detail` | Butuh parameter `kode_penyedia` dan hak akses khusus dari pengelola API |
| `ekatalog-list-produk-penyedia` | `/api/v1/ekatalog/list-produk-penyedia` | Butuh parameter turunan `kode_penyedia` |
| `ekatalog-list-kategori-produk` | `/api/v1/ekatalog/list-kategori-produk` | Drill-down berjenjang; level 1 tanpa parameter tetap memerlukan keputusan bisnis |
| `legacy-ekatalog-penyedia-detail` | `/api/legacy/ekatalog/penyedia-detail` | Versi legacy dari `ekatalog-penyedia-detail` |

Endpoint nonaktif **tetap terdaftar** di `endpoints` dengan `active = false` agar dapat diaktifkan tanpa perubahan kode.

### A.11 — Ketentuan Seeder

- [ ] Seeder mengisi **65 baris**, 61 dengan `active = true`
- [ ] Kolom `tabel` **tidak** disimpan manual — diturunkan dari `api` + `grup` + `name` sesuai F2
- [ ] Seeder idempoten (`updateOrCreate` berdasarkan `name`) agar aman dijalankan ulang
- [ ] Perubahan daftar endpoint cukup lewat seeder, bukan kode pipeline
- [ ] Grup baru (`bela`, `master`) tetap mengikuti pola penamaan yang sama

---

## Lampiran B — Konfigurasi

| Kunci | Default | Keterangan |
|---|---|---|
| `INAPROC_BASE_URL` | `https://data.inaproc.id` | Alamat API |
| `INAPROC_TIMEOUT` | `120` | Timeout request (detik) |
| `INAPROC_PAGE_SIZE` | `1000` | Baris per halaman (v1) |
| `INAPROC_MAX_RETRY` | `4` | Retry untuk gangguan jaringan |
| `INAPROC_RETRY_BACKOFF` | `2` | Faktor backoff (detik) |
| `INAPROC_CHUNK_SIZE` | `500` | Baris per batch insert |
| `INAPROC_WORKERS` | `4` | KLPD diproses bersamaan |
| `RATE_LIMIT_V1_PER_MIN` | `300` | Batas v1 per token |
| `RATE_LIMIT_LEGACY_PER_MIN` | `90` | Batas legacy per token |
| `RATE_LIMIT_SLOW_THRESHOLD` | `300` | Sisa kuota sebelum memperlambat |
| `RATE_LIMIT_PAUSE_THRESHOLD` | `30` | Sisa kuota sebelum berhenti sejenak |
| `RATE_LIMIT_MAX_RETRY` | `6` | Percobaan ulang saat 429 |
| `RATE_LIMIT_COOLDOWN` | `120` | Lama parkir KLPD (detik) |
| `INAPROC_KLPD_FILE` | `storage/app/klpd_tokens.json` | Berkas kredensial |

**Koneksi basis data (PostgreSQL):**

| Kunci | Default | Keterangan |
|---|---|---|
| `DB_CONNECTION` | `pgsql` | Satu-satunya driver yang digunakan |
| `DB_HOST` | `127.0.0.1` | Host |
| `DB_PORT` | `5432` | Port |
| `DB_DATABASE` | `inaproc` | Nama basis data |
| `DB_USERNAME` | — | Pengguna |
| `DB_PASSWORD` | — | Kata sandi (URL-encode bila memuat `@` atau `#`) |

**Koneksi pengujian:** gunakan basis data PostgreSQL terpisah (mis. `inaproc_test`).
Tidak ada konfigurasi SQLite maupun driver alternatif.
