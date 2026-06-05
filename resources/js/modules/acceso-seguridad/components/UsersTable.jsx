import { Edit2, Shield } from 'lucide-react';
import { Badge } from '@/shared/components/ui/badge';
import { Button } from '@/shared/components/ui/button';
export default function UsersTable({ usuarios, onEditAccess, onEditRoles, canUpdate, }) {
    return (<div className="overflow-hidden rounded-md border">
            <div className="hidden md:block">
                <table className="w-full text-sm">
                    <thead className="bg-slate-50 text-left">
                        <tr>
                            <th className="px-4 py-3">Usuario</th>
                            <th className="px-4 py-3">CI</th>
                            <th className="px-4 py-3">Estado</th>
                            <th className="px-4 py-3">Roles</th>
                            <th className="px-4 py-3 text-right">Acciones</th>
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
                                        <Badge variant={usuario.activo
                ? 'default'
                : 'secondary'}>
                                            {usuario.activo
                ? 'Activo'
                : 'Inactivo'}
                                        </Badge>
                                        <Badge variant="outline">
                                            {usuario.estado_acceso}
                                        </Badge>
                                    </div>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex flex-wrap gap-1">
                                        {usuario.roles.length > 0 ? (usuario.roles.map((role) => (<Badge key={role.id_rol} variant="secondary">
                                                    {role.nombre}
                                                </Badge>))) : (<span className="text-xs text-muted-foreground">
                                                Sin roles
                                            </span>)}
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
                {usuarios.map((usuario) => (<div key={usuario.id_usuario} className="space-y-3 rounded-md border p-4">
                        <div>
                            <h3 className="font-semibold">{usuario.name}</h3>
                            <p className="text-sm text-muted-foreground">
                                {usuario.username} · {usuario.correo}
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Badge>{usuario.estado_acceso}</Badge>
                            {usuario.roles.map((role) => (<Badge key={role.id_rol} variant="secondary">
                                    {role.nombre}
                                </Badge>))}
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
                    </div>))}
            </div>
        </div>);
}
