<?php

namespace App\Modules\Autenticacion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Modules\ReportesMonitoreo\Traits\Auditable;

class Permiso extends Model
{
    use Auditable;

    protected $table = 'permiso';

    protected $primaryKey = 'id_permiso';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'modulo',
        'accion',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'rol_permiso', 'id_permiso', 'id_rol')
            ->withPivot(['fecha_asignacion', 'activo'])
            ->wherePivot('activo', true);
    }
}
