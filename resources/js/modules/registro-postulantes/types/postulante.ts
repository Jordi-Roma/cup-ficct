export type CarreraOption = {
    id_carrera: number;
    nombre: string;
};

export type Postulante = {
    id_postulante: number;
    id_usuario: number;
    ci: string;
    nombre: string;
    apellido: string;
    nombre_completo: string;
    username: string;
    correo: string;
    telefono: string | null;
    sexo: string;
    activo: boolean;
    fecha_nacimiento: string;
    direccion: string | null;
    colegio_procedencia: string | null;
    ciudad: string | null;
    documentacion_completa: boolean;
    postulacion: {
        id_postulacion: number;
        estado_admision: string;
        carrera_opcion1: CarreraOption | null;
        carrera_opcion2: CarreraOption | null;
        grupo: {
            id_grupo: number;
            nombre: string;
        } | null;
    } | null;
};

export type PostulanteFilters = {
    search?: string;
    ciudad?: string;
    colegio_procedencia?: string;
    documentacion_completa?: string;
    estado_admision?: string;
    id_carrera?: string;
};
