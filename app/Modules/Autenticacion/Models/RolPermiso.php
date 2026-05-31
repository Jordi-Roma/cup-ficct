<?php

namespace App\Modules\Autenticacion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolPermiso extends Model
{
    protected $table = 'rol_permiso';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id_rol',
        'id_permiso',
        'fecha_asignacion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_asignacion' => 'datetime',
            'activo' => 'boolean',
        ];
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }

    public function permiso(): BelongsTo
    {
        return $this->belongsTo(Permiso::class, 'id_permiso', 'id_permiso');
    }
}
