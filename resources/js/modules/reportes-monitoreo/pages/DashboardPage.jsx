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

function SummaryCard({ value, label, icon: Icon }) {
    return (
        <Card className="relative overflow-hidden">
            <div className="absolute inset-0 bg-gradient-to-br from-[#F4747A]/5 via-transparent to-[#B8DFF5]/10 dark:from-[#2A1F5A]/30 dark:to-[#1E2D5E]/20 pointer-events-none rounded-2xl" />
            <CardHeader className="pb-2 relative">
                <CardDescription className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                    {label}
                </CardDescription>
                <CardTitle className="text-3xl font-bold text-foreground">
                    {value ?? '—'}
                </CardTitle>
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
        { name: 'Aprobados', value: resumen.aprobados,  color: '#B8DFF5' },   /* celeste pastel */
        { name: 'Reprobados', value: resumen.reprobados, color: '#F4747A' },  /* coral */
        { name: 'Pendientes', value: resumen.pendientes, color: '#C8B8F8' },  /* lavanda */
    ].filter((item) => item.value > 0);

    /* IDs para gradiente del BarChart */
    const BAR_GRADIENT_ID = 'barGradientLight';

    return (
        <>
            <Head title="Dashboard Administrativo" />
            
            <div className="space-y-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground">
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
                                            innerRadius={65}
                                            outerRadius={105}
                                            paddingAngle={4}
                                            dataKey="value"
                                        >
                                            {pieData.map((entry, index) => (
                                                <Cell
                                                    key={`cell-${index}`}
                                                    fill={entry.color}
                                                    stroke="transparent"
                                                />
                                            ))}
                                        </Pie>
                                        <Tooltip
                                            formatter={(value) => [`${value} postulantes`, 'Cantidad']}
                                            contentStyle={{
                                                borderRadius: '12px',
                                                border: '1px solid rgba(13,43,133,0.1)',
                                                boxShadow: '0 4px 16px rgba(13,43,133,0.1)',
                                            }}
                                        />
                                        <Legend />
                                    </PieChart>
                                </ResponsiveContainer>
                            ) : (
                                <div className="flex h-full items-center justify-center text-muted-foreground text-sm border-dashed border rounded-xl">
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
                                        margin={{ top: 5, right: 30, left: 0, bottom: 5 }}
                                    >
                                        <defs>
                                            <linearGradient id={BAR_GRADIENT_ID} x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="0%" stopColor="#F4747A" stopOpacity={0.9} />
                                                <stop offset="100%" stopColor="#B8DFF5" stopOpacity={0.8} />
                                            </linearGradient>
                                        </defs>
                                        <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="currentColor" strokeOpacity={0.08} />
                                        <XAxis dataKey="materia" tick={{ fontSize: 11 }} />
                                        <YAxis domain={[0, 100]} tick={{ fontSize: 11 }} />
                                        <Tooltip
                                            formatter={(value) => [`${value} pts`, 'Promedio']}
                                            contentStyle={{
                                                borderRadius: '12px',
                                                border: '1px solid rgba(13,43,133,0.1)',
                                                boxShadow: '0 4px 16px rgba(13,43,133,0.1)',
                                            }}
                                        />
                                        <Bar
                                            dataKey="promedio"
                                            fill={`url(#${BAR_GRADIENT_ID})`}
                                            radius={[6, 6, 0, 0]}
                                            name="Promedio"
                                        />
                                    </BarChart>
                                </ResponsiveContainer>
                            ) : (
                                <div className="flex h-full items-center justify-center text-muted-foreground text-sm border-dashed border rounded-xl">
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
            title: 'Dashboard Administrativo',
            href: dashboard.url(),
        },
    ],
};
