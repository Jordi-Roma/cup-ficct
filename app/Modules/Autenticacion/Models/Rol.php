<?php

namespace App\Modules\Autenticacion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Rol extends Model
{
    protected $table = 'rol';

    protected $primaryKey = 'id_rol';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
        'fecha_creacion',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'fecha_creacion' => 'datetime',
        ];
    }

    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'rol_permiso', 'id_rol', 'id_permiso')
            ->withPivot(['fecha_asignacion', 'activo'])
            ->wherePivot('activo', true);
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'rol_usuario', 'id_rol', 'id_usuario')
            ->withPivot(['fecha_asignacion', 'fecha_expiracion', 'activo'])
            ->wherePivot('activo', true);
    }
}
