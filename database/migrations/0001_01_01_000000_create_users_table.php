<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usuario', function (Blueprint $table) {
            $table->id('id_usuario');
            $table->string('ci', 20)->unique();
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('username', 50)->unique();
            $table->string('correo', 150)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password_hash');
            $table->string('telefono', 20)->nullable();
            $table->char('sexo', 1)->nullable();
            $table->integer('intentos_fallidos')->default(0);
            $table->string('estado_acceso', 20)->default('HABILITADO');
            $table->boolean('activo')->default(true);
            $table->timestamp('fecha_registro')->useCurrent();
            $table->rememberToken();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 150)->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        DB::statement("ALTER TABLE usuario ADD CONSTRAINT usuario_sexo_check CHECK (sexo IN ('M', 'F', 'O'))");
        DB::statement('ALTER TABLE usuario ADD CONSTRAINT usuario_intentos_fallidos_check CHECK (intentos_fallidos >= 0)');
        DB::statement("ALTER TABLE usuario ADD CONSTRAINT usuario_estado_acceso_check CHECK (estado_acceso IN ('HABILITADO', 'SUSPENDIDO', 'BLOQUEADO'))");
        DB::statement("ALTER TABLE usuario ADD CONSTRAINT usuario_correo_check CHECK (correo ~* '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$')");
        DB::statement("ALTER TABLE password_reset_tokens ADD CONSTRAINT password_reset_tokens_email_check CHECK (email ~* '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('usuario');
    }
};
