<?php

namespace App\Modules\Examenes\Models;

use App\Modules\Autenticacion\Models\User;
use App\Modules\GestionAcademica\Models\MateriaCup;
use App\Modules\RegistroPostulantes\Models\Postulacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\ReportesMonitoreo\Traits\Auditable;

class Nota extends Model
{
    use Auditable;

    protected $table = 'nota';

    protected $primaryKey = 'id_nota';

    public $timestamps = false;

    protected $fillable = [
        'id_postulacion',
        'id_materia',
        'nro_examen',
        'nota',
        'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'nro_examen' => 'integer',
            'nota' => 'decimal:2',
            'fecha_registro' => 'datetime',
        ];
    }

    public function postulacion(): BelongsTo
    {
        return $this->belongsTo(Postulacion::class, 'id_postulacion', 'id_postulacion');
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(MateriaCup::class, 'id_materia', 'id_materia');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por', 'id_usuario');
    }
}
