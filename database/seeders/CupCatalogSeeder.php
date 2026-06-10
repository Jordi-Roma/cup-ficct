<?php

namespace Database\Seeders;

use App\Modules\GestionAcademica\Models\Carrera;
use App\Modules\GestionAcademica\Models\CupoCarrera;
use App\Modules\GestionAcademica\Models\GestionAcademica;
use Illuminate\Database\Seeder;

class CupCatalogSeeder extends Seeder
{
    /**
     * Seed the application's CUP catalog data.
     */
    public function run(): void
    {
        $gestion = GestionAcademica::updateOrCreate(
            ['nombre' => '1-2026'],
            [
                'fecha_inicio' => '2026-01-01',
                'fecha_fin' => '2026-06-30',
                'activo' => true,
            ],
        );

        foreach ([
            'Ingeniería en Sistemas',
            'Ingeniería Informática',
            'Ingeniería en Redes y Telecomunicaciones',
            'Ingeniería en Robótica',
        ] as $nombreCarrera) {
            $carrera = Carrera::updateOrCreate(
                ['nombre' => $nombreCarrera],
                ['activo' => true],
            );

            CupoCarrera::updateOrCreate(
                [
                    'id_carrera' => $carrera->id_carrera,
                    'id_gestion' => $gestion->id_gestion,
                ],
                ['cupo_maximo' => 70],
            );
        }
    }
}
