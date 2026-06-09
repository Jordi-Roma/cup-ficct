<?php

namespace App\Modules\GestionAcademica\Models;

use App\Modules\AccesoSeguridad\Traits\Auditable;

use Illuminate\Database\Eloquent\Model;

class MateriaCup extends Model
{
    use Auditable;

    protected $table = 'materia_cup';

    protected $primaryKey = 'id_materia';

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
}
