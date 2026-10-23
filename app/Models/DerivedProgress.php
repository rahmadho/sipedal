<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DerivedProgress extends Model
{
    protected $table = 'derived_progress';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'tahun' => 'integer',
        'hasil_rows' => 'integer',
        'fetched_at' => 'datetime',
    ];
}
