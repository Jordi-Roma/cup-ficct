<?php

namespace App\Modules\RegistroPostulantes\Models;

use App\Modules\AccesoSeguridad\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\AccesoSeguridad\Traits\Auditable;

class Postulante extends Model
{
    use Auditable;

    protected $table = 'postulante';

    protected $primaryKey = 'id_postulante';

    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'fecha_nacimiento',
        'direccion',
        'colegio_procedencia',
        'ciudad',
        'documentacion_completa',
        'presento_titulo_bachiller',
        'presento_fotocopia_carnet',
        'documentacion_validada',
        'fecha_validacion_documentos',
        'validado_por',
        'creado_por_admin',
        'requiere_pago',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'documentacion_completa' => 'boolean',
            'presento_titulo_bachiller' => 'boolean',
            'presento_fotocopia_carnet' => 'boolean',
            'documentacion_validada' => 'boolean',
            'fecha_validacion_documentos' => 'datetime',
            'creado_por_admin' => 'boolean',
            'requiere_pago' => 'boolean',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    public function postulaciones(): HasMany
    {
        return $this->hasMany(Postulacion::class, 'id_postulante', 'id_postulante');
    }

    public function validador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validado_por', 'id_usuario');
    }
}
