import { Head, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useMemo, useState } from 'react';
import RoleForm from '@/modules/acceso-seguridad/components/RoleForm';
import RolesTable from '@/modules/acceso-seguridad/components/RolesTable';
import { Button } from '@/shared/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle, } from '@/shared/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, } from '@/shared/components/ui/dialog';
import { Input } from '@/shared/components/ui/input';
export default function RolesPage({ roles, permisos, permisosPorModulo, }) {
    const { auth } = usePage().props;
    const canCreate = auth.permissions.includes('roles:create');
    const canUpdate = auth.permissions.includes('roles:update');
    const canToggle = auth.permissions.includes('roles:delete');
    const [search, setSearch] = useState('');
    const [open, setOpen] = useState(false);
    const [selectedRole, setSelectedRole] = useState(null);
    const filteredRoles = useMemo(() => {
        const value = search.trim().toLowerCase();
        if (!value) {
            return roles;
        }
        return roles.filter((role) => [role.nombre, role.descripcion ?? '']
            .join(' ')
            .toLowerCase()
            .includes(value));
    }, [roles, search]);
    const openCreate = () => {
        setSelectedRole(null);
        setOpen(true);
    };
    const openEdit = (role) => {
        setSelectedRole(role);
        setOpen(true);
    };
    return (<>
            <Head title="Roles y permisos"/>

            <div className="space-y-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-[#001f3f]">
                            Roles y permisos
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Administra roles y su matriz de permisos del sistema
                            CUP-FICCT.
                        </p>
                    </div>
                    {canCreate && (<Button type="button" onClick={openCreate} className="bg-[#e30613] text-white hover:bg-[#bb0710]">
                            <Plus className="size-4"/>
                            Crear rol
                        </Button>)}
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle>{roles.length}</CardTitle>
                            <CardDescription>Roles registrados</CardDescription>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>{permisos.length}</CardTitle>
                            <CardDescription>Permisos definidos</CardDescription>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>
                                {Object.keys(permisosPorModulo).length}
                            </CardTitle>
                            <CardDescription>Módulos con permisos</CardDescription>
                        </CardHeader>
                    </Card>
                </div>

                <Card>
                    <CardHeader className="gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <CardTitle>Listado de roles</CardTitle>
                            <CardDescription>
                                Busca, edita y activa o desactiva roles.
                            </CardDescription>
                        </div>
                        <Input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Buscar rol..." className="md:max-w-xs"/>
                    </CardHeader>
                    <CardContent>
                        <RolesTable roles={filteredRoles} onEdit={openEdit} canUpdate={canUpdate} canToggle={canToggle}/>
                    </CardContent>
                </Card>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>
                            {selectedRole ? 'Editar rol' : 'Crear rol'}
                        </DialogTitle>
                        <DialogDescription>
                            Define el nombre del rol y selecciona sus permisos.
                        </DialogDescription>
                    </DialogHeader>
                    <RoleForm role={selectedRole} permisosPorModulo={permisosPorModulo} canSubmit={selectedRole ? canUpdate : canCreate} onSuccess={() => setOpen(false)}/>
                </DialogContent>
            </Dialog>
        </>);
}
