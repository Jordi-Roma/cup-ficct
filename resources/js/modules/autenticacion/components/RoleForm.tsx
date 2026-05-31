import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/shared/components/input-error';
import { Button } from '@/shared/components/ui/button';
import { Checkbox } from '@/shared/components/ui/checkbox';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';
import type { PermisosPorModulo, Rol } from '../types/access-control';
import PermissionsMatrix from './PermissionsMatrix';

type Props = {
    role?: Rol | null;
    permisosPorModulo: PermisosPorModulo;
    canSubmit: boolean;
    onSuccess: () => void;
};

export default function RoleForm({
    role,
    permisosPorModulo,
    canSubmit,
    onSuccess,
}: Props) {
    const { data, setData, post, put, processing, errors } = useForm({
        nombre: role?.nombre ?? '',
        descripcion: role?.descripcion ?? '',
        activo: role?.activo ?? true,
        permisos: role?.permisos.map((permiso) => permiso.id_permiso) ?? [],
    });

    const togglePermission = (id: number) => {
        setData(
            'permisos',
            data.permisos.includes(id)
                ? data.permisos.filter((permisoId) => permisoId !== id)
                : [...data.permisos, id],
        );
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const options = { preserveScroll: true, onSuccess };

        if (role) {
            put(`/admin/roles/${role.id_rol}`, options);

            return;
        }

        post('/admin/roles', options);
    };

    return (
        <form onSubmit={submit} className="space-y-5">
            <div className="grid gap-2">
                <Label htmlFor="nombre">Nombre del rol</Label>
                <Input
                    id="nombre"
                    value={data.nombre}
                    onChange={(event) => setData('nombre', event.target.value)}
                    placeholder="ADMINISTRATIVO"
                />
                <InputError message={errors.nombre} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="descripcion">Descripción</Label>
                <Input
                    id="descripcion"
                    value={data.descripcion}
                    onChange={(event) =>
                        setData('descripcion', event.target.value)
                    }
                    placeholder="Gestión administrativa del proceso CUP"
                />
                <InputError message={errors.descripcion} />
            </div>

            <Label className="flex items-center gap-3 rounded-md border p-3">
                <Checkbox
                    checked={data.activo}
                    onCheckedChange={(checked) =>
                        setData('activo', checked === true)
                    }
                />
                Rol activo
            </Label>

            <div className="space-y-2">
                <Label>Matriz de permisos</Label>
                <PermissionsMatrix
                    permisosPorModulo={permisosPorModulo}
                    selectedIds={data.permisos}
                    onToggle={togglePermission}
                />
                <InputError message={errors.permisos} />
            </div>

            <div className="flex justify-end gap-2">
                <Button
                    type="button"
                    variant="outline"
                    onClick={onSuccess}
                    disabled={processing}
                >
                    Cancelar
                </Button>
                <Button
                    type="submit"
                disabled={processing || !canSubmit}
                className="bg-[#e30613] text-white hover:bg-[#bb0710]"
            >
                    {role ? 'Guardar cambios' : 'Crear rol'}
                </Button>
            </div>
        </form>
    );
}
