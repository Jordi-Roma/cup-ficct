<?php

namespace App\Modules\GestionAcademica\Models;

use App\Modules\Autenticacion\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'contratado',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'profesional_area' => 'boolean',
            'maestria' => 'boolean',
            'diplomado_educacion_superior' => 'boolean',
            'contratado' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }
}
