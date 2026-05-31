<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rol', function (Blueprint $table) {
            $table->id('id_rol');
            $table->string('nombre', 50)->unique();
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamp('fecha_creacion')->useCurrent();
        });

        Schema::create('permiso', function (Blueprint $table) {
            $table->id('id_permiso');
            $table->string('nombre', 100)->unique();
            $table->string('modulo', 50);
            $table->string('accion', 50);
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
        });

        Schema::create('rol_usuario', function (Blueprint $table) {
            $table->foreignId('id_usuario')->constrained('usuario', 'id_usuario')->cascadeOnDelete();
            $table->foreignId('id_rol')->constrained('rol', 'id_rol')->cascadeOnDelete();
            $table->timestamp('fecha_asignacion')->useCurrent();
            $table->timestamp('fecha_expiracion')->nullable();
            $table->boolean('activo')->default(true);
            $table->primary(['id_usuario', 'id_rol']);
        });

        Schema::create('rol_permiso', function (Blueprint $table) {
            $table->foreignId('id_rol')->constrained('rol', 'id_rol')->cascadeOnDelete();
            $table->foreignId('id_permiso')->constrained('permiso', 'id_permiso')->cascadeOnDelete();
            $table->timestamp('fecha_asignacion')->useCurrent();
            $table->boolean('activo')->default(true);
            $table->primary(['id_rol', 'id_permiso']);
        });

        Schema::create('sesion', function (Blueprint $table) {
            $table->id('id_sesion');
            $table->foreignId('id_usuario')->constrained('usuario', 'id_usuario')->cascadeOnDelete();
            $table->string('token_sesion')->unique();
            $table->string('refresh_token')->unique()->nullable();
            $table->timestamp('fecha_inicio')->useCurrent();
            $table->timestamp('fecha_expiracion')->nullable();
            $table->timestamp('fecha_cierre')->nullable();
            $table->text('user_agent')->nullable();
        });

        DB::statement('ALTER TABLE sesion ADD COLUMN ip_origen INET');

        Schema::create('gestion_academica', function (Blueprint $table) {
            $table->id('id_gestion');
            $table->string('nombre', 30)->unique();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->boolean('activo')->default(true);
        });

        Schema::create('carrera', function (Blueprint $table) {
            $table->id('id_carrera');
            $table->string('nombre', 120)->unique();
            $table->boolean('activo')->default(true);
        });

        Schema::create('cupo_carrera', function (Blueprint $table) {
            $table->id('id_cupo');
            $table->foreignId('id_carrera')->constrained('carrera', 'id_carrera');
            $table->foreignId('id_gestion')->constrained('gestion_academica', 'id_gestion');
            $table->integer('cupo_maximo');
            $table->unique(['id_carrera', 'id_gestion']);
        });

        Schema::create('postulante', function (Blueprint $table) {
            $table->id('id_postulante');
            $table->foreignId('id_usuario')->unique()->constrained('usuario', 'id_usuario')->cascadeOnDelete();
            $table->date('fecha_nacimiento');
            $table->text('direccion')->nullable();
            $table->string('colegio_procedencia', 150)->nullable();
            $table->string('ciudad', 80)->nullable();
            $table->boolean('documentacion_completa')->default(false);
        });

        Schema::create('docente', function (Blueprint $table) {
            $table->id('id_docente');
            $table->foreignId('id_usuario')->unique()->constrained('usuario', 'id_usuario')->cascadeOnDelete();
            $table->boolean('profesional_area')->default(false);
            $table->boolean('maestria')->default(false);
            $table->boolean('diplomado_educacion_superior')->default(false);
            $table->boolean('contratado')->default(false);
            $table->boolean('activo')->default(true);
        });

        Schema::create('materia_cup', function (Blueprint $table) {
            $table->id('id_materia');
            $table->string('nombre', 80)->unique();
            $table->boolean('activo')->default(true);
        });

        Schema::create('aula', function (Blueprint $table) {
            $table->id('id_aula');
            $table->string('nombre', 50)->unique();
            $table->integer('capacidad');
        });

        Schema::create('horario', function (Blueprint $table) {
            $table->id('id_horario');
            $table->string('dia', 20);
            $table->time('hora_inicio');
            $table->time('hora_fin');
        });

        Schema::create('grupo_academico', function (Blueprint $table) {
            $table->id('id_grupo');
            $table->foreignId('id_gestion')->constrained('gestion_academica', 'id_gestion');
            $table->string('nombre', 50);
            $table->integer('capacidad_maxima')->default(70);
            $table->boolean('activo')->default(true);
            $table->unique(['id_gestion', 'nombre']);
        });

        Schema::create('asignacion_academica', function (Blueprint $table) {
            $table->id('id_asignacion');
            $table->foreignId('id_grupo')->constrained('grupo_academico', 'id_grupo');
            $table->foreignId('id_materia')->constrained('materia_cup', 'id_materia');
            $table->foreignId('id_docente')->constrained('docente', 'id_docente');
            $table->foreignId('id_aula')->constrained('aula', 'id_aula');
            $table->foreignId('id_horario')->constrained('horario', 'id_horario');
            $table->boolean('activo')->default(true);
            $table->unique(['id_grupo', 'id_materia']);
            $table->unique(['id_docente', 'id_horario']);
            $table->unique(['id_aula', 'id_horario']);
        });

        Schema::create('postulacion', function (Blueprint $table) {
            $table->id('id_postulacion');
            $table->foreignId('id_postulante')->constrained('postulante', 'id_postulante');
            $table->foreignId('id_gestion')->constrained('gestion_academica', 'id_gestion');
            $table->foreignId('id_carrera_opcion1')->constrained('carrera', 'id_carrera');
            $table->foreignId('id_carrera_opcion2')->nullable()->constrained('carrera', 'id_carrera');
            $table->foreignId('id_carrera_admitida')->nullable()->constrained('carrera', 'id_carrera');
            $table->foreignId('id_grupo')->nullable()->constrained('grupo_academico', 'id_grupo');
            $table->string('estado_admision', 20)->default('PENDIENTE');
            $table->timestamp('fecha_postulacion')->useCurrent();
            $table->unique(['id_postulante', 'id_gestion']);
        });

        Schema::create('pago_inscripcion', function (Blueprint $table) {
            $table->id('id_pago');
            $table->foreignId('id_postulacion')->constrained('postulacion', 'id_postulacion');
            $table->decimal('monto', 10, 2);
            $table->string('moneda', 10)->default('BOB');
            $table->string('pasarela', 50);
            $table->string('numero_transaccion', 150)->unique();
            $table->string('codigo_autorizacion', 100)->nullable();
            $table->string('codigo_error', 100)->nullable();
            $table->string('estado_pago', 30)->default('PENDIENTE');
            $table->timestamp('fecha_inicio')->useCurrent();
            $table->timestamp('fecha_confirmacion')->nullable();
        });

        Schema::create('nota', function (Blueprint $table) {
            $table->id('id_nota');
            $table->foreignId('id_postulacion')->constrained('postulacion', 'id_postulacion');
            $table->foreignId('id_materia')->constrained('materia_cup', 'id_materia');
            $table->integer('nro_examen');
            $table->decimal('nota', 5, 2);
            $table->timestamp('fecha_registro')->useCurrent();
            $table->foreignId('registrado_por')->nullable()->constrained('usuario', 'id_usuario');
            $table->unique(['id_postulacion', 'id_materia', 'nro_examen']);
        });

        Schema::create('log_auditoria', function (Blueprint $table) {
            $table->id('id_log');
            $table->string('tabla_afectada', 100);
            $table->string('operacion', 20);
            $table->string('id_registro', 50)->nullable();
            $table->jsonb('datos_anteriores')->nullable();
            $table->jsonb('datos_nuevos')->nullable();
            $table->foreignId('id_usuario')->nullable()->constrained('usuario', 'id_usuario');
            $table->text('user_agent')->nullable();
            $table->timestamp('fecha_operacion')->useCurrent();
        });

        DB::statement('ALTER TABLE log_auditoria ADD COLUMN ip_origen INET');

        $this->addCheckConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_auditoria');
        Schema::dropIfExists('nota');
        Schema::dropIfExists('pago_inscripcion');
        Schema::dropIfExists('postulacion');
        Schema::dropIfExists('asignacion_academica');
        Schema::dropIfExists('grupo_academico');
        Schema::dropIfExists('horario');
        Schema::dropIfExists('aula');
        Schema::dropIfExists('materia_cup');
        Schema::dropIfExists('docente');
        Schema::dropIfExists('postulante');
        Schema::dropIfExists('cupo_carrera');
        Schema::dropIfExists('carrera');
        Schema::dropIfExists('gestion_academica');
        Schema::dropIfExists('sesion');
        Schema::dropIfExists('rol_permiso');
        Schema::dropIfExists('rol_usuario');
        Schema::dropIfExists('permiso');
        Schema::dropIfExists('rol');
    }

    private function addCheckConstraints(): void
    {
        DB::statement("ALTER TABLE permiso ADD CONSTRAINT permiso_accion_check CHECK (accion IN ('CREAR', 'LEER', 'ACTUALIZAR', 'ELIMINAR', 'EJECUTAR'))");
        DB::statement('ALTER TABLE rol_usuario ADD CONSTRAINT rol_usuario_fecha_expiracion_check CHECK (fecha_expiracion IS NULL OR fecha_expiracion >= fecha_asignacion)');
        DB::statement('ALTER TABLE sesion ADD CONSTRAINT sesion_fecha_expiracion_check CHECK (fecha_expiracion IS NULL OR fecha_expiracion > fecha_inicio)');
        DB::statement('ALTER TABLE sesion ADD CONSTRAINT sesion_fecha_cierre_check CHECK (fecha_cierre IS NULL OR fecha_cierre >= fecha_inicio)');
        DB::statement('ALTER TABLE gestion_academica ADD CONSTRAINT gestion_academica_fechas_check CHECK (fecha_fin > fecha_inicio)');
        DB::statement('ALTER TABLE cupo_carrera ADD CONSTRAINT cupo_carrera_cupo_maximo_check CHECK (cupo_maximo > 0)');
        DB::statement('ALTER TABLE docente ADD CONSTRAINT docente_contratado_check CHECK (contratado = FALSE OR (profesional_area = TRUE AND maestria = TRUE AND diplomado_educacion_superior = TRUE))');
        DB::statement('ALTER TABLE aula ADD CONSTRAINT aula_capacidad_check CHECK (capacidad > 0)');
        DB::statement("ALTER TABLE horario ADD CONSTRAINT horario_dia_check CHECK (dia IN ('LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO'))");
        DB::statement('ALTER TABLE horario ADD CONSTRAINT horario_horas_check CHECK (hora_fin > hora_inicio)');
        DB::statement('ALTER TABLE grupo_academico ADD CONSTRAINT grupo_academico_capacidad_maxima_check CHECK (capacidad_maxima > 0 AND capacidad_maxima <= 70)');
        DB::statement("ALTER TABLE postulacion ADD CONSTRAINT postulacion_estado_admision_check CHECK (estado_admision IN ('PENDIENTE', 'ADMITIDO', 'NO_ADMITIDO'))");
        DB::statement('ALTER TABLE postulacion ADD CONSTRAINT postulacion_carreras_distintas_check CHECK (id_carrera_opcion2 IS NULL OR id_carrera_opcion1 <> id_carrera_opcion2)');
        DB::statement('ALTER TABLE pago_inscripcion ADD CONSTRAINT pago_inscripcion_monto_check CHECK (monto > 0)');
        DB::statement("ALTER TABLE pago_inscripcion ADD CONSTRAINT pago_inscripcion_pasarela_check CHECK (pasarela IN ('PAYPAL', 'STRIPE'))");
        DB::statement("ALTER TABLE pago_inscripcion ADD CONSTRAINT pago_inscripcion_estado_pago_check CHECK (estado_pago IN ('PENDIENTE', 'PROCESANDO', 'APROBADO', 'RECHAZADO', 'CANCELADO'))");
        DB::statement('ALTER TABLE pago_inscripcion ADD CONSTRAINT pago_inscripcion_fecha_confirmacion_check CHECK (fecha_confirmacion IS NULL OR fecha_confirmacion >= fecha_inicio)');
        DB::statement('ALTER TABLE nota ADD CONSTRAINT nota_nro_examen_check CHECK (nro_examen BETWEEN 1 AND 3)');
        DB::statement('ALTER TABLE nota ADD CONSTRAINT nota_nota_check CHECK (nota BETWEEN 0 AND 100)');
        DB::statement("ALTER TABLE log_auditoria ADD CONSTRAINT log_auditoria_operacion_check CHECK (operacion IN ('INSERT', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT'))");
    }
};
