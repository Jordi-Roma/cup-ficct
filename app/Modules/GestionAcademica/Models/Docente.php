<?php

namespace App\Modules\GestionAcademica\Models;

use App\Modules\AccesoSeguridad\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Docente extends Model
{
    protected $table = 'docente';

    protected $primaryKey = 'id_docente';

    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'profesional_area',
        'maestria',
        'diplomado_educacion_superior',
        'maestria_educacion_superior',
        'contratado',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'profesional_area' => 'boolean',
            'maestria' => 'boolean',
            'diplomado_educacion_superior' => 'boolean',
            'maestria_educacion_superior' => 'boolean',
            'contratado' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    public function habilitaciones(): HasMany
    {
        return $this->hasMany(DocenteHabilitacionMateria::class, 'id_docente', 'id_docente');
    }

    public function materiasHabilitadas()
    {
        return MateriaCup::query()
            ->whereIn('id_materia', $this->habilitaciones()
                ->where('activo', true)
                ->select('id_materia')
            );
    }

    public function puedeDictarMateria(int $idMateria): bool
    {
        return (bool) $this->activo
            && (bool) $this->contratado
            && (bool) $this->maestria_educacion_superior
            && $this->habilitaciones()
                ->where('activo', true)
                ->where('id_materia', $idMateria)
                ->exists();
    }
}
