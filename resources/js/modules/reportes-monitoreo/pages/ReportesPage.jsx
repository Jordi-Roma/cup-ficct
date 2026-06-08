import { Head, router, usePage } from '@inertiajs/react';
import { Download, Filter, RotateCcw } from 'lucide-react';
import { Badge } from '@/shared/components/ui/badge';
import { Button } from '@/shared/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/shared/components/ui/card';
import { Label } from '@/shared/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/shared/components/ui/select';

export default function ReportesPage({
    gestiones,
    selectedGestion,
    options,
    filters,
    resumen,
    listaGeneral,
    aprobados,
    reprobados,
    estadisticasPorMateria,
    grupos,
    docentesPorGrupo,
    gruposConMasAprobados,
}) {
    const { auth } = usePage().props;
    const canExport = auth.permissions.includes('reportes:export');
    const normalizedFilters = {
        id_gestion: String(filters.id_gestion ?? selectedGestion.id_gestion),
        id_grupo: filters.id_grupo ? String(filters.id_grupo) : 'TODOS',
        id_materia: filters.id_materia ? String(filters.id_materia) : 'TODOS',
        estado: filters.estado ?? 'TODOS',
    };

    const applyFilter = (key, value) => {
        const nextFilters = {
            ...normalizedFilters,
            [key]: value,
        };

        if (key === 'id_gestion') {
            nextFilters.id_grupo = 'TODOS';
        }

        router.get('/reportes', cleanFilters(nextFilters), {
            preserveScroll: true,
            replace: true,
        });
    };

    const resetFilters = () => {
        router.get(
            '/reportes',
            { id_gestion: normalizedFilters.id_gestion },
            {
                preserveScroll: true,
                replace: true,
            },
        );
    };

    return (
        <>
            <Head title="Reportes" />

            <div className="space-y-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground">
                            Reportes
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Consulta reportes estadísticos y operativos del
                            proceso de admisión CUP.
                        </p>
                    </div>
                </div>

                <Card>
                    <CardHeader className="gap-4 md:flex-row md:items-start md:justify-between">
                        <div className="space-y-1">
                            <CardTitle className="flex items-center gap-2">
                                <Filter className="h-5 w-5" />
                                Filtros de reporte
                            </CardTitle>
                            <CardDescription>
                                Filtra por gestión, grupo, materia y estado
                                para enfocar los resultados.
                            </CardDescription>
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={resetFilters}
                        >
                            <RotateCcw className="mr-2 h-4 w-4" />
                            Limpiar filtros
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <FilterSelect
                                label="Gestión"
                                value={normalizedFilters.id_gestion}
                                onChange={(value) =>
                                    applyFilter('id_gestion', value)
                                }
                                options={gestiones.map((gestion) => ({
                                    value: String(gestion.id_gestion),
                                    label: `${gestion.nombre}${gestion.activo ? ' (activa)' : ''}`,
                                }))}
                            />

                            <FilterSelect
                                label="Grupo"
                                value={normalizedFilters.id_grupo}
                                onChange={(value) =>
                                    applyFilter('id_grupo', value)
                                }
                                options={[
                                    { value: 'TODOS', label: 'Todos' },
                                    ...(options?.grupos ?? []).map((grupo) => ({
                                        value: String(grupo.id_grupo),
                                        label: `${grupo.nombre}${grupo.activo ? '' : ' (inactivo)'}`,
                                    })),
                                ]}
                            />

                            <FilterSelect
                                label="Materia"
                                value={normalizedFilters.id_materia}
                                onChange={(value) =>
                                    applyFilter('id_materia', value)
                                }
                                options={[
                                    { value: 'TODOS', label: 'Todas' },
                                    ...(options?.materias ?? []).map(
                                        (materia) => ({
                                            value: String(materia.id_materia),
                                            label: `${materia.nombre}${materia.activo ? '' : ' (inactiva)'}`,
                                        }),
                                    ),
                                ]}
                            />

                            <FilterSelect
                                label="Estado"
                                value={normalizedFilters.estado}
                                onChange={(value) =>
                                    applyFilter('estado', value)
                                }
                                options={options?.estados ?? [
                                    { value: 'TODOS', label: 'Todos' },
                                ]}
                            />
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-4 md:grid-cols-4 xl:grid-cols-7">
                    <SummaryCard
                        value={resumen.total_postulantes}
                        label="Postulantes"
                    />
                    <SummaryCard value={resumen.aprobados} label="Aprobados" />
                    <SummaryCard
                        value={resumen.reprobados}
                        label="Reprobados"
                    />
                    <SummaryCard
                        value={resumen.pendientes}
                        label="Pendientes"
                    />
                    <SummaryCard
                        value={resumen.admitidos}
                        label="Admitidos"
                    />
                    <SummaryCard
                        value={resumen.no_admitidos}
                        label="No admitidos"
                    />
                    <SummaryCard
                        value={resumen.asignaciones_activas}
                        label="Asignaciones"
                    />
                </div>

                <ReportSection
                    title="Lista general de postulantes"
                    description="Postulantes con carrera, grupo, promedio y estado académico."
                    exportType="postulantes"
                    canExport={canExport}
                    filters={normalizedFilters}
                >
                    <DataTable
                        columns={[
                            'CI',
                            'Postulante',
                            'Carrera 1',
                            'Estado admisión',
                            'Grupo',
                            'Promedio',
                            'Estado final',
                        ]}
                        rows={listaGeneral.map((row) => [
                            row.ci,
                            row.nombre_completo,
                            row.carrera_opcion1,
                            <StatusBadge
                                key="admision"
                                status={row.estado_admision}
                            />,
                            row.grupo,
                            formatNumber(row.promedio_final),
                            <StatusBadge
                                key="estado"
                                status={row.estado_final}
                            />,
                        ])}
                    />
                </ReportSection>

                <div className="grid gap-6 xl:grid-cols-2">
                    <ReportSection
                        title="Postulantes aprobados"
                        description="Postulantes con notas completas y promedio final mayor o igual a 60."
                        exportType="aprobados"
                        canExport={canExport}
                        filters={normalizedFilters}
                    >
                        <SimpleStudentTable rows={aprobados} />
                    </ReportSection>

                    <ReportSection
                        title="Postulantes reprobados"
                        description="Postulantes con notas completas y promedio final menor a 60."
                        exportType="reprobados"
                        canExport={canExport}
                        filters={normalizedFilters}
                    >
                        <SimpleStudentTable rows={reprobados} />
                    </ReportSection>
                </div>

                <ReportSection
                    title="Estadísticas por materia"
                    description="Promedios generales, aprobados, reprobados y pendientes por materia."
                    exportType="materias"
                    canExport={canExport}
                    filters={normalizedFilters}
                >
                    <DataTable
                        columns={[
                            'Materia',
                            'Promedio',
                            'Notas',
                            'Aprobados',
                            'Reprobados',
                            'Pendientes',
                        ]}
                        rows={estadisticasPorMateria.map((row) => [
                            row.materia,
                            formatNumber(row.promedio),
                            row.cantidad_notas,
                            row.aprobados,
                            row.reprobados,
                            row.pendientes,
                        ])}
                    />
                </ReportSection>

                <ReportSection
                    title="Grupos habilitados"
                    description="Capacidad, postulantes asignados y cupos disponibles."
                >
                    <DataTable
                        columns={[
                            'Grupo',
                            'Capacidad',
                            'Asignados',
                            'Disponibles',
                            'Estado',
                        ]}
                        rows={grupos.map((row) => [
                            row.grupo,
                            row.capacidad_maxima,
                            row.postulantes_asignados,
                            row.cupos_disponibles,
                            row.activo ? 'Activo' : 'Inactivo',
                        ])}
                    />
                </ReportSection>

                <ReportSection
                    title="Docentes por grupo"
                    description="Asignaciones académicas con docente, materia, horario y aula."
                    exportType="docentes-grupo"
                    canExport={canExport}
                    filters={normalizedFilters}
                >
                    <DataTable
                        columns={[
                            'Grupo',
                            'Materia',
                            'Docente',
                            'Horario',
                            'Aula',
                        ]}
                        rows={docentesPorGrupo.map((row) => [
                            row.grupo,
                            row.materia,
                            row.docente,
                            row.horario,
                            row.aula,
                        ])}
                    />
                </ReportSection>

                <ReportSection
                    title="Grupos con mayor cantidad de aprobados"
                    description="Ranking de grupos por cantidad y porcentaje de aprobados."
                    exportType="grupos-aprobados"
                    canExport={canExport}
                    filters={normalizedFilters}
                >
                    <DataTable
                        columns={[
                            'Grupo',
                            'Aprobados',
                            'Total postulantes',
                            '% aprobados',
                        ]}
                        rows={gruposConMasAprobados.map((row) => [
                            row.grupo,
                            row.aprobados,
                            row.total_postulantes,
                            `${formatNumber(row.porcentaje_aprobados)}%`,
                        ])}
                    />
                </ReportSection>
            </div>
        </>
    );
}

function FilterSelect({ label, value, onChange, options }) {
    return (
        <div className="space-y-2">
            <Label>{label}</Label>
            <Select value={value} onValueChange={onChange}>
                <SelectTrigger>
                    <SelectValue placeholder={label} />
                </SelectTrigger>
                <SelectContent>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
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

function ReportSection({
    title,
    description,
    exportType,
    canExport = false,
    filters,
    children,
}) {
    const exportHref = exportType
        ? `/reportes/export/${exportType}?${new URLSearchParams(cleanFilters(filters)).toString()}`
        : null;

    return (
        <Card>
            <CardHeader className="gap-4 md:flex-row md:items-start md:justify-between">
                <div className="space-y-1">
                    <CardTitle>{title}</CardTitle>
                    <CardDescription>{description}</CardDescription>
                </div>
                {canExport && exportHref && (
                    <Button asChild variant="outline" size="sm">
                        <a href={exportHref}>
                            <Download className="mr-2 h-4 w-4" />
                            Exportar Excel
                        </a>
                    </Button>
                )}
            </CardHeader>
            <CardContent>{children}</CardContent>
        </Card>
    );
}

function SimpleStudentTable({ rows }) {
    return (
        <DataTable
            columns={['CI', 'Postulante', 'Carrera', 'Promedio']}
            rows={rows.map((row) => [
                row.ci,
                row.nombre_completo,
                row.carrera_opcion1,
                formatNumber(row.promedio_final),
            ])}
        />
    );
}

function DataTable({ columns, rows }) {
    if (rows.length === 0) {
        return (
            <div className="rounded-md border border-dashed p-6 text-sm text-muted-foreground">
                No hay datos para mostrar.
            </div>
        );
    }

    return (
        <div className="overflow-hidden rounded-md border">
            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead className="bg-muted/60 text-left">
                        <tr>
                            {columns.map((column) => (
                                <th
                                    key={column}
                                    className="whitespace-nowrap px-4 py-3"
                                >
                                    {column}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row, index) => (
                            <tr key={index} className="border-t">
                                {row.map((cell, cellIndex) => (
                                    <td
                                        key={cellIndex}
                                        className="whitespace-nowrap px-4 py-3"
                                    >
                                        {cell ?? '-'}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

function StatusBadge({ status }) {
    const className =
        status === 'APROBADO' || status === 'ADMITIDO'
            ? 'border-green-200 bg-green-50 text-green-700'
            : status === 'REPROBADO' || status === 'NO_ADMITIDO'
              ? 'border-red-200 bg-red-50 text-red-700'
              : 'border-yellow-200 bg-yellow-50 text-yellow-700';

    return (
        <Badge variant="outline" className={className}>
            {status ?? 'PENDIENTE'}
        </Badge>
    );
}

function cleanFilters(filters) {
    return Object.fromEntries(
        Object.entries(filters ?? {}).filter(([, value]) => {
            return value !== null && value !== undefined && value !== 'TODOS';
        }),
    );
}

function formatNumber(value) {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    return Number(value).toFixed(2);
}
