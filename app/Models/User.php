<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\SatkerFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Model User.
 *
 * Kolom tabel users mengikuti DDL resmi. Tabel users tidak menyimpan kolom
 * role/peran secara langsung (peran dikelola di session atau relasi tersendiri).
 *
 * Peran yang wajib memiliki satuan kerja (rup_stk_id): kepala_opd dan ppk.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'username',
        'email',
        'email_verified_at',
        'password',
        'kd_klpd',
        'rup_stk_id',
        'nip',
        'pangkat',
        'golongan',
        'jabatan',
        'no_hp',
        'avatar',
        'no_telp',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'rup_stk_id' => 'integer',
        ];
    }

    /**
     * Daftar peran yang wajib memiliki kode satuan kerja (rup_stk_id).
     */
    public static function peranWajibSatker(): array
    {
        return SatkerFilter::PERAN_WAJIB;
    }

    /**
     * Cek apakah peran tertentu memerlukan satuan kerja.
     */
    public static function butuhSatker(?string $peran): bool
    {
        return SatkerFilter::wajib($peran);
    }

    /**
     * Scope untuk menyaring data pengguna berdasarkan satuan kerja.
     */
    public function scopeUntukSatker(Builder $query, ?int $rupStkId): Builder
    {
        if ($rupStkId === null) {
            return $query;
        }

        return $query->where('rup_stk_id', $rupStkId);
    }
}
