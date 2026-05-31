import { Head, router, usePage } from '@inertiajs/react';
import { Plus, Shuffle, Wand2 } from 'lucide-react';
import { useState } from 'react';
import GrupoAcademicoForm from '@/modules/gestion-academica/components/GrupoAcademicoForm';
import GrupoPostulantesDialog from '@/modules/gestion-academica/components/GrupoPostulantesDialog';
import GruposAcademicosTable from '@/modules/gestion-academica/components/GruposAcademicosTable';
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
    GrupoAcademico,
    GruposResumen,
} from '../types/grupo-academico';

type Props = {
    grupos: GrupoAcademico[];
    resumen: GruposResumen;
};

export default function GruposAcademicosPage({ grupos, resumen }: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const canCreate = auth.permissions.includes('grupos:create');
    const canRead = auth.permissions.includes('grupos:read');
    const canUpdate = auth.permissions.includes('grupos:update');
    const canToggle = auth.permissions.includes('grupos:delete');
    const [open, setOpen] = useState(false);
    const [postulantesOpen, setPostulantesOpen] = useState(false);
    const [selectedGrupo, setSelectedGrupo] = useState<GrupoAcademico | null>(
        null,
    );
    const [selectedPostulantesGrupo, setSelectedPostulantesGrupo] =
        useState<GrupoAcademico | null>(null);

    const openCreate = () => {
        setSelectedGrupo(null);
        setOpen(true);
    };

    const openEdit = (grupo: GrupoAcademico) => {
        setSelectedGrupo(grupo);
        setOpen(true);
    };

    const openPostulantes = (grupo: GrupoAcademico) => {
        setSelectedPostulantesGrupo(grupo);
        setPostulantesOpen(true);
    };

    const generateGroups = () => {
        router.post('/academico/grupos/generar', undefined, {
            preserveScroll: true,
        });
    };

    const assignPostulantes = () => {
        router.post('/academico/grupos/asignar-postulantes', undefined, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Grupos académicos" />

            <div className="space-y-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-[#001f3f]">
                            Grupos académicos
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Administra los grupos del CUP para la gestión
                            activa.
                        </p>
                    </div>
                    <div className="flex flex-col gap-2 sm:flex-row">
                        {canCreate && (
                            <>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={generateGroups}
                                >
                                    <Wand2 className="size-4" />
                                    Generar grupos
                                </Button>
                                <Button
                                    type="button"
                                    onClick={openCreate}
                                    className="bg-[#e30613] text-white hover:bg-[#bb0710]"
                                >
                                    <Plus className="size-4" />
                                    Crear grupo
                                </Button>
                            </>
                        )}
                        {canUpdate && (
                            <Button
                                type="button"
                                className="bg-[#001f3f] text-white hover:bg-[#06345f]"
                                onClick={assignPostulantes}
                            >
                                <Shuffle className="size-4" />
                                Asignar postulantes
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-5">
                    <Card>
                        <CardHeader>
                            <CardTitle>
                                {resumen.gestion_activa.nombre}
                            </CardTitle>
                            <CardDescription>Gestión activa</CardDescription>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>{resumen.total_inscritos}</CardTitle>
                            <CardDescription>
                                Inscritos elegibles
                            </CardDescription>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>
                                {resumen.grupos_necesarios}
                            </CardTitle>
                            <CardDescription>
                                Grupos necesarios
                            </CardDescription>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>{resumen.grupos_activos}</CardTitle>
                            <CardDescription>Grupos activos</CardDescription>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>{resumen.grupos_faltantes}</CardTitle>
                            <CardDescription>Grupos faltantes</CardDescription>
                        </CardHeader>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Listado de grupos</CardTitle>
                        <CardDescription>
                            Crea, edita y organiza grupos con capacidad máxima
                            de 70 postulantes.
                        </CardDescription>
                    </CardHeader>
                    <div className="px-6 pb-6">
                        <GruposAcademicosTable
                            grupos={grupos}
                            canUpdate={canUpdate}
                            canToggle={canToggle}
                            canViewPostulantes={canRead}
                            onEdit={openEdit}
                            onViewPostulantes={openPostulantes}
                        />
                    </div>
                </Card>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {selectedGrupo ? 'Editar grupo' : 'Crear grupo'}
                        </DialogTitle>
                        <DialogDescription>
                            Define el nombre y la capacidad del grupo.
                        </DialogDescription>
                    </DialogHeader>
                    <GrupoAcademicoForm
                        grupo={selectedGrupo}
                        canSubmit={selectedGrupo ? canUpdate : canCreate}
                        onSuccess={() => setOpen(false)}
                    />
                </DialogContent>
            </Dialog>

            <GrupoPostulantesDialog
                grupo={selectedPostulantesGrupo}
                open={postulantesOpen}
                onOpenChange={setPostulantesOpen}
            />
        </>
    );
}
