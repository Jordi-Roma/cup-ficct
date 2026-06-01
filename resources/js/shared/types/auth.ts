export type User = {
    id: number;
    name: string;
    email: string;
    username?: string;
    nombre?: string;
    apellido?: string;
    correo?: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
    roles: string[];
    permissions: string[];
};
