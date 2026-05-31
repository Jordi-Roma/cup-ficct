<?php

namespace App\Modules\GestionAcademica\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GestionAcademica extends Model
{
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
}
