<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Alamat API
    |--------------------------------------------------------------------------
    */
    'base_url' => env('INAPROC_BASE_URL', 'https://data.inaproc.id'),

    /*
    |--------------------------------------------------------------------------
    | Perilaku HTTP
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('INAPROC_TIMEOUT', 120),
    'connect_timeout' => (int) env('INAPROC_CONNECT_TIMEOUT', 20),
    'page_size' => (int) env('INAPROC_PAGE_SIZE', 1000),
    'max_pages' => (int) env('INAPROC_MAX_PAGES', 10000),
    'max_retry' => (int) env('INAPROC_MAX_RETRY', 4),
    'retry_backoff' => (int) env('INAPROC_RETRY_BACKOFF', 2),
    'chunk_size' => (int) env('INAPROC_CHUNK_SIZE', 500),
    'workers' => (int) env('INAPROC_WORKERS', 4),

    /*
    |--------------------------------------------------------------------------
    | Kredensial KLPD
    |--------------------------------------------------------------------------
    |
    | driver: "file" membaca storage/app/klpd_tokens.json, "database" membaca
    | tabel klpd_credentials. Pilih salah satu — jangan keduanya.
    |
    */
    'credentials_driver' => env('INAPROC_CREDENTIALS_DRIVER', 'database'),
    'klpd_file' => env('INAPROC_KLPD_FILE', storage_path('app/klpd_tokens.json')),

    /*
    |--------------------------------------------------------------------------
    | Autentikasi
    |--------------------------------------------------------------------------
    |
    | Dokumentasi API menerima `X-API-Key: <token>` atau
    | `Authorization: Bearer <token>`. Default: X-API-Key.
    |
    */
    // Gateway hanya menerima Bearer; X-API-Key membalas 401
    // {"error":"Authorization field missing"}.
    'auth_header' => env('INAPROC_AUTH_HEADER', 'Authorization'),
    'auth_scheme' => env('INAPROC_AUTH_SCHEME', 'Bearer'),

    /*
    |--------------------------------------------------------------------------
    | Penanda blokir (F9)
    |--------------------------------------------------------------------------
    */
    'block_markers' => [
        'access denied',
        'forbidden',
        'blocked',
        'diblokir',
        'cloudflare',
        'captcha',
    ],

    /*
    |--------------------------------------------------------------------------
    | Endpoint turunan (F1.3, F1.4)
    |--------------------------------------------------------------------------
    |
    | `key`      kolom kunci nilai turunan
    | `source`   tabel + kolom asal nilai
    | `tree`     true bila endpoint berjenjang (butuh keputusan bisnis; nonaktif)
    |
    */
    'derived' => [
        'ekatalog-penyedia-detail' => [
            'key' => 'kode_penyedia',
            'source_table' => 'rec_ekatalog_paket_e_purchasing',
            'source_column' => 'kode_penyedia',
            'tree' => false,
        ],
        'ekatalog-list-produk-penyedia' => [
            'key' => 'kode_penyedia',
            'source_table' => 'rec_ekatalog_paket_e_purchasing',
            'source_column' => 'kode_penyedia',
            'tree' => false,
        ],
        'legacy-ekatalog-penyedia-detail' => [
            'key' => 'kode_penyedia',
            'source_table' => 'rec_legacy_ekatalog_paket_e_purchasing',
            'source_column' => 'kode_penyedia',
            'tree' => false,
        ],
        'ekatalog-list-kategori-produk' => [
            'key' => 'kategori',
            'source_table' => null,
            'source_column' => null,
            'tree' => true,
            'max_depth' => (int) env('INAPROC_DERIVED_MAX_DEPTH', 5),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pembatasan laju (F7)
    |--------------------------------------------------------------------------
    */
    'rate' => [
        'v1_per_min' => (int) env('RATE_LIMIT_V1_PER_MIN', 300),
        'legacy_per_min' => (int) env('RATE_LIMIT_LEGACY_PER_MIN', 90),
        'slow_threshold' => (int) env('RATE_LIMIT_SLOW_THRESHOLD', 300),
        'pause_threshold' => (int) env('RATE_LIMIT_PAUSE_THRESHOLD', 30),
        'max_retry' => (int) env('RATE_LIMIT_MAX_RETRY', 6),
        'cooldown' => (int) env('RATE_LIMIT_COOLDOWN', 120),
    ],

    /*
    |--------------------------------------------------------------------------
    | Realisasi RUP (F11)
    |--------------------------------------------------------------------------
    */
    'realisasi' => [
        'max_depth' => (int) env('INAPROC_REALISASI_MAX_DEPTH', 30),

        'sumber' => [
            'tender' => ['table' => 'rec_tender_pengumuman', 'column' => 'kd_rup'],
            'non-tender' => ['table' => 'rec_tender_non_tender_pengumuman', 'column' => 'kd_rup'],
            'pencatatan-non-tender' => ['table' => 'rec_tender_pencatatan_non_tender', 'column' => 'kd_rup'],
            'e-katalog' => ['table' => 'rec_ekatalog_paket_e_purchasing', 'column' => 'rup_code'],
        ],

        'paket_table' => 'rec_rup_paket_penyedia',
        'paket_kode_column' => 'kd_rup',
        'kaji_ulang_table' => 'rec_rup_history_kaji_ulang',
    ],

];
