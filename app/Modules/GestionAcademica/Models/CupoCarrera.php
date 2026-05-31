<?php

namespace App\Modules\GestionAcademica\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CupoCarrera extends Model
{
    protected $table = 'cupo_carrera';

    protected $primaryKey = 'id_cupo';

    public $timestamps = false;

    protected $fillable = [
        'id_carrera',
        'id_gestion',
        'cupo_maximo',
    ];

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'id_carrera', 'id_carrera');
    }

    public function gestion(): BelongsTo
    {
        return $this->belongsTo(GestionAcademica::class, 'id_gestion', 'id_gestion');
    }
}
