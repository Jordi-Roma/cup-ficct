export type Docente = {
    id_docente: number;
    id_usuario: number;
    ci: string;
    nombre: string;
    apellido: string;
    nombre_completo: string;
    username: string;
    correo: string;
    telefono: string | null;
    sexo: 'M' | 'F' | 'O';
    estado_acceso: 'HABILITADO' | 'BLOQUEADO' | 'SUSPENDIDO';
    usuario_activo: boolean;
    profesional_area: boolean;
    maestria: boolean;
    diplomado_educacion_superior: boolean;
    contratado: boolean;
    activo: boolean;
};

export type DocenteFilters = {
    search?: string;
    contratado?: string;
    activo?: string;
    profesional_area?: string;
    maestria?: string;
    diplomado_educacion_superior?: string;
};
