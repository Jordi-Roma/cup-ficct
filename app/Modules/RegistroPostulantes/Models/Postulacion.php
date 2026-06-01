<?php

namespace App\Modules\RegistroPostulantes\Models;

use App\Modules\GestionAcademica\Models\Carrera;
use App\Modules\GestionAcademica\Models\GestionAcademica;
use App\Modules\GestionAcademica\Models\GrupoAcademico;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Postulacion extends Model
{
    protected $table = 'postulacion';

    protected $primaryKey = 'id_postulacion';

    public $timestamps = false;

    protected $fillable = [
        'id_postulante',
        'id_gestion',
        'id_carrera_opcion1',
        'id_carrera_opcion2',
        'id_carrera_admitida',
        'id_grupo',
        'estado_admision',
        'fecha_postulacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_postulacion' => 'datetime',
        ];
    }

    public function postulante(): BelongsTo
    {
        return $this->belongsTo(Postulante::class, 'id_postulante', 'id_postulante');
    }

    public function gestion(): BelongsTo
    {
        return $this->belongsTo(GestionAcademica::class, 'id_gestion', 'id_gestion');
    }

    public function carreraOpcion1(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'id_carrera_opcion1', 'id_carrera');
    }

    public function carreraOpcion2(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'id_carrera_opcion2', 'id_carrera');
    }

    public function carreraAdmitida(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'id_carrera_admitida', 'id_carrera');
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(GrupoAcademico::class, 'id_grupo', 'id_grupo');
    }
}
