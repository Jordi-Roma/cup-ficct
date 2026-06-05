<?php

namespace App\Modules\AccesoSeguridad\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolUsuario extends Model
{
    protected $table = 'rol_usuario';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'id_rol',
        'fecha_asignacion',
        'fecha_expiracion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_asignacion' => 'datetime',
            'fecha_expiracion' => 'datetime',
            'activo' => 'boolean',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }
}
