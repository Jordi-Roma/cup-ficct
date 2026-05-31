<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('password_reset_tokens', 'correo')) {
            return;
        }

        DB::statement('ALTER TABLE password_reset_tokens DROP CONSTRAINT IF EXISTS password_reset_tokens_correo_check');
        DB::statement('ALTER TABLE password_reset_tokens RENAME COLUMN correo TO email');
        DB::statement('ALTER TABLE password_reset_tokens RENAME COLUMN fecha_creacion TO created_at');
        DB::statement("ALTER TABLE password_reset_tokens ADD CONSTRAINT password_reset_tokens_email_check CHECK (email ~* '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('password_reset_tokens', 'email')) {
            return;
        }

        DB::statement('ALTER TABLE password_reset_tokens DROP CONSTRAINT IF EXISTS password_reset_tokens_email_check');
        DB::statement('ALTER TABLE password_reset_tokens RENAME COLUMN email TO correo');
        DB::statement('ALTER TABLE password_reset_tokens RENAME COLUMN created_at TO fecha_creacion');
        DB::statement("ALTER TABLE password_reset_tokens ADD CONSTRAINT password_reset_tokens_correo_check CHECK (correo ~* '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$')");
    }
};
