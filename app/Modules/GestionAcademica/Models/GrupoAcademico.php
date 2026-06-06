<?php

namespace App\Modules\GestionAcademica\Models;

use App\Modules\RegistroPostulantes\Models\Postulacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\AccesoSeguridad\Traits\Auditable;

class GrupoAcademico extends Model
{
    use Auditable;

    protected $table = 'grupo_academico';

    protected $primaryKey = 'id_grupo';

    public $timestamps = false;

    protected $fillable = [
        'id_gestion',
        'nombre',
        'turno',
        'capacidad_maxima',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function gestion(): BelongsTo
    {
        return $this->belongsTo(GestionAcademica::class, 'id_gestion', 'id_gestion');
    }

    public function postulaciones(): HasMany
    {
        return $this->hasMany(Postulacion::class, 'id_grupo', 'id_grupo');
    }
}
