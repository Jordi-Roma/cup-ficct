<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HorarioSeeder extends Seeder
{
    public function run(): void
    {
        $horarios = [
            ['LUNES', '08:00', '10:00'],
            ['LUNES', '10:00', '12:00'],
            ['MARTES', '08:00', '10:00'],
            ['MARTES', '10:00', '12:00'],
            ['MIERCOLES', '08:00', '10:00'],
            ['MIERCOLES', '10:00', '12:00'],
            ['JUEVES', '08:00', '10:00'],
            ['JUEVES', '10:00', '12:00'],
            ['VIERNES', '08:00', '10:00'],
            ['VIERNES', '10:00', '12:00'],
            ['SABADO', '08:00', '10:00'],
        ];

        foreach ($horarios as [$dia, $inicio, $fin]) {
            $exists = DB::table('horario')
                ->where([
                    'dia' => $dia,
                    'hora_inicio' => $inicio,
                    'hora_fin' => $fin,
                ])
                ->exists();

            if (! $exists) {
                DB::table('horario')->insert([
                    'dia' => $dia,
                    'hora_inicio' => $inicio,
                    'hora_fin' => $fin,
                    'activo' => true,
                ]);
            } else {
                DB::table('horario')
                    ->where([
                        'dia' => $dia,
                        'hora_inicio' => $inicio,
                        'hora_fin' => $fin,
                    ])
                    ->update(['activo' => true]);
            }
        }
    }
}
