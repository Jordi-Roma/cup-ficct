<?php

namespace App\Modules\GestionAcademica\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\AccesoSeguridad\Traits\Auditable;

class GestionAcademica extends Model
{
    use Auditable;

    protected $table = 'gestion_academica';

    protected $primaryKey = 'id_gestion';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'fecha_inicio',
        'fecha_fin',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'activo' => 'boolean',
        ];
    }

    public function cupos(): HasMany
    {
        return $this->hasMany(CupoCarrera::class, 'id_gestion', 'id_gestion');
    }

    public function grupos(): HasMany
    {
        return $this->hasMany(GrupoAcademico::class, 'id_gestion', 'id_gestion');
    }

    public function postulaciones(): HasMany
    {
        return $this->hasMany(\App\Modules\RegistroPostulantes\Models\Postulacion::class, 'id_gestion', 'id_gestion');
    }
}
