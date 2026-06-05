import { Head } from '@inertiajs/react';
import { BookOpen, CalendarCheck, Layers } from 'lucide-react';
import { Badge } from '@/shared/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/shared/components/ui/card';

function SummaryCard({ title, value, icon: Icon }) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardDescription>{title}</CardDescription>
                <Icon className="size-5 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <CardTitle className="text-3xl">{value}</CardTitle>
            </CardContent>
        </Card>
    );
}

function formatHorario(horario) {
    if (!horario?.hora_inicio || !horario?.hora_fin) {
        return '-';
    }

    return `${horario.hora_inicio} - ${horario.hora_fin}`;
}

export default function MisAsignacionesPage({ asignaciones = [], resumen = {} }) {
    return (
        <>
            <Head title="Mis asignaciones" />

            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold text-[#001f3f]">
                        Mis asignaciones
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Consulta tus grupos, materias, aulas y horarios asignados.
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <SummaryCard
                        title="Asignaciones activas"
                        value={resumen.total_asignaciones ?? 0}
                        icon={CalendarCheck}
                    />
                    <SummaryCard
                        title="Grupos asignados"
                        value={resumen.total_grupos ?? 0}
                        icon={Layers}
                    />
                    <SummaryCard
                        title="Materias asignadas"
                        value={resumen.total_materias ?? 0}
                        icon={BookOpen}
                    />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Carga académica</CardTitle>
                        <CardDescription>
                            Asignaciones activas vinculadas a tu perfil docente.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {asignaciones.length === 0 ? (
                            <div className="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground">
                                No tienes asignaciones académicas activas.
                            </div>
                        ) : (
                            <div className="overflow-x-auto rounded-md border">
                                <table className="w-full min-w-[760px] text-sm">
                                    <thead className="bg-muted/60 text-left">
                                        <tr>
                                            <th className="px-4 py-3 font-medium">Gestión</th>
                                            <th className="px-4 py-3 font-medium">Grupo</th>
                                            <th className="px-4 py-3 font-medium">Materia</th>
                                            <th className="px-4 py-3 font-medium">Aula</th>
                                            <th className="px-4 py-3 font-medium">Día</th>
                                            <th className="px-4 py-3 font-medium">Horario</th>
                                            <th className="px-4 py-3 font-medium">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {asignaciones.map((asignacion) => (
                                            <tr
                                                key={asignacion.id_asignacion}
                                                className="border-t"
                                            >
                                                <td className="px-4 py-3">
                                                    {asignacion.gestion?.nombre ?? '-'}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {asignacion.grupo?.nombre ?? '-'}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {asignacion.materia?.nombre ?? '-'}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {asignacion.aula?.nombre ?? '-'}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {asignacion.horario?.dia ?? '-'}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {formatHorario(asignacion.horario)}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge
                                                        variant={
                                                            asignacion.activo
                                                                ? 'default'
                                                                : 'secondary'
                                                        }
                                                    >
                                                        {asignacion.activo
                                                            ? 'Activa'
                                                            : 'Inactiva'}
                                                    </Badge>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

MisAsignacionesPage.layout = {
    breadcrumbs: [
        {
            title: 'Mis asignaciones',
            href: '/examenes/mis-asignaciones',
        },
    ],
};
