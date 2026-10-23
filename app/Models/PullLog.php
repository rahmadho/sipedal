<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PullStatus;
use Illuminate\Database\Eloquent\Model;

class PullLog extends Model
{
    protected $table = 'pull_log';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'rows' => 'integer',
            'pages' => 'integer',
            'durasi_ms' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function statusEnum(): PullStatus
    {
        return PullStatus::from($this->status);
    }
}
