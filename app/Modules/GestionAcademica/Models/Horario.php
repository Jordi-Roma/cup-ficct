<?php

namespace App\Modules\GestionAcademica\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Horario extends Model
{
    protected $table = 'horario';

    protected $primaryKey = 'id_horario';

    public $timestamps = false;

    protected $fillable = [
        'dia',
        'hora_inicio',
        'hora_fin',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionAcademica::class, 'id_horario', 'id_horario');
    }
}
