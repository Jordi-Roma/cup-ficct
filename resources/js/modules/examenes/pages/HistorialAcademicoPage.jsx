import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/shared/components/ui/badge';
import { Button } from '@/shared/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/shared/components/ui/card';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';

export default function HistorialAcademicoPage({
    historial,
    postulantes,
    filters,
    canViewAny,
}) {
    const [search, setSearch] = useState(filters.search ?? '');
    const selectedPostulanteId = filters.id_postulante
        ? Number(filters.id_postulante)
        : null;

    const submitSearch = (event) => {
        event.preventDefault();

        router.get(
            '/examenes/historial',
            { search },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    return (
        <>
            <Head title="Historial académico" />

            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold text-[#001f3f]">
                        Historial académico
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Consulta notas, promedios y estado académico del CUP.
                    </p>
                </div>

                {canViewAny && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Buscar postulante</CardTitle>
                            <CardDescription>
                                Busca por CI, nombre, apellido o correo.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <form
                                onSubmit={submitSearch}
                                className="grid gap-3 md:grid-cols-[1fr_auto]"
                            >
                                <div className="space-y-2">
                                    <Label htmlFor="search">Búsqueda</Label>
                                    <Input
                                        id="search"
                                        value={search}
                                        onChange={(event) =>
                                            setSearch(event.target.value)
                                        }
                                        placeholder="CI, nombre, apellido o correo"
                                    />
                                </div>
                                <Button
                                    type="submit"
                                    className="self-end bg-[#001f3f] text-white hover:bg-[#06345f]"
                                >
                                    <Search className="mr-2 h-4 w-4" />
                                    Buscar
                                </Button>
                            </form>

                            {postulantes.length > 0 && (
                                <div className="overflow-hidden rounded-md border">
                                    <table className="w-full text-sm">
                                        <thead className="bg-muted/60 text-left">
                                            <tr>
                                                <th className="px-4 py-3">
                                                    CI
                                                </th>
                                                <th className="px-4 py-3">
                                                    Postulante
                                                </th>
                                                <th className="px-4 py-3">
                                                    Carrera
                                                </th>
                                                <th className="px-4 py-3">
                                                    Estado
                                                </th>
                                                <th className="px-4 py-3 text-right">
                                                    Acción
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {postulantes.map((postulante) => (
                                                <tr
                                                    key={
                                                        postulante.id_postulante
                                                    }
                                                    className="border-t"
                                                >
                                                    <td className="px-4 py-3">
                                                        {postulante.ci}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <div className="font-medium">
                                                            {
                                                                postulante.nombre_completo
                                                            }
                                                        </div>
                                                        <div className="text-xs text-muted-foreground">
                                                            {postulante.correo}
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {postulante.carrera ??
                                                            '-'}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <Badge variant="outline">
                                                            {postulante.estado_admision ??
                                                                'PENDIENTE'}
                                                        </Badge>
                                                    </td>
                                                    <td className="px-4 py-3 text-right">
                                                        <Button
                                                            asChild
                                                            variant={
                                                                selectedPostulanteId ===
                                                                postulante.id_postulante
                                                                    ? 'default'
                                                                    : 'outline'
                                                            }
                                                            size="sm"
                                                        >
                                                            <Link
                                                                href={`/examenes/historial?id_postulante=${postulante.id_postulante}&search=${encodeURIComponent(search)}`}
                                                            >
                                                                Ver historial
                                                            </Link>
                                                        </Button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}

                {historial ? (
                    <HistorialContent historial={historial} />
                ) : (
                    <Card>
                        <CardHeader>
                            <CardTitle>Selecciona un postulante</CardTitle>
                            <CardDescription>
                                Usa el buscador para consultar el historial
                                académico.
                            </CardDescription>
                        </CardHeader>
                    </Card>
                )}
            </div>
        </>
    );
}

function HistorialContent({ historial }) {
    const { postulante, postulacion, materias, resumen, message } = historial;

    if (message) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>Historial no disponible</CardTitle>
                    <CardDescription>{message}</CardDescription>
                </CardHeader>
            </Card>
        );
    }

    return (
        <div className="space-y-6">
            <div className="grid gap-4 md:grid-cols-4">
                <SummaryCard
                    label="Promedio final"
                    value={formatNumber(resumen.promedio_final)}
                />
                <SummaryCard
                    label="Estado final"
                    value={resumen.estado_final}
                    status={resumen.estado_final}
                />
                <SummaryCard
                    label="Notas registradas"
                    value={`${resumen.total_notas_registradas}/${resumen.total_notas_esperadas}`}
                />
                <SummaryCard
                    label="Estado admisión"
                    value={postulacion?.estado_admision ?? 'PENDIENTE'}
                />
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>{postulante.nombre_completo}</CardTitle>
                    <CardDescription>
                        CI {postulante.ci} · {postulante.correo}
                    </CardDescription>
                </CardHeader>
                <CardContent className="grid gap-4 text-sm md:grid-cols-3">
                    <InfoItem label="Gestión" value={postulacion?.gestion} />
                    <InfoItem
                        label="Grupo"
                        value={postulacion?.grupo?.nombre}
                    />
                    <InfoItem
                        label="Carrera opción 1"
                        value={postulacion?.carrera_opcion1?.nombre}
                    />
                    <InfoItem
                        label="Carrera opción 2"
                        value={postulacion?.carrera_opcion2?.nombre}
                    />
                    <InfoItem
                        label="Carrera admitida"
                        value={postulacion?.carrera_admitida?.nombre}
                    />
                    <InfoItem
                        label="Documentación"
                        value={
                            postulante.documentacion_completa
                                ? 'Completa'
                                : 'Pendiente'
                        }
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Notas por materia</CardTitle>
                    <CardDescription>
                        Este módulo es solo lectura. Las notas se gestionan
                        desde el módulo de notas.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    {materias.length > 0 ? (
                        <div className="overflow-hidden rounded-md border">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/60 text-left">
                                    <tr>
                                        <th className="px-4 py-3">Materia</th>
                                        <th className="px-4 py-3">
                                            Examen 1
                                        </th>
                                        <th className="px-4 py-3">
                                            Examen 2
                                        </th>
                                        <th className="px-4 py-3">
                                            Examen 3
                                        </th>
                                        <th className="px-4 py-3">
                                            Promedio
                                        </th>
                                        <th className="px-4 py-3">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {materias.map((materia) => (
                                        <tr
                                            key={materia.id_materia}
                                            className="border-t"
                                        >
                                            <td className="px-4 py-3 font-medium">
                                                {materia.materia}
                                            </td>
                                            <td className="px-4 py-3">
                                                {formatNumber(
                                                    materia.examen_1,
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {formatNumber(
                                                    materia.examen_2,
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {formatNumber(
                                                    materia.examen_3,
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {formatNumber(
                                                    materia.promedio,
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <StatusBadge
                                                    status={
                                                        materia.estado_materia
                                                    }
                                                />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="rounded-md border border-dashed p-6 text-sm text-muted-foreground">
                            Todavía no existen notas registradas para este
                            postulante.
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}

function SummaryCard({ label, value, status }) {
    return (
        <Card>
            <CardHeader>
                <CardDescription>{label}</CardDescription>
                <CardTitle className="text-2xl">
                    {status ? <StatusBadge status={status} /> : value}
                </CardTitle>
            </CardHeader>
        </Card>
    );
}

function InfoItem({ label, value }) {
    return (
        <div>
            <div className="text-xs font-medium uppercase text-muted-foreground">
                {label}
            </div>
            <div className="mt-1 font-medium text-[#001f3f]">
                {value ?? '-'}
            </div>
        </div>
    );
}

function StatusBadge({ status }) {
    const className =
        status === 'APROBADO'
            ? 'border-green-200 bg-green-50 text-green-700'
            : status === 'REPROBADO'
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
