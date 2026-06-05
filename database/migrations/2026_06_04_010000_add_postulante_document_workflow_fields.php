<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('postulante', function (Blueprint $table): void {
            if (! Schema::hasColumn('postulante', 'presento_titulo_bachiller')) {
                $table->boolean('presento_titulo_bachiller')->default(false);
            }

            if (! Schema::hasColumn('postulante', 'presento_fotocopia_carnet')) {
                $table->boolean('presento_fotocopia_carnet')->default(false);
            }

            if (! Schema::hasColumn('postulante', 'documentacion_validada')) {
                $table->boolean('documentacion_validada')->default(false);
            }

            if (! Schema::hasColumn('postulante', 'fecha_validacion_documentos')) {
                $table->timestamp('fecha_validacion_documentos')->nullable();
            }

            if (! Schema::hasColumn('postulante', 'validado_por')) {
                $table->foreignId('validado_por')
                    ->nullable()
                    ->constrained('usuario', 'id_usuario')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('postulante', 'creado_por_admin')) {
                $table->boolean('creado_por_admin')->default(false);
            }

            if (! Schema::hasColumn('postulante', 'requiere_pago')) {
                $table->boolean('requiere_pago')->default(true);
            }
        });

        Schema::table('postulacion', function (Blueprint $table): void {
            if (! Schema::hasColumn('postulacion', 'estado_proceso')) {
                $table->string('estado_proceso', 40)->default('PENDIENTE_VALIDACION');
            }
        });
    }

    public function down(): void
    {
        Schema::table('postulante', function (Blueprint $table): void {
            foreach ([
                'presento_titulo_bachiller',
                'presento_fotocopia_carnet',
                'documentacion_validada',
                'fecha_validacion_documentos',
                'creado_por_admin',
                'requiere_pago',
            ] as $column) {
                if (Schema::hasColumn('postulante', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('postulante', 'validado_por')) {
                $table->dropConstrainedForeignId('validado_por');
            }
        });

        Schema::table('postulacion', function (Blueprint $table): void {
            if (Schema::hasColumn('postulacion', 'estado_proceso')) {
                $table->dropColumn('estado_proceso');
            }
        });
    }
};
