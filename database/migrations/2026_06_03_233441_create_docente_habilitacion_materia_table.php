<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('docente', function (Blueprint $table) {
            if (! Schema::hasColumn('docente', 'maestria_educacion_superior')) {
                $table->boolean('maestria_educacion_superior')->default(false);
            }
        });

        DB::statement('ALTER TABLE docente DROP CONSTRAINT IF EXISTS docente_contratado_check');
        DB::statement('ALTER TABLE docente ADD CONSTRAINT docente_contratado_check CHECK (contratado = FALSE OR maestria_educacion_superior = TRUE)');

        Schema::create('docente_habilitacion_materia', function (Blueprint $table) {
            $table->id('id_habilitacion');
            $table->foreignId('id_docente')->constrained('docente', 'id_docente')->cascadeOnDelete();
            $table->foreignId('id_materia')->constrained('materia_cup', 'id_materia')->cascadeOnDelete();
            $table->string('tipo_habilitacion', 30);
            $table->boolean('activo')->default(true);
            $table->timestamp('fecha_registro')->useCurrent();

            $table->unique(['id_docente', 'id_materia', 'tipo_habilitacion'], 'docente_habilitacion_unique');
            $table->index('id_docente');
            $table->index('id_materia');
            $table->index('activo');
        });

        DB::statement("ALTER TABLE docente_habilitacion_materia ADD CONSTRAINT docente_habilitacion_tipo_check CHECK (tipo_habilitacion IN ('PROFESIONAL_AREA', 'DIPLOMADO', 'MAESTRIA'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('docente_habilitacion_materia');

        DB::statement('ALTER TABLE docente DROP CONSTRAINT IF EXISTS docente_contratado_check');
        DB::statement('ALTER TABLE docente ADD CONSTRAINT docente_contratado_check CHECK (contratado = FALSE OR (profesional_area = TRUE AND maestria = TRUE AND diplomado_educacion_superior = TRUE))');

        Schema::table('docente', function (Blueprint $table) {
            if (Schema::hasColumn('docente', 'maestria_educacion_superior')) {
                $table->dropColumn('maestria_educacion_superior');
            }
        });
    }
};
