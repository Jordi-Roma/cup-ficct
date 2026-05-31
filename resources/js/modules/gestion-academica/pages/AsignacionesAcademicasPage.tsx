import { Head, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useMemo, useState } from 'react';
import AsignacionAcademicaForm from '@/modules/gestion-academica/components/AsignacionAcademicaForm';
import AsignacionesAcademicasTable from '@/modules/gestion-academica/components/AsignacionesAcademicasTable';
import { Button } from '@/shared/components/ui/button';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/shared/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/shared/components/ui/dialog';
import type { Auth } from '@/shared/types';
import type {
    AsignacionAcademica,
    AsignacionOptions,
} from '../types/asignacion-academica';

type Props = {
    asignaciones: AsignacionAcademica[];
    options: AsignacionOptions;
};

export default function AsignacionesAcademicasPage({
    asignaciones,
    options,
}: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const canCreate = auth.permissions.includes('asignaciones:create');
    const canUpdate = auth.permissions.includes('asignaciones:update');
    const canToggle = auth.permissions.includes('asignaciones:delete');
    const [open, setOpen] = useState(false);
    const [selectedAsignacion, setSelectedAsignacion] =
        useState<AsignacionAcademica | null>(null);

    const summary = useMemo(() => {
        const active = asignaciones.filter(
            (asignacion) => asignacion.activo,
        ).length;
        const groups = new Set(
            asignaciones
                .filter((asignacion) => asignacion.activo)
                .map((asignacion) => asignacion.grupo.id_grupo),
        ).size;
        const docentes = new Set(
            asignaciones
                .filter((asignacion) => asignacion.activo)
                .map((asignacion) => asignacion.docente.id_docente),
        ).size;

        return { active, groups, docentes };
    }, [asignaciones]);

    const openCreate = () => {
        setSelectedAsignacion(null);
        setOpen(true);
    };

    const openEdit = (asignacion: AsignacionAcademica) => {
        setSelectedAsignacion(asignacion);
        setOpen(true);
    };

    return (
        <>
            <Head title="Asignacion academica" />

            <div className="space-y-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-[#001f3f]">
                            Asignacion academica
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Asigna docentes, materias, aulas y horarios a los
                            grupos del CUP.
                        </p>
                    </div>
                    {canCreate && (
                        <Button
                            type="button"
                            onClick={openCreate}
                            className="bg-[#e30613] text-white hover:bg-[#bb0710]"
                        >
                            <Plus className="size-4" />
                            Crear asignacion
                        </Button>
                    )}
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

                <Card>
                    <CardHeader>
                        <CardTitle>Listado de asignaciones</CardTitle>
                        <CardDescription>
                            Controla conflictos de docente, aula, horario y
                            materia por grupo.
                        </CardDescription>
                    </CardHeader>
                    <div className="px-6 pb-6">
                        <AsignacionesAcademicasTable
                            asignaciones={asignaciones}
                            canUpdate={canUpdate}
                            canToggle={canToggle}
                            onEdit={openEdit}
                        />
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
                    <AsignacionAcademicaForm
                        asignacion={selectedAsignacion}
                        options={options}
                        canSubmit={selectedAsignacion ? canUpdate : canCreate}
                        onSuccess={() => setOpen(false)}
                    />
                </DialogContent>
            </Dialog>
        </>
    );
}
