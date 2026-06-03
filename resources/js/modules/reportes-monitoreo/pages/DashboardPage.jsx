import { Head, router } from '@inertiajs/react';
import { dashboard } from '@/routes';
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
import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
    PieChart,
    Pie,
    Cell,
    Legend
} from 'recharts';

function SummaryCard({ value, label }) {
    return (
        <Card>
            <CardHeader className="pb-2">
                <CardDescription>{label}</CardDescription>
                <CardTitle className="text-4xl">{value}</CardTitle>
            </CardHeader>
        </Card>
    );
}

export default function Dashboard({
    gestiones,
    selectedGestion,
    resumen,
    estadisticasPorMateria,
}) {
    const changeGestion = (value) => {
        router.get(
            '/dashboard',
            { id_gestion: value },
            {
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const pieData = [
        { name: 'Aprobados', value: resumen.aprobados, color: '#10b981' },
        { name: 'Reprobados', value: resumen.reprobados, color: '#ef4444' },
        { name: 'Pendientes', value: resumen.pendientes, color: '#f59e0b' },
    ].filter(item => item.value > 0);

    return (
        <>
            <Head title="Panel Administrativo" />
            
            <div className="space-y-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-[#001f3f]">
                            Dashboard Administrativo
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Resumen general del proceso de admisión CUP.
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

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <SummaryCard value={resumen.total_postulantes} label="Postulantes Totales" />
                    <SummaryCard value={resumen.postulantes_con_documentacion} label="Con Documentación" />
                    <SummaryCard value={resumen.grupos_activos} label="Grupos Activos" />
                    <SummaryCard value={resumen.docentes_contratados} label="Docentes Contratados" />
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Estado de Admisión</CardTitle>
                            <CardDescription>
                                Distribución de resultados académicos de los postulantes
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="h-[300px]">
                            {pieData.length > 0 ? (
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <Pie
                                            data={pieData}
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={60}
                                            outerRadius={100}
                                            paddingAngle={5}
                                            dataKey="value"
                                        >
                                            {pieData.map((entry, index) => (
                                                <Cell key={`cell-${index}`} fill={entry.color} />
                                            ))}
                                        </Pie>
                                        <Tooltip formatter={(value) => [`${value} postulantes`, 'Cantidad']} />
                                        <Legend />
                                    </PieChart>
                                </ResponsiveContainer>
                            ) : (
                                <div className="flex h-full items-center justify-center text-muted-foreground text-sm border-dashed border rounded-md">
                                    No hay datos suficientes
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Rendimiento por Materia</CardTitle>
                            <CardDescription>
                                Promedio general obtenido en cada materia CUP
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="h-[300px]">
                            {estadisticasPorMateria.length > 0 ? (
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart
                                        data={estadisticasPorMateria}
                                        margin={{
                                            top: 5,
                                            right: 30,
                                            left: 0,
                                            bottom: 5,
                                        }}
                                    >
                                        <CartesianGrid strokeDasharray="3 3" vertical={false} />
                                        <XAxis dataKey="materia" />
                                        <YAxis domain={[0, 100]} />
                                        <Tooltip formatter={(value) => [`${value} pts`, 'Promedio']} />
                                        <Bar dataKey="promedio" fill="#3b82f6" radius={[4, 4, 0, 0]} name="Promedio" />
                                    </BarChart>
                                </ResponsiveContainer>
                            ) : (
                                <div className="flex h-full items-center justify-center text-muted-foreground text-sm border-dashed border rounded-md">
                                    No hay datos suficientes
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Panel',
            href: dashboard(),
        },
    ],
};
