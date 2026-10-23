<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ApiFamily;
use App\Enums\EndpointGroup;
use App\Enums\EndpointScope;
use App\Services\Inaproc\EndpointRegistry;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property string $path
 * @property string $api
 * @property string $grup
 * @property string $scope
 * @property bool   $active
 * @property int    $urutan
 */
class Endpoint extends Model
{
    protected $table = 'endpoints';

    protected $primaryKey = 'name';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['name', 'path', 'api', 'grup', 'scope', 'active', 'urutan'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    public function family(): ApiFamily
    {
        return ApiFamily::from($this->api);
    }

    public function group(): EndpointGroup
    {
        return EndpointGroup::from($this->grup);
    }

    public function endpointScope(): EndpointScope
    {
        return EndpointScope::from($this->scope);
    }

    /**
     * Nama tabel data (F2). Diturunkan, tidak disimpan.
     */
    public function tableName(): string
    {
        return EndpointRegistry::tableNameFor($this->api, $this->grup, $this->name);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeGroup($query, string $group)
    {
        return $query->where('grup', $group);
    }
}
