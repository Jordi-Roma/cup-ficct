-- Script de estructura adaptado para Laravel/Fortify + PostgreSQL.
-- Proyecto: CUP-FICCT
-- Nota: Laravel puede reconstruir esta estructura con:
-- php artisan migrate
-- php artisan db:seed

-- CREATE DATABASE admision_cup_ficct;
-- \c admision_cup_ficct;

-- =========================
-- USUARIOS, SESIONES Y FORTIFY
-- =========================

CREATE TABLE usuario (
    id_usuario BIGSERIAL PRIMARY KEY,
    ci VARCHAR(20) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    correo VARCHAR(150) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password_hash VARCHAR(255) NOT NULL,
    two_factor_secret TEXT NULL,
    two_factor_recovery_codes TEXT NULL,
    two_factor_confirmed_at TIMESTAMP NULL,
    telefono VARCHAR(20),
    sexo CHAR(1),
    intentos_fallidos INT DEFAULT 0 NOT NULL,
    estado_acceso VARCHAR(20) DEFAULT 'HABILITADO' NOT NULL,
    activo BOOLEAN DEFAULT TRUE NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    remember_token VARCHAR(100),
    CONSTRAINT usuario_sexo_check CHECK (sexo IN ('M', 'F', 'O')),
    CONSTRAINT usuario_intentos_fallidos_check CHECK (intentos_fallidos >= 0),
    CONSTRAINT usuario_estado_acceso_check CHECK (estado_acceso IN ('HABILITADO', 'SUSPENDIDO', 'BLOQUEADO')),
    CONSTRAINT usuario_correo_check CHECK (correo ~* '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$')
);

CREATE TABLE password_reset_tokens (
    email VARCHAR(150) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    CONSTRAINT password_reset_tokens_email_check CHECK (email ~* '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$')
);

CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload TEXT NOT NULL,
    last_activity INT NOT NULL
);

CREATE INDEX sessions_user_id_index ON sessions (user_id);
CREATE INDEX sessions_last_activity_index ON sessions (last_activity);

CREATE TABLE passkeys (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    credential_id VARCHAR(255) UNIQUE NOT NULL,
    credential JSON NOT NULL,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE INDEX passkeys_user_id_index ON passkeys (user_id);

CREATE TABLE sesion (
    id_sesion BIGSERIAL PRIMARY KEY,
    id_usuario BIGINT NOT NULL REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    token_sesion VARCHAR(255) UNIQUE NOT NULL,
    refresh_token VARCHAR(255) UNIQUE,
    fecha_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    fecha_expiracion TIMESTAMP,
    fecha_cierre TIMESTAMP,
    ip_origen INET,
    user_agent TEXT,
    CONSTRAINT sesion_fecha_expiracion_check CHECK (fecha_expiracion IS NULL OR fecha_expiracion > fecha_inicio),
    CONSTRAINT sesion_fecha_cierre_check CHECK (fecha_cierre IS NULL OR fecha_cierre >= fecha_inicio)
);

-- =========================
-- ROLES Y PERMISOS
-- =========================

CREATE TABLE rol (
    id_rol BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(50) UNIQUE NOT NULL,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL
);

CREATE TABLE permiso (
    id_permiso BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(100) UNIQUE NOT NULL,
    modulo VARCHAR(50) NOT NULL,
    accion VARCHAR(50) NOT NULL,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE NOT NULL,
    CONSTRAINT permiso_accion_check CHECK (accion IN ('CREAR', 'LEER', 'ACTUALIZAR', 'ELIMINAR', 'EJECUTAR'))
);

CREATE TABLE rol_usuario (
    id_usuario BIGINT NOT NULL REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    id_rol BIGINT NOT NULL REFERENCES rol(id_rol) ON DELETE CASCADE,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    fecha_expiracion TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE NOT NULL,
    PRIMARY KEY (id_usuario, id_rol),
    CONSTRAINT rol_usuario_fecha_expiracion_check CHECK (fecha_expiracion IS NULL OR fecha_expiracion >= fecha_asignacion)
);

CREATE TABLE rol_permiso (
    id_rol BIGINT NOT NULL REFERENCES rol(id_rol) ON DELETE CASCADE,
    id_permiso BIGINT NOT NULL REFERENCES permiso(id_permiso) ON DELETE CASCADE,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    activo BOOLEAN DEFAULT TRUE NOT NULL,
    PRIMARY KEY (id_rol, id_permiso)
);

-- =========================
-- GESTION ACADEMICA, CARRERAS Y CUPOS
-- =========================

CREATE TABLE gestion_academica (
    id_gestion BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(30) UNIQUE NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    activo BOOLEAN DEFAULT TRUE NOT NULL,
    CONSTRAINT gestion_academica_fechas_check CHECK (fecha_fin > fecha_inicio)
);

CREATE TABLE carrera (
    id_carrera BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(120) UNIQUE NOT NULL,
    activo BOOLEAN DEFAULT TRUE NOT NULL
);

CREATE TABLE cupo_carrera (
    id_cupo BIGSERIAL PRIMARY KEY,
    id_carrera BIGINT NOT NULL REFERENCES carrera(id_carrera),
    id_gestion BIGINT NOT NULL REFERENCES gestion_academica(id_gestion),
    cupo_maximo INT NOT NULL,
    UNIQUE (id_carrera, id_gestion),
    CONSTRAINT cupo_carrera_cupo_maximo_check CHECK (cupo_maximo > 0)
);

-- =========================
-- POSTULANTES Y DOCENTES
-- =========================

CREATE TABLE postulante (
    id_postulante BIGSERIAL PRIMARY KEY,
    id_usuario BIGINT UNIQUE NOT NULL REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    fecha_nacimiento DATE NOT NULL,
    direccion TEXT,
    colegio_procedencia VARCHAR(150),
    ciudad VARCHAR(80),
    documentacion_completa BOOLEAN DEFAULT FALSE NOT NULL
);

CREATE TABLE docente (
    id_docente BIGSERIAL PRIMARY KEY,
    id_usuario BIGINT UNIQUE NOT NULL REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    profesional_area BOOLEAN DEFAULT FALSE NOT NULL,
    maestria BOOLEAN DEFAULT FALSE NOT NULL,
    diplomado_educacion_superior BOOLEAN DEFAULT FALSE NOT NULL,
    contratado BOOLEAN DEFAULT FALSE NOT NULL,
    activo BOOLEAN DEFAULT TRUE NOT NULL,
    CONSTRAINT docente_contratado_check CHECK (
        contratado = FALSE
        OR (
            profesional_area = TRUE
            AND maestria = TRUE
            AND diplomado_educacion_superior = TRUE
        )
    )
);

-- =========================
-- MATERIAS, AULAS Y HORARIOS
-- =========================

CREATE TABLE materia_cup (
    id_materia BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(80) UNIQUE NOT NULL,
    activo BOOLEAN DEFAULT TRUE NOT NULL
);

CREATE TABLE aula (
    id_aula BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(50) UNIQUE NOT NULL,
    capacidad INT NOT NULL,
    CONSTRAINT aula_capacidad_check CHECK (capacidad > 0)
);

CREATE TABLE horario (
    id_horario BIGSERIAL PRIMARY KEY,
    dia VARCHAR(20) NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    CONSTRAINT horario_dia_check CHECK (dia IN ('LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO')),
    CONSTRAINT horario_horas_check CHECK (hora_fin > hora_inicio)
);

-- =========================
-- GRUPOS Y ASIGNACION ACADEMICA
-- =========================

CREATE TABLE grupo_academico (
    id_grupo BIGSERIAL PRIMARY KEY,
    id_gestion BIGINT NOT NULL REFERENCES gestion_academica(id_gestion),
    nombre VARCHAR(50) NOT NULL,
    capacidad_maxima INT DEFAULT 70 NOT NULL,
    activo BOOLEAN DEFAULT TRUE NOT NULL,
    UNIQUE (id_gestion, nombre),
    CONSTRAINT grupo_academico_capacidad_maxima_check CHECK (capacidad_maxima > 0 AND capacidad_maxima <= 70)
);

CREATE TABLE asignacion_academica (
    id_asignacion BIGSERIAL PRIMARY KEY,
    id_grupo BIGINT NOT NULL REFERENCES grupo_academico(id_grupo),
    id_materia BIGINT NOT NULL REFERENCES materia_cup(id_materia),
    id_docente BIGINT NOT NULL REFERENCES docente(id_docente),
    id_aula BIGINT NOT NULL REFERENCES aula(id_aula),
    id_horario BIGINT NOT NULL REFERENCES horario(id_horario),
    activo BOOLEAN DEFAULT TRUE NOT NULL,
    UNIQUE (id_grupo, id_materia),
    UNIQUE (id_docente, id_horario),
    UNIQUE (id_aula, id_horario)
);

-- =========================
-- POSTULACIONES, PAGOS Y NOTAS
-- =========================

CREATE TABLE postulacion (
    id_postulacion BIGSERIAL PRIMARY KEY,
    id_postulante BIGINT NOT NULL REFERENCES postulante(id_postulante),
    id_gestion BIGINT NOT NULL REFERENCES gestion_academica(id_gestion),
    id_carrera_opcion1 BIGINT NOT NULL REFERENCES carrera(id_carrera),
    id_carrera_opcion2 BIGINT REFERENCES carrera(id_carrera),
    id_carrera_admitida BIGINT REFERENCES carrera(id_carrera),
    id_grupo BIGINT REFERENCES grupo_academico(id_grupo),
    estado_admision VARCHAR(20) DEFAULT 'PENDIENTE' NOT NULL,
    fecha_postulacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    UNIQUE (id_postulante, id_gestion),
    CONSTRAINT postulacion_estado_admision_check CHECK (estado_admision IN ('PENDIENTE', 'ADMITIDO', 'NO_ADMITIDO')),
    CONSTRAINT postulacion_carreras_distintas_check CHECK (id_carrera_opcion2 IS NULL OR id_carrera_opcion1 <> id_carrera_opcion2)
);

CREATE TABLE pago_inscripcion (
    id_pago BIGSERIAL PRIMARY KEY,
    id_postulacion BIGINT NOT NULL REFERENCES postulacion(id_postulacion),
    monto NUMERIC(10, 2) NOT NULL,
    moneda VARCHAR(10) DEFAULT 'BOB' NOT NULL,
    pasarela VARCHAR(50) NOT NULL,
    numero_transaccion VARCHAR(150) UNIQUE NOT NULL,
    codigo_autorizacion VARCHAR(100),
    codigo_error VARCHAR(100),
    estado_pago VARCHAR(30) DEFAULT 'PENDIENTE' NOT NULL,
    fecha_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    fecha_confirmacion TIMESTAMP,
    CONSTRAINT pago_inscripcion_monto_check CHECK (monto > 0),
    CONSTRAINT pago_inscripcion_pasarela_check CHECK (pasarela IN ('PAYPAL', 'STRIPE')),
    CONSTRAINT pago_inscripcion_estado_pago_check CHECK (estado_pago IN ('PENDIENTE', 'PROCESANDO', 'APROBADO', 'RECHAZADO', 'CANCELADO')),
    CONSTRAINT pago_inscripcion_fecha_confirmacion_check CHECK (fecha_confirmacion IS NULL OR fecha_confirmacion >= fecha_inicio)
);

CREATE TABLE nota (
    id_nota BIGSERIAL PRIMARY KEY,
    id_postulacion BIGINT NOT NULL REFERENCES postulacion(id_postulacion),
    id_materia BIGINT NOT NULL REFERENCES materia_cup(id_materia),
    nro_examen INT NOT NULL,
    nota NUMERIC(5, 2) NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    registrado_por BIGINT REFERENCES usuario(id_usuario),
    UNIQUE (id_postulacion, id_materia, nro_examen),
    CONSTRAINT nota_nro_examen_check CHECK (nro_examen BETWEEN 1 AND 3),
    CONSTRAINT nota_nota_check CHECK (nota BETWEEN 0 AND 100)
);

-- =========================
-- LOG DE AUDITORIA
-- =========================

CREATE TABLE log_auditoria (
    id_log BIGSERIAL PRIMARY KEY,
    tabla_afectada VARCHAR(100) NOT NULL,
    operacion VARCHAR(20) NOT NULL,
    id_registro VARCHAR(50),
    datos_anteriores JSONB,
    datos_nuevos JSONB,
    id_usuario BIGINT REFERENCES usuario(id_usuario),
    ip_origen INET,
    user_agent TEXT,
    fecha_operacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT log_auditoria_operacion_check CHECK (operacion IN ('INSERT', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT'))
);

-- =========================
-- TABLAS INTERNAS DE LARAVEL
-- =========================

CREATE TABLE cache (
    key VARCHAR(255) PRIMARY KEY,
    value TEXT NOT NULL,
    expiration BIGINT NOT NULL
);

CREATE INDEX cache_expiration_index ON cache (expiration);

CREATE TABLE cache_locks (
    key VARCHAR(255) PRIMARY KEY,
    owner VARCHAR(255) NOT NULL,
    expiration BIGINT NOT NULL
);

CREATE INDEX cache_locks_expiration_index ON cache_locks (expiration);

CREATE TABLE jobs (
    id BIGSERIAL PRIMARY KEY,
    queue VARCHAR(255) NOT NULL,
    payload TEXT NOT NULL,
    attempts SMALLINT NOT NULL,
    reserved_at INT NULL,
    available_at INT NOT NULL,
    created_at INT NOT NULL
);

CREATE INDEX jobs_queue_index ON jobs (queue);

CREATE TABLE job_batches (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    total_jobs INT NOT NULL,
    pending_jobs INT NOT NULL,
    failed_jobs INT NOT NULL,
    failed_job_ids TEXT NOT NULL,
    options TEXT,
    cancelled_at INT,
    created_at INT NOT NULL,
    finished_at INT
);

CREATE TABLE failed_jobs (
    id BIGSERIAL PRIMARY KEY,
    uuid VARCHAR(255) UNIQUE NOT NULL,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload TEXT NOT NULL,
    exception TEXT NOT NULL,
    failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL
);

CREATE INDEX failed_jobs_connection_queue_failed_at_index ON failed_jobs (connection, queue, failed_at);

-- =========================
-- VISTAS PARA REPORTES
-- =========================

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
    m.nombre;

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
    u.apellido;

-- =========================
-- FUNCION PARA CALCULAR GRUPOS
-- =========================

CREATE OR REPLACE FUNCTION calcular_grupos(total_inscritos INT)
RETURNS INT AS
$$
BEGIN
    RETURN CEIL(total_inscritos / 70.0);
END;
$$ LANGUAGE plpgsql;
