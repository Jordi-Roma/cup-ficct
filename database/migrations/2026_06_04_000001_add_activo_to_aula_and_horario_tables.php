<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aula', function (Blueprint $table) {
            if (! Schema::hasColumn('aula', 'activo')) {
                $table->boolean('activo')->default(true);
            }
        });

        Schema::table('horario', function (Blueprint $table) {
            if (! Schema::hasColumn('horario', 'activo')) {
                $table->boolean('activo')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('horario', function (Blueprint $table) {
            if (Schema::hasColumn('horario', 'activo')) {
                $table->dropColumn('activo');
            }
        });

        Schema::table('aula', function (Blueprint $table) {
            if (Schema::hasColumn('aula', 'activo')) {
                $table->dropColumn('activo');
            }
        });
    }
};
