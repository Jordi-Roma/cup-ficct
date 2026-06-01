import { Head, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import UserRolesForm from '@/modules/autenticacion/components/UserRolesForm';
import UsersTable from '@/modules/autenticacion/components/UsersTable';
import { Button } from '@/shared/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle, } from '@/shared/components/ui/card';
import { Checkbox } from '@/shared/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, } from '@/shared/components/ui/dialog';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue, } from '@/shared/components/ui/select';
export default function UsuariosPage({ usuarios, roles }) {
    const { auth } = usePage().props;
    const canUpdate = auth.permissions.includes('usuarios:update');
    const [search, setSearch] = useState('');
    const [accessUser, setAccessUser] = useState(null);
    const [rolesUser, setRolesUser] = useState(null);
    const { data, setData, put, processing } = useForm({
        estado_acceso: 'HABILITADO',
        activo: true,
    });
    const filteredUsers = useMemo(() => {
        const value = search.trim().toLowerCase();
        if (!value) {
            return usuarios;
        }
        return usuarios.filter((usuario) => [
            usuario.name,
            usuario.username,
            usuario.correo,
            usuario.ci,
            usuario.estado_acceso,
        ]
            .join(' ')
            .toLowerCase()
            .includes(value));
    }, [usuarios, search]);
    const openAccess = (usuario) => {
        if (!canUpdate) {
            return;
        }
        setAccessUser(usuario);
        setData({
            estado_acceso: usuario.estado_acceso,
            activo: usuario.activo,
        });
    };
    const submitAccess = (event) => {
        event.preventDefault();
        if (!accessUser) {
            return;
        }
        put(`/admin/usuarios/${accessUser.id_usuario}`, {
            preserveScroll: true,
            onSuccess: () => setAccessUser(null),
        });
    };
    return (<>
            <Head title="Usuarios"/>

            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold text-[#001f3f]">
                        Usuarios
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Consulta usuarios, cambia acceso y asigna roles.
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle>{usuarios.length}</CardTitle>
                            <CardDescription>
                                Usuarios registrados
                            </CardDescription>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>
                                {usuarios.filter((usuario) => usuario.activo).length}
                            </CardTitle>
                            <CardDescription>Usuarios activos</CardDescription>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>{roles.length}</CardTitle>
                            <CardDescription>Roles disponibles</CardDescription>
                        </CardHeader>
                    </Card>
                </div>

                <Card>
                    <CardHeader className="gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <CardTitle>Listado de usuarios</CardTitle>
                            <CardDescription>
                                Administra acceso y roles sin crear usuarios
                                nuevos.
                            </CardDescription>
                        </div>
                        <Input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Buscar usuario..." className="md:max-w-xs"/>
                    </CardHeader>
                    <CardContent>
                        <UsersTable usuarios={filteredUsers} onEditAccess={openAccess} onEditRoles={setRolesUser} canUpdate={canUpdate}/>
                    </CardContent>
                </Card>
            </div>

            <Dialog open={accessUser !== null} onOpenChange={(open) => !open && setAccessUser(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Actualizar acceso</DialogTitle>
                        <DialogDescription>
                            Cambia el estado de acceso de {accessUser?.name}.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submitAccess} className="space-y-5">
                        <div className="grid gap-2">
                            <Label>Estado de acceso</Label>
                            <Select value={data.estado_acceso} onValueChange={(value) => setData('estado_acceso', value)}>
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="HABILITADO">
                                        Habilitado
                                    </SelectItem>
                                    <SelectItem value="BLOQUEADO">
                                        Bloqueado
                                    </SelectItem>
                                    <SelectItem value="SUSPENDIDO">
                                        Suspendido
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <Label className="flex items-center gap-3 rounded-md border p-3">
                            <Checkbox checked={data.activo} onCheckedChange={(checked) => setData('activo', checked === true)}/>
                            Usuario activo
                        </Label>

                        <div className="flex justify-end gap-2">
                            <Button type="button" variant="outline" onClick={() => setAccessUser(null)}>
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={processing || !canUpdate} className="bg-[#e30613] text-white hover:bg-[#bb0710]">
                                Guardar acceso
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={rolesUser !== null} onOpenChange={(open) => !open && setRolesUser(null)}>
                <DialogContent className="sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Asignar roles</DialogTitle>
                        <DialogDescription>
                            Selecciona los roles activos del usuario.
                        </DialogDescription>
                    </DialogHeader>
                    {rolesUser && (<UserRolesForm usuario={rolesUser} roles={roles} canSubmit={canUpdate} onSuccess={() => setRolesUser(null)}/>)}
                </DialogContent>
            </Dialog>
        </>);
}
