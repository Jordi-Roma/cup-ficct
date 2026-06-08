import { Head, router, usePage } from '@inertiajs/react';
import { Plus, Shuffle, WandSparkles } from 'lucide-react';
import { useMemo, useState } from 'react';
import AsignacionAcademicaForm from '@/modules/gestion-academica/components/AsignacionAcademicaForm';
import AsignacionesAcademicasTable from '@/modules/gestion-academica/components/AsignacionesAcademicasTable';
import { Button } from '@/shared/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle, } from '@/shared/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, } from '@/shared/components/ui/dialog';
export default function AsignacionesAcademicasPage({ asignaciones, options, generationSummary, }) {
    const { auth } = usePage().props;
    const canCreate = auth.permissions.includes('asignaciones:create');
    const canUpdate = auth.permissions.includes('asignaciones:update');
    const canToggle = auth.permissions.includes('asignaciones:delete');
    const [open, setOpen] = useState(false);
    const [generateOpen, setGenerateOpen] = useState(false);
    const [generating, setGenerating] = useState(false);
    const [selectedAsignacion, setSelectedAsignacion] = useState(null);
    const summary = useMemo(() => {
        const active = asignaciones.filter((asignacion) => asignacion.activo).length;
        const groups = new Set(asignaciones
            .filter((asignacion) => asignacion.activo)
            .map((asignacion) => asignacion.grupo.id_grupo)).size;
        const docentes = new Set(asignaciones
            .filter((asignacion) => asignacion.activo)
            .map((asignacion) => asignacion.docente.id_docente)).size;
        return { active, groups, docentes };
    }, [asignaciones]);
    const openCreate = () => {
        setSelectedAsignacion(null);
        setOpen(true);
    };
    const openEdit = (asignacion) => {
        setSelectedAsignacion(asignacion);
        setOpen(true);
    };
    const assignPostulantes = () => {
        router.post('/academico/asignaciones/asignar-postulantes', undefined, {
            preserveScroll: true,
        });
    };
    const generateAssignments = () => {
        setGenerating(true);
        router.post('/academico/asignaciones/generar', undefined, {
            preserveScroll: true,
            onFinish: () => {
                setGenerating(false);
                setGenerateOpen(false);
            },
        });
    };
    return (<>
            <Head title="Asignacion academica"/>

            <div className="space-y-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground">
                            Asignacion academica
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Asigna docentes, materias, aulas y horarios a los
                            grupos del CUP.
                        </p>
                    </div>
                    <div className="flex flex-col gap-2 sm:flex-row">
                        {canCreate && (<Button type="button" className="bg-[#B91C1C] text-white hover:bg-[#991B1B]" onClick={() => setGenerateOpen(true)}>
                                <WandSparkles className="size-4"/>
                                Generar asignaciones
                            </Button>)}
                        {canUpdate && (
                            <Button type="button" className="bg-[#001f3f] text-white hover:bg-[#06345f]" onClick={assignPostulantes}>
                                <Shuffle className="size-4"/>
                                Asignar postulantes
                            </Button>
                        )}
                        {canCreate && (<Button type="button" onClick={openCreate} className="bg-[#0D2B85] text-white hover:bg-[#0a2270]">
                                <Plus className="size-4"/>
                                Crear asignacion
                            </Button>)}
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>{asignaciones.length}</CardTitle>
                            <CardDescription>
                                Total asignaciones
                            </CardDescription>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>{summary.active}</CardTitle>
                            <CardDescription>
                                Asignaciones activas
                            </CardDescription>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>{summary.groups}</CardTitle>
                            <CardDescription>
                                Grupos con materias
                            </CardDescription>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>{summary.docentes}</CardTitle>
                            <CardDescription>
                                Docentes asignados
                            </CardDescription>
                        </CardHeader>
                    </Card>
                </div>

                {generationSummary && (<Card>
                        <CardHeader>
                            <CardTitle>Resumen de generacion</CardTitle>
                            <CardDescription>
                                Creadas: {generationSummary.creadas}. Omitidas:{' '}
                                {generationSummary.omitidas}.
                            </CardDescription>
                        </CardHeader>
                        {generationSummary.detalles?.length > 0 && (<CardContent>
                                <div className="max-h-56 overflow-y-auto rounded-md border">
                                    <table className="w-full text-sm">
                                        <thead className="bg-muted text-left">
                                            <tr>
                                                <th className="px-3 py-2">Grupo</th>
                                                <th className="px-3 py-2">Materia</th>
                                                <th className="px-3 py-2">Motivo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {generationSummary.detalles.map((detalle, index) => (<tr key={`${detalle.grupo}-${detalle.materia}-${index}`} className="border-t">
                                                    <td className="px-3 py-2">{detalle.grupo}</td>
                                                    <td className="px-3 py-2">{detalle.materia}</td>
                                                    <td className="px-3 py-2 text-muted-foreground">
                                                        {detalle.motivo}
                                                    </td>
                                                </tr>))}
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>)}
                    </Card>)}

                <Card>
                    <CardHeader>
                        <CardTitle>Listado de asignaciones</CardTitle>
                        <CardDescription>
                            Controla conflictos de docente, aula, horario y
                            materia por grupo.
                        </CardDescription>
                    </CardHeader>
                    <div className="px-6 pb-6">
                        <AsignacionesAcademicasTable asignaciones={asignaciones} canUpdate={canUpdate} canToggle={canToggle} onEdit={openEdit}/>
                    </div>
                </Card>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>
                            {selectedAsignacion
            ? 'Editar asignacion'
            : 'Crear asignacion'}
                        </DialogTitle>
                        <DialogDescription>
                            Selecciona grupo, materia, docente, aula y horario.
                        </DialogDescription>
                    </DialogHeader>
                    <AsignacionAcademicaForm asignacion={selectedAsignacion} options={options} canSubmit={selectedAsignacion ? canUpdate : canCreate} onSuccess={() => setOpen(false)}/>
                </DialogContent>
            </Dialog>
            <Dialog open={generateOpen} onOpenChange={setGenerateOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Generar asignaciones automaticas</DialogTitle>
                        <DialogDescription>
                            Se crearan asignaciones automaticas para los grupos
                            activos que aun no tengan sus materias completas. No
                            se modificaran asignaciones existentes.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setGenerateOpen(false)} disabled={generating}>
                            Cancelar
                        </Button>
                        <Button type="button" onClick={generateAssignments} disabled={generating} className="bg-[#B91C1C] text-white hover:bg-[#991B1B]">
                            <WandSparkles className="size-4"/>
                            {generating ? 'Generando...' : 'Generar'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>);
}
