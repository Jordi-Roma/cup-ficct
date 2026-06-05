import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Play, Settings } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/shared/components/input-error';
import { Badge } from '@/shared/components/ui/badge';
import { Button } from '@/shared/components/ui/button';
import {
    Card,
    CardContent,
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
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/shared/components/ui/select';

export default function AdmisionCuposPage({
    gestiones,
    selectedGestion,
    carreras,
    cupos,
    postulantes,
    resumen,
}) {
    const { auth } = usePage().props;
    const canUpdate = auth.permissions.includes('admision:update');
    const canProcess = auth.permissions.includes('admision:process');
    const [open, setOpen] = useState(false);
    const [selectedCupo, setSelectedCupo] = useState(null);
    const {
        data,
        setData,
        post,
        processing,
        errors,
        reset,
        clearErrors,
    } = useForm({
        id_carrera: '',
        id_gestion: selectedGestion.id_gestion,
        cupo_maximo: '',
    });
    const processForm = useForm({
        id_gestion: selectedGestion.id_gestion,
    });

    const changeGestion = (value) => {
        router.get(
            '/postulantes/admision-cupos',
            { id_gestion: value },
            {
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const openCupoForm = (cupo) => {
        clearErrors();
        setSelectedCupo(cupo);
        setData({
            id_carrera: cupo.id_carrera,
            id_gestion: selectedGestion.id_gestion,
            cupo_maximo: cupo.cupo_maximo || '',
        });
        setOpen(true);
    };

    const submitCupo = (event) => {
        event.preventDefault();
        post('/postulantes/admision-cupos/cupos', {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    };

    const processAdmission = () => {
        processForm.post('/postulantes/admision-cupos/procesar', {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Admisión por cupos" />

            <div className="space-y-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-[#001f3f]">
                            Admisión por cupos
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Configura cupos por carrera y procesa la admisión
                            final del CUP.
                        </p>
                    </div>

                    <div className="w-full space-y-2 md:w-72">
                        <Label>Gestión académica</Label>
                        <Select
                            value={String(selectedGestion.id_gestion)}
                            onValueChange={changeGestion}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Gestión" />
                            </SelectTrigger>
                            <SelectContent>
                                {gestiones.map((gestion) => (
                                    <SelectItem
                                        key={gestion.id_gestion}
                                        value={String(gestion.id_gestion)}
                                    >
                                        {gestion.nombre}
                                        {gestion.activo ? ' (activa)' : ''}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-5">
                    <SummaryCard
                        value={resumen.total_postulantes}
                        label="Postulantes"
                    />
                    <SummaryCard value={resumen.admitidos} label="Admitidos" />
                    <SummaryCard
                        value={resumen.no_admitidos}
                        label="No admitidos"
                    />
                    <SummaryCard
                        value={resumen.pendientes}
                        label="Pendientes"
                    />
                    <SummaryCard
                        value={resumen.cupos_configurados}
                        label="Cupos configurados"
                    />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Cupos por carrera</CardTitle>
                        <CardDescription>
                            Define el cupo máximo y revisa la disponibilidad
                            actual por carrera.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-hidden rounded-md border">
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-muted/60 text-left">
                                        <tr>
                                            <th className="px-4 py-3">
                                                Carrera
                                            </th>
                                            <th className="px-4 py-3">
                                                Cupo máximo
                                            </th>
                                            <th className="px-4 py-3">
                                                Admitidos
                                            </th>
                                            <th className="px-4 py-3">
                                                Disponibles
                                            </th>
                                            <th className="px-4 py-3 text-right">
                                                Acciones
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {cupos.map((cupo) => (
                                            <tr
                                                key={cupo.id_carrera}
                                                className="border-t"
                                            >
                                                <td className="px-4 py-3 font-medium">
                                                    {cupo.carrera}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {cupo.cupo_maximo}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {cupo.admitidos}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {cupo.disponibles}
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    {canUpdate && (
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                openCupoForm(
                                                                    cupo,
                                                                )
                                                            }
                                                        >
                                                            <Settings className="mr-2 h-4 w-4" />
                                                            Configurar
                                                        </Button>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="gap-4 md:flex-row md:items-start md:justify-between">
                        <div className="space-y-1">
                            <CardTitle>Proceso de admisión</CardTitle>
                            <CardDescription>
                                El proceso recalcula los estados de admisión de
                                la gestión seleccionada.
                            </CardDescription>
                        </div>
                        {canProcess && (
                            <Button
                                type="button"
                                className="bg-[#001f3f] text-white hover:bg-[#06345f]"
                                disabled={processForm.processing}
                                onClick={processAdmission}
                            >
                                <Play className="mr-2 h-4 w-4" />
                                Procesar admisión
                            </Button>
                        )}
                    </CardHeader>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Postulantes</CardTitle>
                        <CardDescription>
                            Resultado académico y estado de admisión por
                            postulante.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-hidden rounded-md border">
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-muted/60 text-left">
                                        <tr>
                                            <th className="px-4 py-3">CI</th>
                                            <th className="px-4 py-3">
                                                Postulante
                                            </th>
                                            <th className="px-4 py-3">
                                                Doc.
                                            </th>
                                            <th className="px-4 py-3">
                                                Opción 1
                                            </th>
                                            <th className="px-4 py-3">
                                                Opción 2
                                            </th>
                                            <th className="px-4 py-3">
                                                Admitida
                                            </th>
                                            <th className="px-4 py-3">
                                                Promedio
                                            </th>
                                            <th className="px-4 py-3">
                                                Académico
                                            </th>
                                            <th className="px-4 py-3">
                                                Admisión
                                            </th>
                                            <th className="px-4 py-3">
                                                Grupo
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {postulantes.map((postulante) => (
                                            <tr
                                                key={
                                                    postulante.id_postulacion
                                                }
                                                className="border-t"
                                            >
                                                <td className="px-4 py-3">
                                                    {postulante.ci}
                                                </td>
                                                <td className="px-4 py-3 font-medium">
                                                    {
                                                        postulante.nombre_completo
                                                    }
                                                </td>
                                                <td className="px-4 py-3">
                                                    {postulante.documentacion_completa
                                                        ? 'Completa'
                                                        : 'Pendiente'}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {
                                                        postulante.carrera_opcion1
                                                    }
                                                </td>
                                                <td className="px-4 py-3">
                                                    {postulante.carrera_opcion2 ??
                                                        '-'}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {postulante.carrera_admitida ??
                                                        '-'}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {formatNumber(
                                                        postulante.promedio_final,
                                                    )}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <StatusBadge
                                                        status={
                                                            postulante.estado_academico
                                                        }
                                                    />
                                                </td>
                                                <td className="px-4 py-3">
                                                    <StatusBadge
                                                        status={
                                                            postulante.estado_admision
                                                        }
                                                    />
                                                </td>
                                                <td className="px-4 py-3">
                                                    {postulante.grupo ?? '-'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Configurar cupo</DialogTitle>
                        <DialogDescription>
                            Actualiza el cupo máximo de la carrera para la
                            gestión seleccionada.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submitCupo} className="space-y-4">
                        <div className="space-y-2">
                            <Label>Carrera</Label>
                            <Select
                                value={String(data.id_carrera)}
                                onValueChange={(value) =>
                                    setData('id_carrera', value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Carrera" />
                                </SelectTrigger>
                                <SelectContent>
                                    {carreras.map((carrera) => (
                                        <SelectItem
                                            key={carrera.id_carrera}
                                            value={String(carrera.id_carrera)}
                                        >
                                            {carrera.nombre}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.id_carrera} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="cupo_maximo">Cupo máximo</Label>
                            <Input
                                id="cupo_maximo"
                                type="number"
                                min="1"
                                value={data.cupo_maximo}
                                onChange={(event) =>
                                    setData('cupo_maximo', event.target.value)
                                }
                            />
                            <InputError message={errors.cupo_maximo} />
                        </div>

                        <input
                            type="hidden"
                            value={data.id_gestion}
                            readOnly
                        />

                        <div className="flex justify-end gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setOpen(false)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                disabled={processing}
                                className="bg-[#e30613] text-white hover:bg-[#bb0710]"
                            >
                                Guardar
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}

function SummaryCard({ value, label }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-2xl">{value}</CardTitle>
                <CardDescription>{label}</CardDescription>
            </CardHeader>
        </Card>
    );
}

function StatusBadge({ status }) {
    const className =
        status === 'ADMITIDO' || status === 'APROBADO'
            ? 'border-green-200 bg-green-50 text-green-700'
            : status === 'NO_ADMITIDO' || status === 'REPROBADO'
              ? 'border-red-200 bg-red-50 text-red-700'
              : 'border-yellow-200 bg-yellow-50 text-yellow-700';

    return (
        <Badge variant="outline" className={className}>
            {status ?? 'PENDIENTE'}
        </Badge>
    );
}

function formatNumber(value) {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    return Number(value).toFixed(2);
}
