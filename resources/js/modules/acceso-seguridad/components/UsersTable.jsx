import { Edit2, Shield } from 'lucide-react';
import { Badge } from '@/shared/components/ui/badge';
import { Button } from '@/shared/components/ui/button';
export default function UsersTable({ usuarios, onEditAccess, onEditRoles, canUpdate, }) {
    return (<div className="overflow-hidden rounded-xl border">
            <div className="hidden md:block">
                <table className="w-full text-sm">
                    <thead className="bg-muted/40 text-left border-b">
                        <tr>
                            <th className="px-4 py-3 font-medium text-muted-foreground">Usuario</th>
                            <th className="px-4 py-3 font-medium text-muted-foreground">CI</th>
                            <th className="px-4 py-3 font-medium text-muted-foreground">Estado</th>
                            <th className="px-4 py-3 font-medium text-muted-foreground">Roles</th>
                            <th className="px-4 py-3 font-medium text-muted-foreground text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {usuarios.map((usuario) => (<tr key={usuario.id_usuario}>
                                <td className="px-4 py-3">
                                    <div className="font-medium">
                                        {usuario.name}
                                    </div>
                                    <div className="text-xs text-muted-foreground">
                                        {usuario.username} · {usuario.correo}
                                    </div>
                                </td>
                                <td className="px-4 py-3">{usuario.ci}</td>
                                <td className="px-4 py-3">
                                    <div className="flex flex-wrap gap-2">
                                        <Badge variant={usuario.activo ? 'active' : 'inactive'}>
                                            {usuario.activo ? 'Activo' : 'Inactivo'}
                                        </Badge>
                                        <Badge variant="pending">
                                            {usuario.estado_acceso}
                                        </Badge>
                                    </div>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex flex-wrap gap-1">
                                        {usuario.roles.length > 0
                                            ? usuario.roles.map((role) => (
                                                <Badge key={role.id_rol} variant="outline" className="text-[#3D52B0] border-[#D4D8F8] bg-[#EEF0FF] dark:text-[#9BA8E0] dark:border-[#2E2E58] dark:bg-[#1E1E48]">
                                                    {role.nombre}
                                                </Badge>
                                            ))
                                            : <span className="text-xs text-muted-foreground">Sin roles</span>
                                        }
                                    </div>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex justify-end gap-2">
                                        {canUpdate && (<>
                                                <Button type="button" variant="outline" size="sm" onClick={() => onEditAccess(usuario)}>
                                                    <Edit2 className="size-4"/>
                                                </Button>
                                                <Button type="button" variant="outline" size="sm" onClick={() => onEditRoles(usuario)}>
                                                    <Shield className="size-4"/>
                                                </Button>
                                            </>)}
                                    </div>
                                </td>
                            </tr>))}
                    </tbody>
                </table>
            </div>

            <div className="grid gap-3 p-3 md:hidden">
                {usuarios.map((usuario) => (
                    <div key={usuario.id_usuario} className="space-y-3 rounded-xl border p-4">
                        <div>
                            <h3 className="font-semibold">{usuario.name}</h3>
                            <p className="text-sm text-muted-foreground">
                                {usuario.username} · {usuario.correo}
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Badge variant={usuario.activo ? 'active' : 'inactive'}>
                                {usuario.activo ? 'Activo' : 'Inactivo'}
                            </Badge>
                            <Badge variant="pending">{usuario.estado_acceso}</Badge>
                            {usuario.roles.map((role) => (
                                <Badge key={role.id_rol} variant="outline" className="text-[#3D52B0] border-[#D4D8F8] bg-[#EEF0FF] dark:text-[#9BA8E0] dark:border-[#2E2E58] dark:bg-[#1E1E48]">
                                    {role.nombre}
                                </Badge>
                            ))}
                        </div>
                        <div className="flex gap-2">
                            {canUpdate && (<>
                                <Button type="button" variant="outline" size="sm" onClick={() => onEditAccess(usuario)}>
                                    Acceso
                                </Button>
                                <Button type="button" variant="outline" size="sm" onClick={() => onEditRoles(usuario)}>
                                    Roles
                                </Button>
                            </>)}
                        </div>
                    </div>
                ))}
            </div>
        </div>);
}
