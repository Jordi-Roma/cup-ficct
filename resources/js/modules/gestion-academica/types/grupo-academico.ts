export type GrupoAcademico = {
    id_grupo: number;
    id_gestion: number;
    nombre: string;
    capacidad_maxima: number;
    activo: boolean;
    postulantes_asignados: number;
    cupos_disponibles: number;
    postulantes?: GrupoPostulante[];
};

export type GrupoPostulante = {
    id_postulante: number;
    ci: string;
    nombre: string;
    apellido: string;
    nombre_completo: string;
    username: string;
    correo: string;
    telefono: string | null;
    ciudad: string | null;
    colegio_procedencia: string | null;
    documentacion_completa: boolean;
    estado_admision: string;
    carrera_opcion1: string | null;
    carrera_opcion2: string | null;
};

export type GruposResumen = {
    total_inscritos: number;
    grupos_necesarios: number;
    grupos_activos: number;
    grupos_faltantes: number;
    gestion_activa: {
        id_gestion: number;
        nombre: string;
    };
};
