<?php

namespace App\Modules\GestionAcademica\Models;

use App\Modules\AccesoSeguridad\Traits\Auditable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocenteHabilitacionMateria extends Model
{
    use Auditable;

    public const PROFESIONAL_AREA = 'PROFESIONAL_AREA';
    public const DIPLOMADO = 'DIPLOMADO';
    public const MAESTRIA = 'MAESTRIA';

    public const TIPOS = [
        self::PROFESIONAL_AREA,
        self::DIPLOMADO,
        self::MAESTRIA,
    ];

    protected $table = 'docente_habilitacion_materia';

    protected $primaryKey = 'id_habilitacion';

    public $timestamps = false;

    protected $fillable = [
        'id_docente',
        'id_materia',
        'tipo_habilitacion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'fecha_registro' => 'datetime',
        ];
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'id_docente', 'id_docente');
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(MateriaCup::class, 'id_materia', 'id_materia');
    }
}
