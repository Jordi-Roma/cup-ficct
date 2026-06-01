import { Head, router, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useMemo, useState } from 'react';
import DocenteForm from '@/modules/gestion-academica/components/DocenteForm';
import DocentesTable from '@/modules/gestion-academica/components/DocentesTable';
import { Button } from '@/shared/components/ui/button';
import { Card, CardDescription, CardHeader, CardTitle, } from '@/shared/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, } from '@/shared/components/ui/dialog';
import { Input } from '@/shared/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue, } from '@/shared/components/ui/select';
const booleanOptions = [
    { value: 'all', label: 'Todos' },
    { value: 'true', label: 'Si' },
    { value: 'false', label: 'No' },
];
export default function DocentesPage({ docentes, filters }) {
    const { auth } = usePage().props;
    const canCreate = auth.permissions.includes('docentes:create');
    const canUpdate = auth.permissions.includes('docentes:update');
    const canToggle = auth.permissions.includes('docentes:delete');
    const [open, setOpen] = useState(false);
    const [selectedDocente, setSelectedDocente] = useState(null);
    const [localFilters, setLocalFilters] = useState({
        search: filters.search ?? '',
        contratado: filters.contratado ?? '',
        activo: filters.activo ?? '',
        profesional_area: filters.profesional_area ?? '',
        maestria: filters.maestria ?? '',
        diplomado_educacion_superior: filters.diplomado_educacion_superior ?? '',
    });
    const summary = useMemo(() => {
        const active = docentes.filter((docente) => docente.activo).length;
        const hired = docentes.filter((docente) => docente.contratado).length;
        const qualified = docentes.filter((docente) => docente.profesional_area &&
            docente.maestria &&
            docente.diplomado_educacion_superior).length;
        return { active, hired, qualified };
    }, [docentes]);
    const openCreate = () => {
        setSelectedDocente(null);
        setOpen(true);
    };
    const openEdit = (docente) => {
        setSelectedDocente(docente);
        setOpen(true);
    };
    const applyFilters = () => {
        router.get('/academico/docentes', normalizeFilters(localFilters), {
            preserveScroll: true,
            preserveState: true,
        });
    };
    const clearFilters = () => {
        setLocalFilters({});
        router.get('/academico/docentes', {}, { preserveScroll: true });
    };
    return (<>
            <Head title="Docentes"/>

            <div className="space-y-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-[#001f3f]">
                            Docentes
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Administra docentes habilitados para dictar materias
                            del CUP.
                        </p>
                    </div>
                    {canCreate && (<Button type="button" onClick={openCreate} className="bg-[#e30613] text-white hover:bg-[#bb0710]">
                            <Plus className="size-4"/>
                            Crear docente
                        </Button>)}
                </div>

                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>{docentes.length}</CardTitle>
                            <CardDescription>Total docentes</CardDescription>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>{summary.hired}</CardTitle>
                            <CardDescription>Contratados</CardDescription>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>{summary.active}</CardTitle>
                            <CardDescription>Activos</CardDescription>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>{summary.qualified}</CardTitle>
                            <CardDescription>Cumplen requisitos</CardDescription>
                        </CardHeader>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Filtros</CardTitle>
                        <CardDescription>
                            Busca por CI, nombre, usuario o correo.
                        </CardDescription>
                    </CardHeader>
                    <div className="grid gap-3 px-6 pb-6 md:grid-cols-3 xl:grid-cols-6">
                        <Input placeholder="Buscar docente" value={localFilters.search ?? ''} onChange={(event) => setLocalFilters((current) => ({
            ...current,
            search: event.target.value,
        }))}/>
                        <BooleanFilter value={localFilters.contratado} placeholder="Contratado" onChange={(value) => setLocalFilters((current) => ({
            ...current,
            contratado: value,
        }))}/>
                        <BooleanFilter value={localFilters.activo} placeholder="Activo" onChange={(value) => setLocalFilters((current) => ({
            ...current,
            activo: value,
        }))}/>
                        <BooleanFilter value={localFilters.profesional_area} placeholder="Profesional" onChange={(value) => setLocalFilters((current) => ({
            ...current,
            profesional_area: value,
        }))}/>
                        <BooleanFilter value={localFilters.maestria} placeholder="Maestria" onChange={(value) => setLocalFilters((current) => ({
            ...current,
            maestria: value,
        }))}/>
                        <BooleanFilter value={localFilters.diplomado_educacion_superior} placeholder="Diplomado" onChange={(value) => setLocalFilters((current) => ({
            ...current,
            diplomado_educacion_superior: value,
        }))}/>
                        <div className="flex gap-2 md:col-span-3 xl:col-span-6">
                            <Button type="button" onClick={applyFilters}>
                                Filtrar
                            </Button>
                            <Button type="button" variant="outline" onClick={clearFilters}>
                                Limpiar
                            </Button>
                        </div>
                    </div>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Listado de docentes</CardTitle>
                        <CardDescription>
                            Gestiona datos personales, requisitos academicos y
                            contratacion.
                        </CardDescription>
                    </CardHeader>
                    <div className="px-6 pb-6">
                        <DocentesTable docentes={docentes} canUpdate={canUpdate} canToggle={canToggle} onEdit={openEdit}/>
                    </div>
                </Card>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>
                            {selectedDocente
            ? 'Editar docente'
            : 'Crear docente'}
                        </DialogTitle>
                        <DialogDescription>
                            Registra datos de acceso y requisitos academicos del
                            docente.
                        </DialogDescription>
                    </DialogHeader>
                    <DocenteForm docente={selectedDocente} canSubmit={selectedDocente ? canUpdate : canCreate} onSuccess={() => setOpen(false)}/>
                </DialogContent>
            </Dialog>
        </>);
}
function BooleanFilter({ value, placeholder, onChange, }) {
    return (<Select value={value || 'all'} onValueChange={onChange}>
            <SelectTrigger className="w-full">
                <SelectValue placeholder={placeholder}/>
            </SelectTrigger>
            <SelectContent>
                {booleanOptions.map((option) => (<SelectItem key={option.value} value={option.value}>
                        {placeholder}: {option.label}
                    </SelectItem>))}
            </SelectContent>
        </Select>);
}
function normalizeFilters(filters) {
    return Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== undefined && value !== '' && value !== 'all'));
}
