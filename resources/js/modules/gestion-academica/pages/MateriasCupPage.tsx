import { Head, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useMemo, useState } from 'react';
import MateriaCupForm from '@/modules/gestion-academica/components/MateriaCupForm';
import MateriasCupTable from '@/modules/gestion-academica/components/MateriasCupTable';
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
import type { MateriaCup } from '../types/materia-cup';

type Props = {
    materias: MateriaCup[];
};

export default function MateriasCupPage({ materias }: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const canCreate = auth.permissions.includes('materias:create');
    const canUpdate = auth.permissions.includes('materias:update');
    const canToggle = auth.permissions.includes('materias:delete');
    const [open, setOpen] = useState(false);
    const [selectedMateria, setSelectedMateria] = useState<MateriaCup | null>(
        null,
    );

    const activeCount = useMemo(
        () => materias.filter((materia) => materia.activo).length,
        [materias],
    );

    const openCreate = () => {
        setSelectedMateria(null);
        setOpen(true);
    };

    const openEdit = (materia: MateriaCup) => {
        setSelectedMateria(materia);
        setOpen(true);
    };

    return (
        <>
            <Head title="Materias del CUP" />

            <div className="space-y-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-[#001f3f]">
                            Materias del CUP
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Administra el catálogo de materias del Curso
                            Preuniversitario.
                        </p>
                    </div>
                    {canCreate && (
                        <Button
                            type="button"
                            onClick={openCreate}
                            className="bg-[#e30613] text-white hover:bg-[#bb0710]"
                        >
                            <Plus className="size-4" />
                            Crear materia
                        </Button>
                    )}
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle>{materias.length}</CardTitle>
                            <CardDescription>Total de materias</CardDescription>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>{activeCount}</CardTitle>
                            <CardDescription>Materias activas</CardDescription>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>{materias.length - activeCount}</CardTitle>
                            <CardDescription>
                                Materias inactivas
                            </CardDescription>
                        </CardHeader>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Listado de materias</CardTitle>
                        <CardDescription>
                            Crea, edita y activa o desactiva materias del CUP.
                        </CardDescription>
                    </CardHeader>
                    <div className="px-6 pb-6">
                        <MateriasCupTable
                            materias={materias}
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
                        <DialogTitle>
                            {selectedMateria ? 'Editar materia' : 'Crear materia'}
                        </DialogTitle>
                        <DialogDescription>
                            Mantén actualizado el catálogo base del CUP.
                        </DialogDescription>
                    </DialogHeader>
                    <MateriaCupForm
                        materia={selectedMateria}
                        canSubmit={selectedMateria ? canUpdate : canCreate}
                        onSuccess={() => setOpen(false)}
                    />
                </DialogContent>
            </Dialog>
        </>
    );
}
