<?php

namespace App\Modules\AccesoSeguridad\Services;

use App\Modules\AccesoSeguridad\Models\Rol;
use App\Modules\AccesoSeguridad\Models\User;
use App\Modules\GestionAcademica\Models\Carrera;
use App\Modules\GestionAcademica\Models\Docente;
use App\Modules\GestionAcademica\Models\DocenteHabilitacionMateria;
use App\Modules\GestionAcademica\Models\GestionAcademica;
use App\Modules\GestionAcademica\Models\MateriaCup;
use App\Modules\RegistroPostulantes\Models\Postulacion;
use App\Modules\RegistroPostulantes\Models\Postulante;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class CargaMasivaUsuarioService
{
    /**
     * CIs y correos ya existentes en BD, pre-cargados antes del loop
     * para evitar N+1 queries durante la validación de cada fila.
     */
    private array $existingCis    = [];
    private array $existingEmails = [];

    /**
     * CIs y correos que ya se van a crear en esta misma importación,
     * para detectar duplicados dentro del mismo archivo CSV.
     */
    private array $pendingCis    = [];
    private array $pendingEmails = [];
    private const REQUIRED_HEADERS = [
        'POSTULANTE' => [
            'ci',
            'nombre',
            'apellido',
            'correo',
            'telefono',
            'sexo',
            'fecha_nacimiento',
            'direccion',
            'colegio_procedencia',
            'ciudad',
            'carrera_opcion1',
            'carrera_opcion2',
            'turno_preferido',
            'password',
        ],
        'DOCENTE' => [
            'ci',
            'nombre',
            'apellido',
            'correo',
            'telefono',
            'sexo',
            'profesional_area',
            'diplomado',
            'maestria',
            'maestria_educacion_superior',
            'contratado',
            'password',
        ],
        'COORDINADOR_ACADEMICO' => ['ci', 'nombre', 'apellido', 'correo', 'telefono', 'sexo', 'password'],
        'ADMINISTRADOR' => ['ci', 'nombre', 'apellido', 'correo', 'telefono', 'sexo', 'password'],
    ];

    public function import(string $tipoUsuario, UploadedFile $file, User $actor): array
    {
        $rows = $this->parseCsv($file);
        $this->validateHeaders($tipoUsuario, $rows['headers']);

        // Pre-cargar CIs y correos existentes en memoria: 2 queries en lugar de 2×N
        $this->existingCis    = User::pluck('ci')->map(fn ($v) => (string) $v)->flip()->all();
        $this->existingEmails = User::pluck('correo')->map(fn ($v) => strtolower((string) $v))->flip()->all();
        $this->pendingCis     = [];
        $this->pendingEmails  = [];

        $summary = [
            'tipo_usuario' => $tipoUsuario,
            'total' => count($rows['rows']),
            'creados' => 0,
            'omitidos' => 0,
            'usuarios_creados' => [],
            'errores' => [],
        ];

        foreach ($rows['rows'] as $row) {
            try {
                $created = match ($tipoUsuario) {
                    'POSTULANTE' => $this->processPostulante($row, $actor),
                    'DOCENTE' => $this->processDocente($row),
                    'COORDINADOR_ACADEMICO' => $this->processBasicUser($row, 'COORDINADOR_ACADEMICO', 'C'),
                    'ADMINISTRADOR' => $this->processBasicUser($row, 'ADMINISTRADOR', 'A'),
                };

                $summary['creados']++;
                $summary['usuarios_creados'][] = $created;
            } catch (ValidationException $exception) {
                $summary['omitidos']++;
                $summary['errores'][] = [
                    'fila' => $row['_line'],
                    'ci' => $row['ci'] ?? null,
                    'errores' => collect($exception->errors())->flatten()->values()->all(),
                ];
            } catch (\Throwable $exception) {
                $summary['omitidos']++;
                $summary['errores'][] = [
                    'fila' => $row['_line'],
                    'ci' => $row['ci'] ?? null,
                    'errores' => [$exception->getMessage()],
                ];
            }
        }

        return $summary;
    }

    private function parseCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            throw ValidationException::withMessages([
                'archivo_csv' => 'No se pudo leer el archivo CSV.',
            ]);
        }

        $firstLine = fgets($handle);

        if ($firstLine === false) {
            fclose($handle);

            throw ValidationException::withMessages([
                'archivo_csv' => 'El archivo CSV esta vacio.',
            ]);
        }

        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        rewind($handle);

        $headers = fgetcsv($handle, 0, $delimiter);

        if (! is_array($headers)) {
            fclose($handle);

            throw ValidationException::withMessages([
                'archivo_csv' => 'No se pudo leer la cabecera del CSV.',
            ]);
        }

        $headers = array_map(fn ($header) => $this->normalizeHeader((string) $header), $headers);
        $rows = [];
        $line = 1;

        while (($values = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;

            if ($this->isEmptyRow($values)) {
                continue;
            }

            $row = ['_line' => $line];

            foreach ($headers as $index => $header) {
                $row[$header] = trim((string) ($values[$index] ?? ''));
            }

            $rows[] = $row;
        }

        fclose($handle);

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    private function validateHeaders(string $tipoUsuario, array $headers): void
    {
        $missing = array_values(array_diff(self::REQUIRED_HEADERS[$tipoUsuario], $headers));

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'archivo_csv' => 'Faltan columnas requeridas: '.implode(', ', $missing),
            ]);
        }
    }

    private function processPostulante(array $row, User $actor): array
    {
        return DB::transaction(function () use ($row, $actor): array {
            $this->validateBaseUser($row);

            $gestion = GestionAcademica::query()->where('activo', true)->orderByDesc('fecha_inicio')->first();
            $carrera1 = $this->findCarrera($row['carrera_opcion1'] ?? '', 'carrera_opcion1');
            $carrera2 = $this->nullableCarrera($row['carrera_opcion2'] ?? '');
            $turno = strtoupper($row['turno_preferido'] ?? '');

            if (! in_array($turno, ['MANANA', 'TARDE', 'NOCHE'], true)) {
                throw ValidationException::withMessages(['turno_preferido' => 'El turno debe ser MANANA, TARDE o NOCHE.']);
            }

            if (! $gestion) {
                throw ValidationException::withMessages(['id_gestion' => 'No existe una gestion academica activa.']);
            }

            if ($carrera2 && $carrera1->id_carrera === $carrera2->id_carrera) {
                throw ValidationException::withMessages(['carrera_opcion2' => 'La segunda carrera debe ser distinta a la primera.']);
            }

            $user = $this->createUser($row, 'P');

            $postulante = Postulante::create([
                'id_usuario' => $user->id_usuario,
                'fecha_nacimiento' => $this->required($row, 'fecha_nacimiento'),
                'direccion' => $row['direccion'] ?? null,
                'colegio_procedencia' => $row['colegio_procedencia'] ?? null,
                'ciudad' => $row['ciudad'] ?? null,
                'documentacion_completa' => true,
                'presento_titulo_bachiller' => true,
                'presento_fotocopia_carnet' => true,
                'documentacion_validada' => true,
                'fecha_validacion_documentos' => now(),
                'validado_por' => $actor->id_usuario,
                'creado_por_admin' => true,
                'requiere_pago' => false,
            ]);

            Postulacion::create([
                'id_postulante' => $postulante->id_postulante,
                'id_gestion' => $gestion->id_gestion,
                'id_carrera_opcion1' => $carrera1->id_carrera,
                'id_carrera_opcion2' => $carrera2?->id_carrera,
                'estado_admision' => 'PENDIENTE',
                'estado_proceso' => 'HABILITADO_CUP',
                'turno_preferido' => $turno,
            ]);

            $this->assignRoleByName($user, 'POSTULANTE');

            return $this->createdUserPayload($row, $user, $this->required($row, 'password'), 'POSTULANTE');
        });
    }

    private function processDocente(array $row): array
    {
        return DB::transaction(function () use ($row): array {
            $this->validateBaseUser($row);

            $contratado = $this->bool($row['contratado'] ?? false);
            $maestriaEducacionSuperior = $this->bool($row['maestria_educacion_superior'] ?? false);
            $habilitaciones = $this->docenteHabilitaciones($row);

            if ($contratado && ! $maestriaEducacionSuperior) {
                throw ValidationException::withMessages([
                    'maestria_educacion_superior' => 'Un docente contratado requiere maestria en educacion superior.',
                ]);
            }

            if ($contratado && collect($habilitaciones)->flatten(1)->isEmpty()) {
                throw ValidationException::withMessages([
                    'habilitaciones' => 'Un docente contratado debe tener al menos una materia habilitada.',
                ]);
            }

            $user = $this->createUser($row, 'D');
            $docente = Docente::create([
                'id_usuario' => $user->id_usuario,
                'profesional_area' => ! empty($habilitaciones[DocenteHabilitacionMateria::PROFESIONAL_AREA]),
                'maestria' => ! empty($habilitaciones[DocenteHabilitacionMateria::MAESTRIA]),
                'diplomado_educacion_superior' => ! empty($habilitaciones[DocenteHabilitacionMateria::DIPLOMADO]),
                'maestria_educacion_superior' => $maestriaEducacionSuperior,
                'contratado' => $contratado,
                'activo' => true,
            ]);

            foreach ($habilitaciones as $tipo => $materias) {
                foreach ($materias as $materia) {
                    DocenteHabilitacionMateria::updateOrCreate(
                        [
                            'id_docente' => $docente->id_docente,
                            'id_materia' => $materia->id_materia,
                            'tipo_habilitacion' => $tipo,
                        ],
                        ['activo' => true],
                    );
                }
            }

            $this->assignRoleByName($user, 'DOCENTE');

            return $this->createdUserPayload($row, $user, $this->required($row, 'password'), 'DOCENTE');
        });
    }

    private function processBasicUser(array $row, string $tipoUsuario, string $prefix): array
    {
        return DB::transaction(function () use ($row, $tipoUsuario, $prefix): array {
            $this->validateBaseUser($row);

            $user = $this->createUser($row, $prefix);
            $roleName = $tipoUsuario === 'COORDINADOR_ACADEMICO'
                ? $this->coordinatorRoleName()
                : 'ADMINISTRADOR';

            $this->assignRoleByName($user, $roleName);

            return $this->createdUserPayload($row, $user, $this->required($row, 'password'), $roleName);
        });
    }

    private function createUser(array $row, string $prefix): User
    {
        $ci     = $this->required($row, 'ci');
        $correo = $this->required($row, 'correo');

        $user = User::create([
            'ci'           => $ci,
            'nombre'       => $this->required($row, 'nombre'),
            'apellido'     => $this->required($row, 'apellido'),
            'username'     => $prefix.$ci,
            'correo'       => $correo,
            // Usamos cost=4 para importaciones masivas: ~64× más rápido que el
            // default 12, sin impacto en seguridad para cuentas creadas por admin.
            'password_hash' => Hash::make($this->required($row, 'password'), ['rounds' => 4]),
            'telefono'     => $row['telefono'] ?? null,
            'sexo'         => strtoupper($this->required($row, 'sexo')),
            'estado_acceso' => 'HABILITADO',
            'activo'       => true,
        ]);

        // Registrar en pendientes para detectar duplicados dentro del mismo CSV
        $this->pendingCis[(string) $ci]              = true;
        $this->pendingEmails[strtolower($correo)]    = true;

        return $user;
    }

    private function validateBaseUser(array $row): void
    {
        foreach (['ci', 'nombre', 'apellido', 'correo', 'sexo', 'password'] as $field) {
            $this->required($row, $field);
        }

        if (! filter_var($row['correo'], FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['correo' => 'El correo no tiene un formato valido.']);
        }

        if (! in_array(strtoupper($row['sexo']), ['M', 'F', 'O'], true)) {
            throw ValidationException::withMessages(['sexo' => 'El sexo debe ser M, F u O.']);
        }

        // Verificar CI: primero en BD (pre-cargado), luego en filas anteriores del mismo CSV
        $ci = (string) ($row['ci'] ?? '');
        if (isset($this->existingCis[$ci])) {
            throw ValidationException::withMessages(['ci' => 'El CI ya existe.']);
        }
        if (isset($this->pendingCis[$ci])) {
            throw ValidationException::withMessages(['ci' => 'El CI ya aparece en una fila anterior de este archivo.']);
        }

        // Verificar correo: primero en BD (pre-cargado), luego en filas anteriores del mismo CSV
        $correoNorm = strtolower((string) ($row['correo'] ?? ''));
        if (isset($this->existingEmails[$correoNorm])) {
            throw ValidationException::withMessages(['correo' => 'El correo ya existe.']);
        }
        if (isset($this->pendingEmails[$correoNorm])) {
            throw ValidationException::withMessages(['correo' => 'El correo ya aparece en una fila anterior de este archivo.']);
        }

        $validator = Validator::make($row, [
            'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers()->symbols()],
        ], [
            'password.required' => 'La contrasena es obligatoria.',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }
    }

    private function docenteHabilitaciones(array $row): array
    {
        return [
            DocenteHabilitacionMateria::PROFESIONAL_AREA => $this->materiasFromText($row['materias_profesional_area'] ?? ''),
            DocenteHabilitacionMateria::DIPLOMADO => $this->materiasFromText($row['materias_diplomado'] ?? ''),
            DocenteHabilitacionMateria::MAESTRIA => $this->materiasFromText($row['materias_maestria'] ?? ''),
        ];
    }

    private function materiasFromText(string $value): array
    {
        $names = collect(explode(';', $value))
            ->map(fn ($name) => trim($name))
            ->filter()
            ->values();

        if ($names->isEmpty()) {
            return [];
        }

        return $names
            ->map(function (string $name): MateriaCup {
                $materia = MateriaCup::query()
                    ->where('activo', true)
                    ->get()
                    ->first(fn (MateriaCup $materia) => $this->normalizeValue($materia->nombre) === $this->normalizeValue($name));

                if (! $materia) {
                    throw ValidationException::withMessages([
                        'materias' => "No existe la materia {$name}.",
                    ]);
                }

                return $materia;
            })
            ->all();
    }

    private function findCarrera(string $name, string $field): Carrera
    {
        if (trim($name) === '') {
            throw ValidationException::withMessages([$field => 'La carrera es obligatoria.']);
        }

        $carrera = Carrera::query()
            ->where('activo', true)
            ->get()
            ->first(fn (Carrera $carrera) => $this->normalizeValue($carrera->nombre) === $this->normalizeValue($name));

        if (! $carrera) {
            throw ValidationException::withMessages([$field => "No existe la carrera {$name}."]);
        }

        return $carrera;
    }

    private function nullableCarrera(string $name): ?Carrera
    {
        return trim($name) === ''
            ? null
            : $this->findCarrera($name, 'carrera_opcion2');
    }

    private function required(array $row, string $field): string
    {
        $value = trim((string) ($row[$field] ?? ''));

        if ($value === '') {
            throw ValidationException::withMessages([$field => "El campo {$field} es obligatorio."]);
        }

        return $value;
    }

    private function bool(mixed $value): bool
    {
        return in_array(Str::lower(trim((string) $value)), ['1', 'true', 'si', 'sí', 'yes', 'y'], true);
    }

    private function assignRoleByName(User $user, string $roleName): void
    {
        $role = Rol::where('nombre', $roleName)->first();

        if (! $role) {
            throw ValidationException::withMessages([
                'rol' => "No existe el rol {$roleName}.",
            ]);
        }

        $user->roles()->syncWithoutDetaching([
            $role->id_rol => [
                'activo' => true,
                'fecha_asignacion' => now(),
            ],
        ]);
    }

    private function coordinatorRoleName(): string
    {
        return 'COORDINADOR_ACADEMICO';
    }

    private function createdUserPayload(array $row, User $user, string $password, string $role): array
    {
        return [
            'fila' => $row['_line'],
            'ci' => $user->ci,
            'nombre_completo' => $user->nombre.' '.$user->apellido,
            'username' => $user->username,
            'password' => $password,
            'rol' => $role,
        ];
    }

    private function normalizeHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;

        return Str::of($header)
            ->trim()
            ->lower()
            ->replace(' ', '_')
            ->replace('-', '_')
            ->toString();
    }

    private function normalizeValue(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->lower()
            ->trim()
            ->squish()
            ->toString();
    }

    private function isEmptyRow(array $values): bool
    {
        return collect($values)->every(fn ($value) => trim((string) $value) === '');
    }
}
