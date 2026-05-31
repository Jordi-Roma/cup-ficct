export type AsignacionAcademica = {
    id_asignacion: number;
    activo: boolean;
    grupo: {
        id_grupo: number;
        nombre: string;
        capacidad_maxima: number;
    };
    materia: {
        id_materia: number;
        nombre: string;
    };
    docente: {
        id_docente: number;
        nombre_completo: string;
        correo: string;
    };
    aula: {
        id_aula: number;
        nombre: string;
        capacidad: number;
    };
    horario: {
        id_horario: number;
        dia: string;
        hora_inicio: string;
        hora_fin: string;
    };
};

export type AsignacionOptions = {
    grupos: Array<{
        id_grupo: number;
        nombre: string;
        capacidad_maxima: number;
    }>;
    materias: Array<{
        id_materia: number;
        nombre: string;
    }>;
    docentes: Array<{
        id_docente: number;
        nombre_completo: string;
        correo: string;
    }>;
    aulas: Array<{
        id_aula: number;
        nombre: string;
        capacidad: number;
    }>;
    horarios: Array<{
        id_horario: number;
        dia: string;
        hora_inicio: string;
        hora_fin: string;
    }>;
};
