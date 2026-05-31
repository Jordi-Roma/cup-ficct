export type Permiso = {
    id_permiso: number;
    nombre: string;
    modulo: string;
    accion: string;
    descripcion: string | null;
    activo: boolean;
};

export type Rol = {
    id_rol: number;
    nombre: string;
    descripcion: string | null;
    activo: boolean;
    fecha_creacion?: string | null;
    permisos: Permiso[];
};

export type Usuario = {
    id_usuario: number;
    ci: string;
    nombre: string;
    apellido: string;
    name: string;
    username: string;
    correo: string;
    telefono: string | null;
    sexo: string;
    estado_acceso: 'HABILITADO' | 'BLOQUEADO' | 'SUSPENDIDO';
    activo: boolean;
    roles: Pick<Rol, 'id_rol' | 'nombre' | 'descripcion' | 'activo'>[];
};

export type PermisosPorModulo = Record<string, Permiso[]>;
