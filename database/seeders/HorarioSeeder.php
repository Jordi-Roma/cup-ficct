<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HorarioSeeder extends Seeder
{
    public function run(): void
    {
        $bloques = [
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

        $canonicalIds = [];

        foreach ($bloques as [$turno, $inicio, $fin]) {
            DB::table('horario')->updateOrInsert(
                [
                    'turno' => $turno,
                    'hora_inicio' => $inicio,
                    'hora_fin' => $fin,
                ],
                ['activo' => true]
            );

            $canonicalIds[] = DB::table('horario')
                ->where('turno', $turno)
                ->where('hora_inicio', $inicio)
                ->where('hora_fin', $fin)
                ->value('id_horario');
        }

        DB::table('horario')
            ->whereNotIn('id_horario', $canonicalIds)
            ->whereNotIn('id_horario', DB::table('asignacion_academica')->select('id_horario'))
            ->delete();
    }
}
