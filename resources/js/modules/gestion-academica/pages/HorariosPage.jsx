import { Head, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useMemo, useState } from 'react';
import HorarioForm from '@/modules/gestion-academica/components/HorarioForm';
import HorariosTable from '@/modules/gestion-academica/components/HorariosTable';
import { Button } from '@/shared/components/ui/button';
import { Card, CardDescription, CardHeader, CardTitle } from '@/shared/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/shared/components/ui/dialog';

export default function HorariosPage({ horarios }) {
    const { auth } = usePage().props;
    const canCreate = auth.permissions.includes('horarios:create');
    const canUpdate = auth.permissions.includes('horarios:update');
    const canToggle = auth.permissions.includes('horarios:delete');
    const [open, setOpen] = useState(false);
    const [selectedHorario, setSelectedHorario] = useState(null);

    const summary = useMemo(() => ({
        active: horarios.filter((horario) => horario.activo).length,
        inactive: horarios.filter((horario) => !horario.activo).length,
        days: new Set(horarios.map((horario) => horario.dia)).size,
    }), [horarios]);

    const openCreate = () => {
        setSelectedHorario(null);
        setOpen(true);
    };

    const openEdit = (horario) => {
        setSelectedHorario(horario);
        setOpen(true);
    };

    return (
        <>
            <Head title="Horarios" />

            <div className="space-y-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-[#001f3f]">Horarios</h1>
                        <p className="text-sm text-muted-foreground">
                            Administra los bloques horarios disponibles para las clases del CUP.
                        </p>
                    </div>
                    {canCreate && (
                        <Button type="button" onClick={openCreate} className="bg-[#e30613] text-white hover:bg-[#bb0710]">
                            <Plus className="size-4" />
                            Crear horario
                        </Button>
                    )}
                </div>

                <div className="grid gap-4 md:grid-cols-4">
                    <SummaryCard value={horarios.length} label="Total de horarios" />
                    <SummaryCard value={summary.active} label="Horarios activos" />
                    <SummaryCard value={summary.inactive} label="Horarios inactivos" />
                    <SummaryCard value={summary.days} label="Dias configurados" />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Listado de horarios</CardTitle>
                        <CardDescription>Crea, edita y activa o desactiva horarios del CUP.</CardDescription>
                    </CardHeader>
                    <div className="px-6 pb-6">
                        <HorariosTable horarios={horarios} canUpdate={canUpdate} canToggle={canToggle} onEdit={openEdit} />
                    </div>
                </Card>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{selectedHorario ? 'Editar horario' : 'Crear horario'}</DialogTitle>
                        <DialogDescription>Manten actualizado el catalogo de horarios del CUP.</DialogDescription>
                    </DialogHeader>
                    <HorarioForm
                        horario={selectedHorario}
                        canSubmit={selectedHorario ? canUpdate : canCreate}
                        onSuccess={() => setOpen(false)}
                    />
                </DialogContent>
            </Dialog>
        </>
    );
}

function SummaryCard({ value, label }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>{value}</CardTitle>
                <CardDescription>{label}</CardDescription>
            </CardHeader>
        </Card>
    );
}
