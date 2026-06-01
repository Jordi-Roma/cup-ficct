import { router } from '@inertiajs/react';
import { Edit2, Power } from 'lucide-react';
import { Badge } from '@/shared/components/ui/badge';
import { Button } from '@/shared/components/ui/button';
export default function AsignacionesAcademicasTable({ asignaciones, canUpdate, canToggle, onEdit, }) {
    const toggle = (asignacion) => {
        router.patch(`/academico/asignaciones/${asignacion.id_asignacion}/toggle`, undefined, { preserveScroll: true });
    };
    return (<div className="overflow-hidden rounded-md border">
            <div className="hidden overflow-x-auto lg:block">
                <table className="w-full min-w-[900px] text-sm">
                    <thead className="bg-slate-50 text-left">
                        <tr>
                            <th className="px-4 py-3">Grupo</th>
                            <th className="px-4 py-3">Materia</th>
                            <th className="px-4 py-3">Docente</th>
                            <th className="px-4 py-3">Aula</th>
                            <th className="px-4 py-3">Horario</th>
                            <th className="px-4 py-3">Estado</th>
                            <th className="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {asignaciones.map((asignacion) => (<tr key={asignacion.id_asignacion}>
                                <td className="px-4 py-3">
                                    {asignacion.grupo.nombre}
                                </td>
                                <td className="px-4 py-3">
                                    {asignacion.materia.nombre}
                                </td>
                                <td className="px-4 py-3">
                                    <div className="font-medium">
                                        {asignacion.docente.nombre_completo}
                                    </div>
                                    <div className="text-xs text-muted-foreground">
                                        {asignacion.docente.correo}
                                    </div>
                                </td>
                                <td className="px-4 py-3">
                                    {asignacion.aula.nombre}
                                </td>
                                <td className="px-4 py-3">
                                    {asignacion.horario.dia}{' '}
                                    {asignacion.horario.hora_inicio}-
                                    {asignacion.horario.hora_fin}
                                </td>
                                <td className="px-4 py-3">
                                    <Badge variant={asignacion.activo
                ? 'default'
                : 'secondary'}>
                                        {asignacion.activo
                ? 'Activa'
                : 'Inactiva'}
                                    </Badge>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex justify-end gap-2">
                                        {canUpdate && (<Button type="button" variant="outline" size="sm" onClick={() => onEdit(asignacion)}>
                                                <Edit2 className="size-4"/>
                                            </Button>)}
                                        {canToggle && (<Button type="button" variant="outline" size="sm" onClick={() => toggle(asignacion)}>
                                                <Power className="size-4"/>
                                            </Button>)}
                                    </div>
                                </td>
                            </tr>))}
                    </tbody>
                </table>
            </div>

            <div className="grid gap-3 p-3 lg:hidden">
                {asignaciones.map((asignacion) => (<div key={asignacion.id_asignacion} className="space-y-3 rounded-md border p-4">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <h3 className="font-semibold">
                                    {asignacion.grupo.nombre} -{' '}
                                    {asignacion.materia.nombre}
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    {asignacion.docente.nombre_completo}
                                </p>
                            </div>
                            <Badge variant={asignacion.activo
                ? 'default'
                : 'secondary'}>
                                {asignacion.activo ? 'Activa' : 'Inactiva'}
                            </Badge>
                        </div>
                        <p className="text-sm">
                            {asignacion.aula.nombre} - {asignacion.horario.dia}{' '}
                            {asignacion.horario.hora_inicio}-
                            {asignacion.horario.hora_fin}
                        </p>
                        <div className="flex gap-2">
                            {canUpdate && (<Button type="button" variant="outline" size="sm" onClick={() => onEdit(asignacion)}>
                                    Editar
                                </Button>)}
                            {canToggle && (<Button type="button" variant="outline" size="sm" onClick={() => toggle(asignacion)}>
                                    Estado
                                </Button>)}
                        </div>
                    </div>))}
            </div>
        </div>);
}
