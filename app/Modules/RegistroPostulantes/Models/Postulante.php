<?php

namespace App\Modules\RegistroPostulantes\Models;

use App\Modules\Autenticacion\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\ReportesMonitoreo\Traits\Auditable;

class Postulante extends Model
{
    use Auditable;

    protected $table = 'postulante';

    protected $primaryKey = 'id_postulante';

    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'fecha_nacimiento',
        'direccion',
        'colegio_procedencia',
        'ciudad',
        'documentacion_completa',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'documentacion_completa' => 'boolean',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    public function postulaciones(): HasMany
    {
        return $this->hasMany(Postulacion::class, 'id_postulante', 'id_postulante');
    }
}
