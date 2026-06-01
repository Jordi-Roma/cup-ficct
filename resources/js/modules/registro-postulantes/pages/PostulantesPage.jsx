import { Head, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import PostulanteDetail from '@/modules/registro-postulantes/components/PostulanteDetail';
import PostulanteForm from '@/modules/registro-postulantes/components/PostulanteForm';
import PostulantesTable from '@/modules/registro-postulantes/components/PostulantesTable';
import { Button } from '@/shared/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle, } from '@/shared/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, } from '@/shared/components/ui/dialog';
import { Input } from '@/shared/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue, } from '@/shared/components/ui/select';
export default function PostulantesPage({ postulantes, filters, carreras, selectedPostulante, }) {
    const { auth } = usePage().props;
    const canUpdate = auth.permissions.includes('postulantes:update');
    const [localFilters, setLocalFilters] = useState({
        search: filters.search ?? '',
        ciudad: filters.ciudad ?? '',
        colegio_procedencia: filters.colegio_procedencia ?? '',
        documentacion_completa: filters.documentacion_completa ?? '',
        estado_admision: filters.estado_admision ?? '',
        id_carrera: filters.id_carrera ?? '',
    });
    const [detailPostulante, setDetailPostulante] = useState(selectedPostulante ?? null);
    const [editPostulante, setEditPostulante] = useState(null);
    const summary = useMemo(() => ({
        total: postulantes.length,
        complete: postulantes.filter((postulante) => postulante.documentacion_completa).length,
        active: postulantes.filter((postulante) => postulante.activo).length,
    }), [postulantes]);
    const applyFilters = () => {
        router.get('/postulantes', localFilters, {
            preserveScroll: true,
            preserveState: true,
        });
    };
    const clearFilters = () => {
        const emptyFilters = {
            search: '',
            ciudad: '',
            colegio_procedencia: '',
            documentacion_completa: '',
            estado_admision: '',
            id_carrera: '',
        };
        setLocalFilters(emptyFilters);
        router.get('/postulantes', emptyFilters, {
            preserveScroll: true,
            preserveState: true,
        });
    };
    return (<>
            <Head title="Postulantes"/>

            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold text-[#001f3f]">
                        Postulantes
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Administra los registros de postulantes del CUP.
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>{summary.total}</CardTitle>
                            <CardDescription>Total postulantes</CardDescription>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>{summary.complete}</CardTitle>
                            <CardDescription>
                                Documentación completa
                            </CardDescription>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>{summary.total - summary.complete}</CardTitle>
                            <CardDescription>
                                Documentación pendiente
                            </CardDescription>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>{summary.active}</CardTitle>
                            <CardDescription>Usuarios activos</CardDescription>
                        </CardHeader>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Filtros</CardTitle>
                        <CardDescription>
                            Busca por CI, nombre, apellido o correo.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
                        <Input value={localFilters.search} onChange={(event) => setLocalFilters({
            ...localFilters,
            search: event.target.value,
        })} placeholder="Buscar..."/>
                        <Input value={localFilters.ciudad} onChange={(event) => setLocalFilters({
            ...localFilters,
            ciudad: event.target.value,
        })} placeholder="Ciudad"/>
                        <Input value={localFilters.colegio_procedencia} onChange={(event) => setLocalFilters({
            ...localFilters,
            colegio_procedencia: event.target.value,
        })} placeholder="Colegio"/>
                        <Select value={localFilters.documentacion_completa || 'all'} onValueChange={(value) => setLocalFilters({
            ...localFilters,
            documentacion_completa: value === 'all' ? '' : value,
        })}>
                            <SelectTrigger className="w-full">
                                <SelectValue placeholder="Documentación"/>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Documentación</SelectItem>
                                <SelectItem value="true">Completa</SelectItem>
                                <SelectItem value="false">Pendiente</SelectItem>
                            </SelectContent>
                        </Select>
                        <Select value={localFilters.estado_admision || 'all'} onValueChange={(value) => setLocalFilters({
            ...localFilters,
            estado_admision: value === 'all' ? '' : value,
        })}>
                            <SelectTrigger className="w-full">
                                <SelectValue placeholder="Estado"/>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Estado</SelectItem>
                                <SelectItem value="PENDIENTE">
                                    Pendiente
                                </SelectItem>
                                <SelectItem value="ADMITIDO">Admitido</SelectItem>
                                <SelectItem value="NO_ADMITIDO">
                                    No admitido
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <Select value={localFilters.id_carrera || 'all'} onValueChange={(value) => setLocalFilters({
            ...localFilters,
            id_carrera: value === 'all' ? '' : value,
        })}>
                            <SelectTrigger className="w-full">
                                <SelectValue placeholder="Carrera"/>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Carrera</SelectItem>
                                {carreras.map((carrera) => (<SelectItem key={carrera.id_carrera} value={carrera.id_carrera.toString()}>
                                        {carrera.nombre}
                                    </SelectItem>))}
                            </SelectContent>
                        </Select>
                        <div className="flex gap-2 md:col-span-3 xl:col-span-6">
                            <Button type="button" onClick={applyFilters} className="bg-[#001f3f] text-white hover:bg-[#06345f]">
                                Filtrar
                            </Button>
                            <Button type="button" variant="outline" onClick={clearFilters}>
                                Limpiar
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Listado de postulantes</CardTitle>
                        <CardDescription>
                            Consulta, verifica documentación y actualiza datos.
                        </CardDescription>
                    </CardHeader>
                    <div className="px-6 pb-6">
                        <PostulantesTable postulantes={postulantes} canUpdate={canUpdate} onView={setDetailPostulante} onEdit={setEditPostulante}/>
                    </div>
                </Card>
            </div>

            <Dialog open={detailPostulante !== null} onOpenChange={(open) => !open && setDetailPostulante(null)}>
                <DialogContent className="sm:max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>Detalle de postulante</DialogTitle>
                        <DialogDescription>
                            Información personal y estado de postulación.
                        </DialogDescription>
                    </DialogHeader>
                    {detailPostulante && (<PostulanteDetail postulante={detailPostulante}/>)}
                </DialogContent>
            </Dialog>

            <Dialog open={editPostulante !== null} onOpenChange={(open) => !open && setEditPostulante(null)}>
                <DialogContent className="sm:max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>Editar postulante</DialogTitle>
                        <DialogDescription>
                            Actualiza datos permitidos del postulante.
                        </DialogDescription>
                    </DialogHeader>
                    {editPostulante && (<PostulanteForm postulante={editPostulante} carreras={carreras} canSubmit={canUpdate} onSuccess={() => setEditPostulante(null)}/>)}
                </DialogContent>
            </Dialog>
        </>);
}
