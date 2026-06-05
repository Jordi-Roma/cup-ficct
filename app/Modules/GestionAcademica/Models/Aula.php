<?php

namespace App\Modules\GestionAcademica\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Aula extends Model
{
    protected $table = 'aula';

    protected $primaryKey = 'id_aula';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'capacidad',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'capacidad' => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionAcademica::class, 'id_aula', 'id_aula');
    }
}
