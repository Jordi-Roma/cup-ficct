<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE VIEW promedio_por_materia AS
            SELECT
                po.id_postulacion,
                u.ci,
                u.nombre,
                u.apellido,
                m.nombre AS materia,
                ROUND(AVG(n.nota), 2) AS promedio_materia
            FROM nota n
            JOIN postulacion po ON n.id_postulacion = po.id_postulacion
            JOIN postulante p ON po.id_postulante = p.id_postulante
            JOIN usuario u ON p.id_usuario = u.id_usuario
            JOIN materia_cup m ON n.id_materia = m.id_materia
            GROUP BY
                po.id_postulacion,
                u.ci,
                u.nombre,
                u.apellido,
                m.nombre
        SQL);

        DB::statement(<<<'SQL'
            CREATE VIEW resultado_final_postulante AS
            SELECT
                po.id_postulacion,
                u.ci,
                u.nombre,
                u.apellido,
                ROUND(AVG(n.nota), 2) AS promedio_final,
                CASE
                    WHEN AVG(n.nota) >= 60 THEN 'APROBADO'
                    ELSE 'REPROBADO'
                END AS estado_final
            FROM postulacion po
            JOIN postulante p ON po.id_postulante = p.id_postulante
            JOIN usuario u ON p.id_usuario = u.id_usuario
            JOIN nota n ON po.id_postulacion = n.id_postulacion
            GROUP BY
                po.id_postulacion,
                u.ci,
                u.nombre,
                u.apellido
        SQL);

        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION calcular_grupos(total_inscritos INT)
            RETURNS INT AS
            $$
            BEGIN
                RETURN CEIL(total_inscritos / 70.0);
            END;
            $$ LANGUAGE plpgsql
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS calcular_grupos(INT)');
        DB::statement('DROP VIEW IF EXISTS resultado_final_postulante');
        DB::statement('DROP VIEW IF EXISTS promedio_por_materia');
    }
};
