<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('postulacion', function (Blueprint $table): void {
            if (! Schema::hasColumn('postulacion', 'turno_preferido')) {
                $table->string('turno_preferido', 20)->nullable()->after('estado_proceso');
            }
        });

        Schema::table('grupo_academico', function (Blueprint $table): void {
            if (! Schema::hasColumn('grupo_academico', 'turno')) {
                $table->string('turno', 20)->nullable()->after('nombre');
            }
        });

        Schema::table('horario', function (Blueprint $table): void {
            if (! Schema::hasColumn('horario', 'turno')) {
                $table->string('turno', 20)->nullable()->after('dia');
            }

            if (! Schema::hasColumn('horario', 'orden_materia')) {
                $table->unsignedTinyInteger('orden_materia')->nullable()->after('turno');
            }

            if (! Schema::hasColumn('horario', 'materia_referencia')) {
                $table->string('materia_referencia', 50)->nullable()->after('orden_materia');
            }
        });

        DB::table('postulacion')
            ->whereNull('turno_preferido')
            ->orWhere('turno_preferido', 'MEDIO_DIA')
            ->update(['turno_preferido' => 'MANANA']);

        DB::table('grupo_academico')
            ->whereNull('turno')
            ->orWhere('turno', 'MEDIO_DIA')
            ->update(['turno' => 'MANANA']);

        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_constraint WHERE conname = 'postulacion_turno_preferido_check'
                ) THEN
                    ALTER TABLE postulacion
                    ADD CONSTRAINT postulacion_turno_preferido_check
                    CHECK (turno_preferido IN ('MANANA', 'TARDE', 'NOCHE'));
                END IF;
            END
            $$;
        ");

        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_constraint WHERE conname = 'grupo_academico_turno_check'
                ) THEN
                    ALTER TABLE grupo_academico
                    ADD CONSTRAINT grupo_academico_turno_check
                    CHECK (turno IN ('MANANA', 'TARDE', 'NOCHE'));
                END IF;
            END
            $$;
        ");

        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_constraint WHERE conname = 'horario_turno_check'
                ) THEN
                    ALTER TABLE horario
                    ADD CONSTRAINT horario_turno_check
                    CHECK (turno IS NULL OR turno IN ('MANANA', 'TARDE', 'NOCHE'));
                END IF;

                IF NOT EXISTS (
                    SELECT 1 FROM pg_constraint WHERE conname = 'horario_orden_materia_check'
                ) THEN
                    ALTER TABLE horario
                    ADD CONSTRAINT horario_orden_materia_check
                    CHECK (orden_materia IS NULL OR orden_materia BETWEEN 1 AND 4);
                END IF;
            END
            $$;
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE horario DROP CONSTRAINT IF EXISTS horario_orden_materia_check');
        DB::statement('ALTER TABLE horario DROP CONSTRAINT IF EXISTS horario_turno_check');
        DB::statement('ALTER TABLE grupo_academico DROP CONSTRAINT IF EXISTS grupo_academico_turno_check');
        DB::statement('ALTER TABLE postulacion DROP CONSTRAINT IF EXISTS postulacion_turno_preferido_check');

        Schema::table('horario', function (Blueprint $table): void {
            if (Schema::hasColumn('horario', 'materia_referencia')) {
                $table->dropColumn('materia_referencia');
            }

            if (Schema::hasColumn('horario', 'orden_materia')) {
                $table->dropColumn('orden_materia');
            }

            if (Schema::hasColumn('horario', 'turno')) {
                $table->dropColumn('turno');
            }
        });

        Schema::table('grupo_academico', function (Blueprint $table): void {
            if (Schema::hasColumn('grupo_academico', 'turno')) {
                $table->dropColumn('turno');
            }
        });

        Schema::table('postulacion', function (Blueprint $table): void {
            if (Schema::hasColumn('postulacion', 'turno_preferido')) {
                $table->dropColumn('turno_preferido');
            }
        });
    }
};
