import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { useEffect, useMemo } from 'react';
import InputError from '@/shared/components/input-error';
import { Badge } from '@/shared/components/ui/badge';
import { Button } from '@/shared/components/ui/button';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/shared/components/ui/card';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/shared/components/ui/select';

export default function NotasPage({ postulantes, options, filters, resumen }) {
    const { auth } = usePage().props;
    const canCreate = auth.permissions.includes('notas:create');
    const canUpdate = auth.permissions.includes('notas:update');
    const initialRows = useMemo(
        () =>
            postulantes.map((postulante) => ({
                id_nota: postulante.id_nota,
                id_postulacion: postulante.id_postulacion,
                id_materia: postulante.materia?.id_materia ?? filters.id_materia ?? '',
                nro_examen: postulante.nro_examen ?? filters.nro_examen ?? '',
                nota: postulante.nota ?? '',
            })),
        [postulantes, filters.id_materia, filters.nro_examen],
    );
    const { data, setData, post, processing, errors, reset } = useForm({
        notas: initialRows,
    });

    useEffect(() => {
        setData('notas', initialRows);
    }, [initialRows, setData]);

    const selectedGroup = filters.id_grupo ?? '';
    const selectedMateria = filters.id_materia ?? '';
    const selectedExam = filters.nro_examen ?? '';
    const canSave = canCreate && selectedMateria && selectedExam;

    const applyFilters = (changes) => {
        router.get(
            '/examenes/notas',
            {
                ...filters,
                ...changes,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const updateNota = (index, value) => {
        const next = [...data.notas];
        next[index] = {
            ...next[index],
            nota: value,
        };
        setData('notas', next);
    };

    const submit = (event) => {
        event.preventDefault();
        post('/examenes/notas/lote', {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <>
            <Head title="Notas" />

            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold text-foreground">
                        Notas
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Registra y consulta calificaciones de postulantes del
                        CUP.
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-4">
                    <SummaryCard
                        value={resumen.total_postulantes}
                        label="Postulantes listados"
                    />
                    <SummaryCard
                        value={resumen.notas_registradas}
                        label="Notas registradas"
                    />
                    <SummaryCard
                        value={resumen.pendientes}
                        label="Notas pendientes"
                    />
                    <SummaryCard
                        value={resumen.promedio_general ?? '-'}
                        label="Promedio general"
                    />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Filtros</CardTitle>
                        <CardDescription>
                            Selecciona grupo, materia y examen para registrar
                            notas.
                        </CardDescription>
                    </CardHeader>
                    <div className="grid gap-4 px-6 pb-6 md:grid-cols-5">
                        <SelectFilter
                            label="Grupo"
                            value={selectedGroup}
                            placeholder="Todos"
                            items={options.grupos.map((grupo) => ({
                                value: grupo.id_grupo.toString(),
                                label: grupo.nombre,
                            }))}
                            onChange={(value) =>
                                applyFilters({
                                    id_grupo: value === 'all' ? '' : value,
                                })
                            }
                        />
                        <SelectFilter
                            label="Materia"
                            value={selectedMateria}
                            placeholder="Materia"
                            items={options.materias.map((materia) => ({
                                value: materia.id_materia.toString(),
                                label: materia.nombre,
                            }))}
                            onChange={(value) =>
                                applyFilters({ id_materia: value })
                            }
                        />
                        <SelectFilter
                            label="Examen"
                            value={selectedExam}
                            placeholder="Examen"
                            items={options.examenes.map((exam) => ({
                                value: exam.toString(),
                                label: `Examen ${exam}`,
                            }))}
                            onChange={(value) =>
                                applyFilters({ nro_examen: value })
                            }
                        />
                        <div className="grid gap-2">
                            <Label htmlFor="search">Búsqueda</Label>
                            <Input
                                id="search"
                                defaultValue={filters.search ?? ''}
                                placeholder="CI, nombre o correo"
                                onKeyDown={(event) => {
                                    if (event.key === 'Enter') {
                                        applyFilters({
                                            search: event.currentTarget.value,
                                        });
                                    }
                                }}
                            />
                        </div>
                        <SelectFilter
                            label="Estado"
                            value={filters.estado_final ?? ''}
                            placeholder="Todos"
                            items={[
                                { value: 'PENDIENTE', label: 'Pendiente' },
                                { value: 'APROBADO', label: 'Aprobado' },
                                { value: 'REPROBADO', label: 'Reprobado' },
                            ]}
                            onChange={(value) =>
                                applyFilters({
                                    estado_final: value === 'all' ? '' : value,
                                })
                            }
                        />
                    </div>
                </Card>

                <form onSubmit={submit}>
                    <Card>
                        <CardHeader className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <CardTitle>Registro de notas</CardTitle>
                                <CardDescription>
                                    Guarda notas entre 0 y 100 para el examen
                                    seleccionado.
                                </CardDescription>
                            </div>
                            {canSave && (
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="bg-[#0D2B85] text-white hover:bg-[#0a2270]"
                                >
                                    <Save className="size-4" />
                                    Guardar notas
                                </Button>
                            )}
                        </CardHeader>
                        <div className="px-6 pb-6">
                            <InputError message={errors.notas} />
                            <InputError message={errors.nota} />
                            <InputError message={errors.id_materia} />

                            <NotasTable
                                postulantes={postulantes}
                                notas={data.notas}
                                canEdit={canCreate || canUpdate}
                                canSave={canSave}
                                onChange={updateNota}
                            />
                        </div>
                    </Card>
                </form>
            </div>
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

function SelectFilter({ label, value, placeholder, items, onChange }) {
    return (
        <div className="grid gap-2">
            <Label>{label}</Label>
            <Select value={value || 'all'} onValueChange={onChange}>
                <SelectTrigger className="w-full">
                    <SelectValue placeholder={placeholder} />
                </SelectTrigger>
                <SelectContent>
                    {placeholder === 'Todos' && (
                        <SelectItem value="all">Todos</SelectItem>
                    )}
                    {items.map((item) => (
                        <SelectItem key={item.value} value={item.value}>
                            {item.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}

function NotasTable({ postulantes, notas, canEdit, canSave, onChange }) {
    if (postulantes.length === 0) {
        return (
            <div className="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground">
                No hay postulantes para los filtros seleccionados.
            </div>
        );
    }

    return (
        <div className="overflow-hidden rounded-md border">
            <div className="hidden overflow-x-auto lg:block">
                <table className="w-full min-w-[980px] text-sm">
                    <thead className="bg-slate-50 text-left">
                        <tr>
                            <th className="px-4 py-3">CI</th>
                            <th className="px-4 py-3">Nombre completo</th>
                            <th className="px-4 py-3">Grupo</th>
                            <th className="px-4 py-3">Materia</th>
                            <th className="px-4 py-3">Examen</th>
                            <th className="px-4 py-3">Nota</th>
                            <th className="px-4 py-3">Prom. materia</th>
                            <th className="px-4 py-3">Prom. final</th>
                            <th className="px-4 py-3">Estado</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {postulantes.map((postulante, index) => (
                            <tr key={postulante.id_postulacion}>
                                <td className="px-4 py-3">{postulante.ci}</td>
                                <td className="px-4 py-3">
                                    <div className="font-medium">
                                        {postulante.nombre_completo}
                                    </div>
                                    <div className="text-xs text-muted-foreground">
                                        {postulante.correo}
                                    </div>
                                </td>
                                <td className="px-4 py-3">
                                    {postulante.grupo?.nombre}
                                </td>
                                <td className="px-4 py-3">
                                    {postulante.materia?.nombre ?? '-'}
                                </td>
                                <td className="px-4 py-3">
                                    {postulante.nro_examen ?? '-'}
                                </td>
                                <td className="px-4 py-3">
                                    <Input
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        value={notas[index]?.nota ?? ''}
                                        disabled={!canEdit || !canSave}
                                        onChange={(event) =>
                                            onChange(index, event.target.value)
                                        }
                                        className="w-24"
                                    />
                                </td>
                                <td className="px-4 py-3">
                                    {postulante.promedio_materia ?? '-'}
                                </td>
                                <td className="px-4 py-3">
                                    {postulante.promedio_final ?? '-'}
                                </td>
                                <td className="px-4 py-3">
                                    <EstadoBadge
                                        estado={postulante.estado_final}
                                    />
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="grid gap-3 p-3 lg:hidden">
                {postulantes.map((postulante, index) => (
                    <div
                        key={postulante.id_postulacion}
                        className="space-y-3 rounded-md border p-4"
                    >
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <h3 className="font-semibold">
                                    {postulante.nombre_completo}
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    {postulante.ci} - {postulante.correo}
                                </p>
                            </div>
                            <EstadoBadge estado={postulante.estado_final} />
                        </div>
                        <div className="grid gap-1 text-sm">
                            <span>Grupo: {postulante.grupo?.nombre}</span>
                            <span>
                                Materia: {postulante.materia?.nombre ?? '-'}
                            </span>
                            <span>Examen: {postulante.nro_examen ?? '-'}</span>
                            <span>
                                Promedio materia:{' '}
                                {postulante.promedio_materia ?? '-'}
                            </span>
                            <span>
                                Promedio final: {postulante.promedio_final ?? '-'}
                            </span>
                        </div>
                        <Input
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            value={notas[index]?.nota ?? ''}
                            disabled={!canEdit || !canSave}
                            onChange={(event) =>
                                onChange(index, event.target.value)
                            }
                        />
                    </div>
                ))}
            </div>
        </div>
    );
}

function EstadoBadge({ estado }) {
    const variant = estado === 'APROBADO' ? 'default' : 'secondary';

    return <Badge variant={variant}>{estado}</Badge>;
}
