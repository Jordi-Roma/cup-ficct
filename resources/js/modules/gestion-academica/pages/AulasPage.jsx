import { Head, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useMemo, useState } from 'react';
import AulaForm from '@/modules/gestion-academica/components/AulaForm';
import AulasTable from '@/modules/gestion-academica/components/AulasTable';
import { Button } from '@/shared/components/ui/button';
import { Card, CardDescription, CardHeader, CardTitle } from '@/shared/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/shared/components/ui/dialog';

export default function AulasPage({ aulas }) {
    const { auth } = usePage().props;
    const canCreate = auth.permissions.includes('aulas:create');
    const canUpdate = auth.permissions.includes('aulas:update');
    const canToggle = auth.permissions.includes('aulas:delete');
    const [open, setOpen] = useState(false);
    const [selectedAula, setSelectedAula] = useState(null);

    const summary = useMemo(() => ({
        active: aulas.filter((aula) => aula.activo).length,
        inactive: aulas.filter((aula) => !aula.activo).length,
        capacity: aulas.reduce((total, aula) => total + Number(aula.capacidad ?? 0), 0),
    }), [aulas]);

    const openCreate = () => {
        setSelectedAula(null);
        setOpen(true);
    };

    const openEdit = (aula) => {
        setSelectedAula(aula);
        setOpen(true);
    };

    return (
        <>
            <Head title="Aulas" />

            <div className="space-y-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground">Aulas</h1>
                        <p className="text-sm text-muted-foreground">
                            Administra las aulas disponibles para las clases del CUP.
                        </p>
                    </div>
                    {canCreate && (
                        <Button type="button" onClick={openCreate} className="bg-[#0D2B85] text-white hover:bg-[#0a2270]">
                            <Plus className="size-4" />
                            Crear aula
                        </Button>
                    )}
                </div>

                <div className="grid gap-4 md:grid-cols-4">
                    <SummaryCard value={aulas.length} label="Total de aulas" />
                    <SummaryCard value={summary.active} label="Aulas activas" />
                    <SummaryCard value={summary.inactive} label="Aulas inactivas" />
                    <SummaryCard value={summary.capacity} label="Capacidad total" />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Listado de aulas</CardTitle>
                        <CardDescription>Crea, edita y activa o desactiva aulas del CUP.</CardDescription>
                    </CardHeader>
                    <div className="px-6 pb-6">
                        <AulasTable aulas={aulas} canUpdate={canUpdate} canToggle={canToggle} onEdit={openEdit} />
                    </div>
                </Card>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{selectedAula ? 'Editar aula' : 'Crear aula'}</DialogTitle>
                        <DialogDescription>Manten actualizado el catalogo de aulas del CUP.</DialogDescription>
                    </DialogHeader>
                    <AulaForm aula={selectedAula} canSubmit={selectedAula ? canUpdate : canCreate} onSuccess={() => setOpen(false)} />
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
