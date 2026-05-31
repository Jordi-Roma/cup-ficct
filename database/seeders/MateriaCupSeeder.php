<?php

namespace Database\Seeders;

use App\Modules\GestionAcademica\Models\MateriaCup;
use Illuminate\Database\Seeder;

class MateriaCupSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Computación', 'Matemáticas', 'Inglés', 'Física'] as $nombre) {
            MateriaCup::updateOrCreate(
                ['nombre' => $nombre],
                ['activo' => true],
            );
        }
    }
}
