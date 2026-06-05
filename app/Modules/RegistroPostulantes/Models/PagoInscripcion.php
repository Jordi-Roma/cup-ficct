<?php

namespace App\Modules\RegistroPostulantes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoInscripcion extends Model
{
    protected $table = 'pago_inscripcion';

    protected $primaryKey = 'id_pago';

    public $timestamps = false;

    protected $fillable = [
        'id_postulacion',
        'monto',
        'moneda',
        'pasarela',
        'numero_transaccion',
        'codigo_autorizacion',
        'codigo_error',
        'estado_pago',
        'fecha_inicio',
        'fecha_confirmacion',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha_inicio' => 'datetime',
            'fecha_confirmacion' => 'datetime',
        ];
    }

    public function postulacion(): BelongsTo
    {
        return $this->belongsTo(Postulacion::class, 'id_postulacion', 'id_postulacion');
    }
}
