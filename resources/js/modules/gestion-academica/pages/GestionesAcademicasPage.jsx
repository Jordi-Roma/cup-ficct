import { Head, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useMemo, useState } from 'react';
import GestionAcademicaForm from '@/modules/gestion-academica/components/GestionAcademicaForm';
import GestionesAcademicasTable from '@/modules/gestion-academica/components/GestionesAcademicasTable';
import { Button } from '@/shared/components/ui/button';
import { Card, CardDescription, CardHeader, CardTitle } from '@/shared/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/shared/components/ui/dialog';

export default function GestionesAcademicasPage({ gestiones }) {
    const { auth } = usePage().props;
    const canCreate = auth.permissions.includes('gestiones:create');
    const canUpdate = auth.permissions.includes('gestiones:update');
    const canToggle = auth.permissions.includes('gestiones:delete');
    const [open, setOpen] = useState(false);
    const [selectedGestion, setSelectedGestion] = useState(null);

    const summary = useMemo(() => {
        const active = gestiones.find((gestion) => gestion.activo);

        return {
            active,
            inactive: gestiones.filter((gestion) => !gestion.activo).length,
        };
    }, [gestiones]);

    const openCreate = () => {
        setSelectedGestion(null);
        setOpen(true);
    };

    const openEdit = (gestion) => {
        setSelectedGestion(gestion);
        setOpen(true);
    };

    return (
        <>
            <Head title="Gestiones académicas" />

            <div className="space-y-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground">Gestiones académicas</h1>
                        <p className="text-sm text-muted-foreground">
                            Administra los periodos académicos del CUP.
                        </p>
                    </div>
                    {canCreate && (
                        <Button type="button" onClick={openCreate} className="bg-[#0D2B85] text-white hover:bg-[#0a2270]">
                            <Plus className="size-4" />
                            Crear gestión
                        </Button>
                    )}
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <SummaryCard value={gestiones.length} label="Total gestiones" />
                    <SummaryCard value={summary.active?.nombre ?? 'Sin activa'} label="Gestión activa" />
                    <SummaryCard value={summary.inactive} label="Gestiones inactivas" />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Listado de gestiones</CardTitle>
                        <CardDescription>
                            Solo una gestión académica puede estar activa al mismo tiempo.
                        </CardDescription>
                    </CardHeader>
                    <div className="px-6 pb-6">
                        <GestionesAcademicasTable
                            gestiones={gestiones}
                            canUpdate={canUpdate}
                            canToggle={canToggle}
                            onEdit={openEdit}
                        />
                    </div>
                </Card>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{selectedGestion ? 'Editar gestión' : 'Crear gestión'}</DialogTitle>
                        <DialogDescription>
                            Define el periodo académico y si será la gestión activa del sistema.
                        </DialogDescription>
                    </DialogHeader>
                    <GestionAcademicaForm
                        gestion={selectedGestion}
                        canSubmit={selectedGestion ? canUpdate : canCreate}
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
