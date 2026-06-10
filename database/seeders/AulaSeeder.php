<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AulaSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Aula 1', 'Aula 2', 'Aula 3','Aula 4', 'Aula 5', 'Aula 6','Aula 7', 'Aula 8', 'Aula 9'] as $nombre) {
            DB::table('aula')->updateOrInsert(
                ['nombre' => $nombre],
                ['capacidad' => 70, 'activo' => true],
            );
        }
    }
}
