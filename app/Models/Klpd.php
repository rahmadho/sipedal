<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Klpd extends Model
{
    protected $table = 'klpds';

    protected $primaryKey = 'kd_klpd';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'kd_klpd',
        'nama',
        'alamat',
        'ibukota',
        'jenis',
        'prp_id',
        'kbp_id',
        'spse_id',
        'spse_versi',
        'spse_url',
        'domain',
        'tema',
        'status',
        'created_at',
        'updated_at',
    ];

    public function credential()
    {
        return $this->hasOne(KlpdCredential::class, 'kode', 'kd_klpd');
    }
}
