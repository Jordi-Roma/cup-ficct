<?php

namespace App\Modules\Autenticacion\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Notifications\Notifiable;
use App\Modules\ReportesMonitoreo\Traits\Auditable;

#[UseFactory(UserFactory::class)]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, Auditable;

    protected $table = 'usuario';

    protected $primaryKey = 'id_usuario';

    public const CREATED_AT = 'fecha_registro';

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ci',
        'nombre',
        'apellido',
        'username',
        'correo',
        'password_hash',
        'telefono',
        'sexo',
        'estado_acceso',
        'activo',
        'name',
        'email',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password_hash' => 'hashed',
            'activo' => 'boolean',
            'fecha_registro' => 'datetime',
        ];
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->correo;
    }

    public function getEmailForVerification(): string
    {
        return $this->correo;
    }

    public function getIdAttribute(): mixed
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }

    public function getNameAttribute(): string
    {
        return trim(($this->nombre ?? '').' '.($this->apellido ?? ''));
    }

    public function setNameAttribute(string $value): void
    {
        $parts = preg_split('/\s+/', trim($value), 2);

        $this->attributes['nombre'] = $parts[0] ?? '';
        $this->attributes['apellido'] = $parts[1] ?? $this->attributes['apellido'] ?? '';
    }

    public function getEmailAttribute(): ?string
    {
        return $this->correo;
    }

    public function setEmailAttribute(string $value): void
    {
        $this->attributes['correo'] = $value;
    }

    public function getPasswordAttribute(): ?string
    {
        return $this->password_hash;
    }

    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password_hash'] = Hash::needsRehash($value)
            ? Hash::make($value)
            : $value;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'rol_usuario', 'id_usuario', 'id_rol')
            ->withPivot(['fecha_asignacion', 'fecha_expiracion', 'activo'])
            ->wherePivot('activo', true);
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()
            ->where('rol.nombre', strtoupper($role))
            ->where('rol.activo', true)
            ->exists();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles()
            ->where('rol.activo', true)
            ->whereHas('permisos', function ($query) use ($permission) {
                $query->where('permiso.nombre', $permission)
                    ->where('permiso.activo', true);
            })
            ->exists();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('ADMINISTRADOR');
    }

}
