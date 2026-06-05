import { router } from '@inertiajs/react';
import { Edit2, Power, Shield } from 'lucide-react';
import { Badge } from '@/shared/components/ui/badge';
import { Button } from '@/shared/components/ui/button';
export default function RolesTable({ roles, onEdit, canUpdate, canToggle, }) {
    const toggle = (role) => {
        router.patch(`/admin/roles/${role.id_rol}/toggle`, undefined, {
            preserveScroll: true,
        });
    };
    return (<div className="overflow-hidden rounded-md border">
            <div className="hidden md:block">
                <table className="w-full text-sm">
                    <thead className="bg-slate-50 text-left">
                        <tr>
                            <th className="px-4 py-3">Rol</th>
                            <th className="px-4 py-3">Descripción</th>
                            <th className="px-4 py-3">Permisos</th>
                            <th className="px-4 py-3">Estado</th>
                            <th className="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {roles.map((role) => (<tr key={role.id_rol}>
                                <td className="px-4 py-3 font-medium">
                                    <span className="inline-flex items-center gap-2">
                                        <Shield className="size-4 text-[#e30613]"/>
                                        {role.nombre}
                                    </span>
                                </td>
                                <td className="px-4 py-3 text-muted-foreground">
                                    {role.descripcion ?? 'Sin descripción'}
                                </td>
                                <td className="px-4 py-3">
                                    {role.permisos.length}
                                </td>
                                <td className="px-4 py-3">
                                    <Badge variant={role.activo
                ? 'default'
                : 'secondary'}>
                                        {role.activo ? 'Activo' : 'Inactivo'}
                                    </Badge>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex justify-end gap-2">
                                        {canUpdate && (<Button type="button" variant="outline" size="sm" onClick={() => onEdit(role)}>
                                                <Edit2 className="size-4"/>
                                            </Button>)}
                                        {canToggle && (<Button type="button" variant="outline" size="sm" onClick={() => toggle(role)}>
                                                <Power className="size-4"/>
                                            </Button>)}
                                    </div>
                                </td>
                            </tr>))}
                    </tbody>
                </table>
            </div>

            <div className="grid gap-3 p-3 md:hidden">
                {roles.map((role) => (<div key={role.id_rol} className="rounded-md border p-4">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <h3 className="font-semibold">{role.nombre}</h3>
                                <p className="text-sm text-muted-foreground">
                                    {role.descripcion ?? 'Sin descripción'}
                                </p>
                            </div>
                            <Badge variant={role.activo ? 'default' : 'secondary'}>
                                {role.activo ? 'Activo' : 'Inactivo'}
                            </Badge>
                        </div>
                        <div className="mt-4 flex items-center justify-between">
                            <span className="text-sm">
                                {role.permisos.length} permisos
                            </span>
                            <div className="flex gap-2">
                                {canUpdate && (<Button type="button" variant="outline" size="sm" onClick={() => onEdit(role)}>
                                        Editar
                                    </Button>)}
                                {canToggle && (<Button type="button" variant="outline" size="sm" onClick={() => toggle(role)}>
                                        Estado
                                    </Button>)}
                            </div>
                        </div>
                    </div>))}
            </div>
        </div>);
}
