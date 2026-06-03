<?php

namespace App\Modules\ReportesMonitoreo\Models;

use App\Modules\Autenticacion\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAuditoria extends Model
{
    protected $table = 'log_auditoria';

    protected $primaryKey = 'id_log';

    public const CREATED_AT = 'fecha_operacion';
    public const UPDATED_AT = null;

    protected $fillable = [
        'tabla_afectada',
        'operacion',
        'id_registro',
        'datos_anteriores',
        'datos_nuevos',
        'id_usuario',
        'ip_origen',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'datos_anteriores' => 'json',
            'datos_nuevos' => 'json',
            'fecha_operacion' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }
}
