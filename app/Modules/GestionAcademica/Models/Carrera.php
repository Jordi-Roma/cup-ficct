<?php

namespace App\Modules\GestionAcademica\Models;

use App\Modules\AccesoSeguridad\Traits\Auditable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrera extends Model
{
    use Auditable;

    protected $table = 'carrera';

    protected $primaryKey = 'id_carrera';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function cupos(): HasMany
    {
        return $this->hasMany(CupoCarrera::class, 'id_carrera', 'id_carrera');
    }
}
