# Inaproc Data Pipeline

Backend layanan data untuk menarik data pengadaan dari **INAPROC API Gateway**
(`https://data.inaproc.id`) per **Kode KLPD** + **Tahun Anggaran**, lalu
mengolahnya menjadi **realisasi RUP**.

Implementasi dari `PRD.md` (versi 1.0). Sistem ini **layanan data tanpa
antarmuka** — semua operasi melalui Artisan command dan scheduler.

- Framework: Laravel 13
- Database: PostgreSQL 18 (satu-satunya driver)
- Queue: `database` (tanpa Redis)

---

## 1. Status Implementasi

| Fase | Lingkup | Status |
|---|---|---|
| 1. Fondasi | Migration tabel sistem, seeder 65 endpoint | ✅ 65 baris, 61 aktif |
| 2. Lapisan HTTP | `ApiClient`, `Paginator`, `RateLimiter`, deteksi blokir | ✅ |
| 3. Penulisan Data | `SchemaManager`, `TableWriter` | ✅ |
| 4. Orkestrasi | `PullService`, fallback, `pull_log`, command interaktif | ✅ |
| 5. Realisasi RUP | `ProsesCollector`, `WarisanResolver`, `ViewBuilder` | ✅ |
| 6. Multi-KLPD | Berkas kredensial, job paralel, laporan | ✅ |
| 7. Pengerasan | Dokumentasi operasional | ✅ |

Test suite: **84 test / 311 assertion**, seluruhnya berjalan di PostgreSQL
(`inaproc_test`), bukan SQLite.

---

## 2. Pemasangan

```bash
composer install
cp .env.example .env      # atau gunakan .env yang sudah ada
php artisan key:generate
php artisan migrate       # idempotent — aman untuk DB yang sudah berisi data
php artisan db:seed --class=EndpointSeeder
```

### Koneksi basis data

Nilai aktif ada di `.env`:

```
DB_CONNECTION=pgsql
DB_HOST=103.22.250.210
DB_PORT=54320
DB_DATABASE=inaproc
DB_USERNAME=sipedal
DB_PASSWORD=...
```

> **Catatan:** migration bersifat idempotent. Basis data `inaproc` sudah berisi
> tabel `rec_*` hasil pipeline sebelumnya beserta dataset acuan; migration
> hanya melengkapi yang belum ada dan tidak pernah men-drop kolom atau data.

### Kredensial KLPD

Token **tidak** disimpan di `.env`. Isi berkas terpisah:

```bash
cp storage/app/klpd_tokens.example.json storage/app/klpd_tokens.json
```

```json
{
  "D462": { "token": "inprc...", "label": "Pemprov Sumatera Barat" },
  "D472": { "token": "",         "label": "Belum ada token" }
}
```

Berkas ini sudah masuk `.gitignore`. KLPD tanpa token tetap terdaftar namun
dilewati.

---

## 3. Perintah

| Command | Fungsi |
|---|---|
| `php artisan inaproc:pull` | Mode interaktif (KLPD → tahun → konfirmasi) |
| `php artisan inaproc:pull --klpd=D462 --tahun=2026` | Tarik seluruh endpoint aktif untuk satu KLPD |
| `php artisan inaproc:pull --all --tahun=2026` | Tarik seluruh KLPD terdaftar secara paralel |
| `php artisan inaproc:pull --dry-run` | Tampilkan rencana tanpa memanggil API |
| `php artisan inaproc:pull --refresh-derived` | Abaikan cache endpoint turunan |
| `php artisan inaproc:pull --sync` | Jalankan langsung tanpa queue |
| `php artisan inaproc:pull --grup=rup --endpoint=paket-penyedia` | Batasi cakupan |
| `php artisan inaproc:probe --klpd=D462 --tahun=2026` | Uji akses tiap endpoint (1 request per endpoint) |
| `php artisan inaproc:validate-tokens` | Periksa validitas seluruh token (token disamarkan) |
| `php artisan inaproc:build-realisasi` | Hitung ulang `ref_rup_proses`, `ref_rup_warisan`, view |
| `php artisan inaproc:endpoints` | Tampilkan registry 65 endpoint + nama tabel |
| `php artisan inaproc:schema-check` | Bandingkan skema aktual dengan registry |

Exit code ≠ 0 bila terdapat `GAGAL` atau `FORBIDDEN` (untuk monitoring).

### Queue worker

Mode paralel memakai queue `database`:

```bash
php artisan queue:work --queue=default --tries=1 --timeout=7200
```

### Penjadwalan

Terdaftar di `routes/console.php`:

| Waktu | Tugas |
|---|---|
| 01:00 harian | `inaproc:pull --all --tahun=<tahun berjalan> --sync` |
| 04:00 harian | `BuildRealisasiJob` |
| Senin 06:00 | `inaproc:validate-tokens` |

Aktifkan dengan cron:

```
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 4. Arsitektur

```
app/
├── Console/Commands/          # 6 command operasional
├── Jobs/
│   ├── PullKlpdJob.php        # 1 KLPD = 1 job
│   └── BuildRealisasiJob.php
├── Enums/                     # ApiFamily, EndpointGroup, EndpointScope, PullStatus
├── Models/                    # Endpoint, PullLog, DerivedProgress
├── Services/
│   ├── Inaproc/               # ApiClient, ApiResponse, EndpointRegistry, Paginator, FallbackResolver
│   ├── Pull/                  # PullService, SchemaManager, TableWriter, DerivedPuller, KlpdCredentials
│   └── Realisasi/             # ProsesCollector, WarisanResolver, ViewBuilder
└── Support/                   # RateLimiter, KodeNormalizer
```

### Alur eksekusi

```
inaproc:pull
   └─► PullKlpdJob (per KLPD, paralel via queue)
         └─► PullService::pullEndpoint (per endpoint)
               ├─ v1     → loop cursor sampai has_more=false ATAU cursor kosong
               ├─ legacy → satu request
               ├─ 200 + data   → TableWriter::replace (delete-insert transaksional)
               ├─ 200 kosong   → skip, tabel tidak disentuh, tanpa fallback
               ├─ 403          → FORBIDDEN, lanjut endpoint berikutnya
               ├─ non-200      → FallbackResolver (R1–R4)
               └─ pull_log ← satu baris per endpoint × KLPD × tahun
inaproc:build-realisasi
   └─► ref_rup_proses + ref_rup_warisan + v_realisasi_rup
```

### Keputusan penting

| Aspek | Implementasi |
|---|---|
| Penamaan tabel | Diturunkan dari `api` + `grup` + `name` (F2), tidak disimpan |
| Skema dinamis | `SchemaManager` mendeteksi kolom & tipe dari data; tipe hanya meluas |
| Terminasi pagination | `has_more` **dan** cursor diperiksa keduanya |
| Fallback | Hanya saat HTTP non-200; hasil masuk tabel utama + `_source` |
| Endpoint turunan | Append (bukan delete-insert) + `derived_progress` sebagai bukti |
| Pewarisan RUP | BFS, bukan DFS, batas 30 level, siklus aman |
| Rate limit | Per token (4 lapisan: bucket, header adaptif, retry 429, circuit breaker) |
| Token di log | Selalu disamarkan (`inprc40f...fe02`) |

---

## 5. Skema Database

### Tabel sistem (migration)

| Tabel | Isi |
|---|---|
| `endpoints` | Registry 65 endpoint (61 aktif) |
| `pull_log` | Satu baris per endpoint × KLPD × tahun |
| `ref_rup_proses` | RUP yang punya proses sendiri |
| `ref_rup_warisan` | RUP yang mewarisi proses |
| `derived_progress` | Penanda nilai turunan yang sudah diambil |
| `klpd_credentials` | Alternatif berkas JSON (driver `database`) |
| `jobs`, `job_batches`, `failed_jobs`, `cache`, `sessions` | Infrastruktur Laravel |

### Tabel data (runtime, 55 tabel `rec_*`)

Dibuat otomatis oleh `SchemaManager` saat endpoint pertama kali ditarik.
Setiap tabel memiliki kolom sistem:

| Kolom | Tipe | Keterangan |
|---|---|---|
| `_row_id` | `bigserial` | Primary key |
| `_kode_klpd` | `varchar(16)` | Kunci partisi |
| `_tahun` | `smallint` | Kunci partisi |
| `_source` | `varchar(128)` | Endpoint asal (fallback) |
| `_fetched_at` | `timestamptz` | Waktu tarik |

Indeks wajib: `(_kode_klpd, _tahun)`.

### View `v_realisasi_rup`

| Kolom | Sumber |
|---|---|
| `kd_rup` | `rec_rup_paket_penyedia.kd_rup`, dinormalisasi ke string bilangan bulat |
| `nama_paket`, `pagu`, `kd_satker`, `nama_satker`, `metode_pengadaan`, `tahun_anggaran` | `rec_rup_paket_penyedia` |
| `sumber` | `tender` / `non-tender` / `pencatatan-non-tender` / `e-katalog` |
| `jenis` | `langsung` (depth 0) / `warisan` (depth > 0) |
| `dari_rup` | Leluhur (null bila langsung) |
| `depth` | Kedalaman rantai |

---

## 6. Hasil Validasi terhadap Dataset Acuan

Dijalankan pada basis data `inaproc` (D462 / 2026):

| Item | Acuan PRD | Hasil pipeline | Selisih |
|---|---:|---:|---:|
| `ref_rup_proses` | 3.362 | **3.375** | +13 |
| `ref_rup_warisan` | 291 | **290** | −1 |
| `v_realisasi_rup` | 3.637 | **3.665** | +28 |

Rincian `v_realisasi_rup` per sumber:

| Sumber | Langsung (acuan) | Langsung (hasil) | Warisan (acuan) | Warisan (hasil) |
|---|---:|---:|---:|---:|
| e-katalog | 1.653 | 1.653 | 222 | 222 |
| non-tender | 1.598 | 1.625 | 62 | 61 |
| pencatatan-non-tender | 5 | 7 | 0 | 0 |
| tender | 90 | 90 | 7 | 7 |

### Penjelasan selisih (wajib dapat dipertanggungjawabkan, PRD Bab 12)

Selisih **bukan** akibat perbedaan algoritma. Dua perhitungan independen
(SQL rekursif murni di PostgreSQL vs pipeline PHP) menghasilkan angka yang
**identik**: `ref_rup_proses` = 3.375, `ref_rup_warisan` = 290.

Penyebab selisih adalah **data sumber yang berubah setelah dataset acuan
dibuat**:

| Bukti | Nilai |
|---|---|
| `ref_rup_proses.updated_at` pada data lama | `2026-10-19 11:30:44` |
| `rec_tender_non_tender_pengumuman._fetched_at` | `2026-10-22 18:53:39` (3 hari lebih baru) |
| `rec_tender_pencatatan_non_tender._fetched_at` | `2026-10-22 18:53:59` |
| `pull_log` terakhir untuk endpoint tersebut | `2026-10-22 18:53` status `OK` |

Artinya: setelah dataset acuan dihitung, endpoint `non-tender-pengumuman` dan
`pencatatan-non-tender` ditarik ulang dan datanya bertambah. Kenaikan
`non-tender` (+27 langsung, −1 warisan) dan `pencatatan-non-tender` (+2)
persis mencerminkan pertambahan itu; sumber `tender` dan `e-katalog` yang
tidak berubah menghasilkan angka **sama persis** dengan acuan.

Untuk mereproduksi angka acuan secara persis, jalankan perhitungan pada
snapshot data per `2026-10-19`.

---

## 7. Pengujian

```bash
php artisan test                    # seluruh suite
./vendor/bin/phpunit tests/Unit     # unit saja (cepat)
```

Seluruh test memakai PostgreSQL (`inaproc_test`, lihat `phpunit.xml`). Tidak
ada konfigurasi SQLite — logika kunci memakai fitur khas PostgreSQL
(`unnest` + `string_to_array`, cast `numeric`, view).

| File | Cakupan |
|---|---|
| `Unit/KodeNormalizerTest` | F11.4 normalisasi kode, masking token |
| `Unit/WarisanResolverBfsTest` | F11.5 BFS, depth, siklus, batas 30 level |
| `Feature/EndpointRegistryTest` | F1, F1.1, F2 — 65/61, scope, penamaan tabel |
| `Feature/ApiClientPaginationTest` | F3 — cursor, terminasi ganda, legacy, non-JSON |
| `Feature/FallbackRulesTest` | F5 — R1–R4, 403, blokir, idempotensi |
| `Feature/RateLimiterTest` | F7 — L1–L4 |
| `Feature/SchemaManagerTest` | F6 — kolom sistem, evolusi tipe, rollback |
| `Feature/DerivedPullerTest` | F1.3 — incremental, append, gagal-diulang |
| `Feature/RealisasiTest` | F11 — multi-nilai, warisan, view |
| `Feature/CommandsTest` | F12 — dry-run, exit code, token tersamar |

---

## 8. Pemulihan

| Situasi | Tindakan |
|---|---|
| Satu endpoint `GAGAL` | Data lama utuh; ulangi `inaproc:pull --endpoint=<nama>` |
| Seluruh KLPD `FORBIDDEN` | Cek `inaproc:validate-tokens`; blokir jaringan ≠ token salah |
| `429` berulang | Kredensial diparkir `RATE_LIMIT_COOLDOWN` detik; KLPD lain tetap jalan |
| Realisasi perlu dihitung ulang | `inaproc:build-realisasi` (aman diulang kapan saja) |
| Skema tabel menyimpang | `inaproc:schema-check` untuk daftar tabel kosong/belum ada |
| Data turunan basi | `inaproc:pull --refresh-derived` |

---

## 9. Batasan yang Disengaja

| Item | Alasan |
|---|---|
| Tanpa UI / dashboard | Aplikasi terpisah membaca DB secara read-only |
| Tanpa ekspor Excel/CSV | Kewenangan aplikasi dashboard |
| Tanpa auth pengguna | Operasi via CLI |
| 4 endpoint `derived` nonaktif | Butuh hak akses khusus / keputusan bisnis (PRD A.10) |
| `ekatalog-list-kategori-produk` | Struktur pohon belum ditetapkan PRD (F1.4) |
| Tanpa Redis | Queue `database` dipakai sesuai keputusan operasional |
