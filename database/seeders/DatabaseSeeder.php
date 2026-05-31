<?php

namespace Database\Seeders;

use App\Modules\Autenticacion\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CupCatalogSeeder::class);
        $this->call(MateriaCupSeeder::class);
        $this->call(AulaSeeder::class);
        $this->call(HorarioSeeder::class);

        User::updateOrCreate([
            'ci' => '00000001',
        ], [
            'nombre' => 'Test',
            'apellido' => 'User',
            'username' => 'testuser',
            'correo' => 'test@example.com',
            'password_hash' => Hash::make('password'),
            'sexo' => 'O',
            'estado_acceso' => 'HABILITADO',
            'activo' => true,
        ]);

        $this->call(AccessControlSeeder::class);
    }
}
