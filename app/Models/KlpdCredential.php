<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class KlpdCredential extends Model
{
    protected $table = 'klpd_credentials';

    protected $primaryKey = 'kode';

    public $incrementing = false;

    protected $keyType = 'string';

    const UPDATED_AT = 'updated_at';
    const CREATED_AT = null;

    protected $fillable = [
        'kode',
        'label',
        'token_encrypted',
        'is_active',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'updated_at' => 'datetime',
        ];
    }

    public function getTokenAttribute(): string
    {
        if (empty($this->token_encrypted)) {
            return '';
        }

        try {
            return Crypt::decryptString($this->token_encrypted);
        } catch (\Throwable) {
            return (string) $this->token_encrypted;
        }
    }

    public function setTokenAttribute(string $value): void
    {
        $clean = trim($value);
        $this->attributes['token_encrypted'] = $clean !== '' ? Crypt::encryptString($clean) : '';
    }

    public function getMaskedTokenAttribute(): string
    {
        $raw = $this->token;
        if (empty($raw)) {
            return '— (Belum diisi)';
        }

        $len = strlen($raw);
        if ($len <= 8) {
            return str_repeat('•', $len);
        }

        return substr($raw, 0, 4) . str_repeat('•', min(16, $len - 8)) . substr($raw, -4);
    }
}
