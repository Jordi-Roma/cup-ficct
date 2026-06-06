<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $blocks = [
        ['MANANA', '07:00', '08:00'],
        ['MANANA', '08:00', '09:00'],
        ['MANANA', '09:00', '10:00'],
        ['MANANA', '10:00', '11:00'],
        ['TARDE', '15:00', '16:00'],
        ['TARDE', '16:00', '17:00'],
        ['TARDE', '17:00', '18:00'],
        ['TARDE', '18:00', '19:00'],
        ['NOCHE', '19:00', '20:00'],
        ['NOCHE', '20:00', '21:00'],
        ['NOCHE', '21:00', '22:00'],
        ['NOCHE', '22:00', '23:00'],
    ];

    public function up(): void
    {
        DB::statement('ALTER TABLE horario DROP CONSTRAINT IF EXISTS horario_dia_check');
        DB::statement('ALTER TABLE horario DROP CONSTRAINT IF EXISTS horario_orden_materia_check');
        DB::statement('ALTER TABLE horario DROP CONSTRAINT IF EXISTS horario_turno_check');

        $canonicalIds = [];

        foreach ($this->blocks as [$turno, $inicio, $fin]) {
            $canonical = DB::table('horario')
                ->where('turno', $turno)
                ->where('hora_inicio', $inicio)
                ->where('hora_fin', $fin)
                ->orderBy('id_horario')
                ->first();

            if (! $canonical) {
                $insert = [
                    'turno' => $turno,
                    'hora_inicio' => $inicio,
                    'hora_fin' => $fin,
                    'activo' => true,
                ];

                if (Schema::hasColumn('horario', 'dia')) {
                    $insert['dia'] = 'LUNES';
                }

                if (Schema::hasColumn('horario', 'orden_materia')) {
                    $insert['orden_materia'] = null;
                }

                if (Schema::hasColumn('horario', 'materia_referencia')) {
                    $insert['materia_referencia'] = null;
                }

                $canonicalId = DB::table('horario')->insertGetId($insert, 'id_horario');
            } else {
                $canonicalId = $canonical->id_horario;

                DB::table('horario')
                    ->where('id_horario', $canonicalId)
                    ->update([
                        'turno' => $turno,
                        'hora_inicio' => $inicio,
                        'hora_fin' => $fin,
                        'activo' => true,
                    ]);
            }

            DB::table('asignacion_academica')
                ->whereIn('id_horario', function ($query) use ($turno, $inicio, $fin): void {
                    $query->select('id_horario')
                        ->from('horario')
                        ->where('turno', $turno)
                        ->where('hora_inicio', $inicio)
                        ->where('hora_fin', $fin);
                })
                ->update(['id_horario' => $canonicalId]);

            $canonicalIds[] = $canonicalId;
        }

        foreach ($this->blocks as [$turno, $inicio, $fin]) {
            $canonicalId = DB::table('horario')
                ->where('turno', $turno)
                ->where('hora_inicio', $inicio)
                ->where('hora_fin', $fin)
                ->value('id_horario');

            DB::table('asignacion_academica')
                ->whereIn('id_horario', function ($query) use ($turno, $inicio, $canonicalIds): void {
                    $query->select('id_horario')
                        ->from('horario')
                        ->where('turno', $turno)
                        ->where('hora_inicio', $inicio)
                        ->whereNotIn('id_horario', $canonicalIds);
                })
                ->update(['id_horario' => $canonicalId]);
        }

        DB::table('horario')
            ->whereNotIn('id_horario', $canonicalIds)
            ->whereNotIn('id_horario', DB::table('asignacion_academica')->select('id_horario'))
            ->delete();

        Schema::table('horario', function (Blueprint $table): void {
            if (Schema::hasColumn('horario', 'dia')) {
                $table->dropColumn('dia');
            }

            if (Schema::hasColumn('horario', 'orden_materia')) {
                $table->dropColumn('orden_materia');
            }

            if (Schema::hasColumn('horario', 'materia_referencia')) {
                $table->dropColumn('materia_referencia');
            }
        });

        DB::table('horario')
            ->whereNull('turno')
            ->update(['turno' => 'MANANA']);

        DB::statement("ALTER TABLE horario ALTER COLUMN turno SET NOT NULL");
        DB::statement("ALTER TABLE horario ADD CONSTRAINT horario_turno_check CHECK (turno IN ('MANANA', 'TARDE', 'NOCHE'))");
        DB::statement('ALTER TABLE horario ADD CONSTRAINT horario_turno_horas_unique UNIQUE (turno, hora_inicio, hora_fin)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE horario DROP CONSTRAINT IF EXISTS horario_turno_horas_unique');
        DB::statement('ALTER TABLE horario DROP CONSTRAINT IF EXISTS horario_turno_check');

        Schema::table('horario', function (Blueprint $table): void {
            if (! Schema::hasColumn('horario', 'dia')) {
                $table->string('dia', 20)->nullable()->after('id_horario');
            }

            if (! Schema::hasColumn('horario', 'orden_materia')) {
                $table->unsignedTinyInteger('orden_materia')->nullable()->after('turno');
            }

            if (! Schema::hasColumn('horario', 'materia_referencia')) {
                $table->string('materia_referencia', 50)->nullable()->after('orden_materia');
            }
        });

        DB::table('horario')->whereNull('dia')->update(['dia' => 'LUNES']);

        DB::statement("ALTER TABLE horario ADD CONSTRAINT horario_turno_check CHECK (turno IS NULL OR turno IN ('MANANA', 'TARDE', 'NOCHE'))");
        DB::statement("ALTER TABLE horario ADD CONSTRAINT horario_dia_check CHECK (dia IN ('LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO'))");
        DB::statement('ALTER TABLE horario ADD CONSTRAINT horario_orden_materia_check CHECK (orden_materia IS NULL OR orden_materia BETWEEN 1 AND 4)');
    }
};
