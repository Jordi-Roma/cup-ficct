import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEvent, useMemo, useState } from 'react';
import InputError from '@/shared/components/input-error';
import { Badge } from '@/shared/components/ui/badge';
import { Button } from '@/shared/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/shared/components/ui/card';
import { Checkbox } from '@/shared/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/shared/components/ui/dialog';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';
import type { Auth } from '@/shared/types';
import type { Permiso, PermisosPorModulo } from '../types/access-control';

type Props = {
    permisos: Permiso[];
    permisosPorModulo: PermisosPorModulo;
};

export default function PermisosPage({ permisos, permisosPorModulo }: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const canCreate = auth.permissions.includes('permisos:create');
    const canUpdate = auth.permissions.includes('permisos:update');
    const [open, setOpen] = useState(false);
    const [selected, setSelected] = useState<Permiso | null>(null);
    const { data, setData, post, put, processing, errors, reset } = useForm({
        nombre: '',
        modulo: '',
        accion: '',
        descripcion: '',
        activo: true,
    });

    const modules = useMemo(
        () => Object.entries(permisosPorModulo),
        [permisosPorModulo],
    );

    const openCreate = () => {
        setSelected(null);
        reset();
        setOpen(true);
    };

    const openEdit = (permiso: Permiso) => {
        if (!canUpdate) {
            return;
        }

        setSelected(permiso);
        setData({
            nombre: permiso.nombre,
            modulo: permiso.modulo,
            accion: permiso.accion,
            descripcion: permiso.descripcion ?? '',
            activo: permiso.activo,
        });
        setOpen(true);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        };

        if (selected) {
            put(`/admin/permisos/${selected.id_permiso}`, options);
            return;
        }

        post('/admin/permisos', options);
    };

    return (
        <>
            <Head title="Permisos" />

            <div className="space-y-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-[#001f3f]">
                            Permisos
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Catálogo de acciones disponibles por módulo.
                        </p>
                    </div>
                    {canCreate && (
                        <Button
                            type="button"
                            onClick={openCreate}
                            className="bg-[#e30613] text-white hover:bg-[#bb0710]"
                        >
                            Crear permiso
                        </Button>
                    )}
                </div>

                <div className="grid gap-4">
                    {modules.map(([modulo, modulePermissions]) => (
                        <Card key={modulo}>
                            <CardHeader>
                                <CardTitle>{modulo}</CardTitle>
                                <CardDescription>
                                    {modulePermissions.length} permisos
                                    configurados
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="grid gap-2 md:grid-cols-2">
                                    {modulePermissions.map((permiso) => (
                                        <button
                                            key={permiso.id_permiso}
                                            type="button"
                                            onClick={() => openEdit(permiso)}
                                            disabled={!canUpdate}
                                            className="rounded-md border p-3 text-left hover:bg-slate-50 disabled:cursor-default disabled:hover:bg-transparent"
                                        >
                                            <div className="flex items-start justify-between gap-3">
                                                <div>
                                                    <div className="font-medium">
                                                        {permiso.nombre}
                                                    </div>
                                                    <div className="text-sm text-muted-foreground">
                                                        {permiso.descripcion ??
                                                            permiso.accion}
                                                    </div>
                                                </div>
                                                <Badge
                                                    variant={
                                                        permiso.activo
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {permiso.activo
                                                        ? 'Activo'
                                                        : 'Inactivo'}
                                                </Badge>
                                            </div>
                                        </button>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {selected ? 'Editar permiso' : 'Crear permiso'}
                        </DialogTitle>
                        <DialogDescription>
                            Define el permiso usando el formato módulo y acción.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="nombre">Nombre</Label>
                            <Input
                                id="nombre"
                                value={data.nombre}
                                onChange={(event) =>
                                    setData('nombre', event.target.value)
                                }
                                placeholder="roles:read"
                            />
                            <InputError message={errors.nombre} />
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="modulo">Módulo</Label>
                                <Input
                                    id="modulo"
                                    value={data.modulo}
                                    onChange={(event) =>
                                        setData('modulo', event.target.value)
                                    }
                                    placeholder="Autenticacion"
                                />
                                <InputError message={errors.modulo} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="accion">Acción</Label>
                                <Input
                                    id="accion"
                                    value={data.accion}
                                    onChange={(event) =>
                                        setData('accion', event.target.value)
                                    }
                                placeholder="LEER"
                                />
                                <InputError message={errors.accion} />
                            </div>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="descripcion">Descripción</Label>
                            <Input
                                id="descripcion"
                                value={data.descripcion}
                                onChange={(event) =>
                                    setData('descripcion', event.target.value)
                                }
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
                            Permiso activo
                        </Label>
                        <div className="flex justify-end gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setOpen(false)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                disabled={
                                    processing ||
                                    (selected ? !canUpdate : !canCreate)
                                }
                                className="bg-[#e30613] text-white hover:bg-[#bb0710]"
                            >
                                Guardar
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
