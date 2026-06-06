import { router } from '@inertiajs/react';
import { Edit2, Power } from 'lucide-react';
import { Badge } from '@/shared/components/ui/badge';
import { Button } from '@/shared/components/ui/button';

export default function HorariosTable({ horarios, canUpdate, canToggle, onEdit }) {
    const toggle = (horario) => {
        router.patch(`/academico/horarios/${horario.id_horario}/toggle`, undefined, {
            preserveScroll: true,
        });
    };

    return (
        <div className="overflow-hidden rounded-md border">
            <div className="hidden md:block">
                <table className="w-full text-sm">
                    <thead className="bg-slate-100 text-left text-slate-700 dark:bg-slate-700/60 dark:text-slate-100">
                        <tr>
                            <th className="px-4 py-3">Turno</th>
                            <th className="px-4 py-3">Hora inicio</th>
                            <th className="px-4 py-3">Hora fin</th>
                            <th className="px-4 py-3">Asignaciones activas</th>
                            <th className="px-4 py-3">Estado</th>
                            <th className="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {horarios.map((horario) => (
                            <tr key={horario.id_horario}>
                                <td className="px-4 py-3 font-medium">{horario.turno_label}</td>
                                <td className="px-4 py-3">{horario.hora_inicio}</td>
                                <td className="px-4 py-3">{horario.hora_fin}</td>
                                <td className="px-4 py-3">{horario.asignaciones_activas}</td>
                                <td className="px-4 py-3">
                                    <Badge variant={horario.activo ? 'default' : 'secondary'}>
                                        {horario.activo ? 'Activo' : 'Inactivo'}
                                    </Badge>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex justify-end gap-2">
                                        {canUpdate && (
                                            <Button type="button" variant="outline" size="sm" onClick={() => onEdit(horario)}>
                                                <Edit2 className="size-4" />
                                            </Button>
                                        )}
                                        {canToggle && (
                                            <Button type="button" variant="outline" size="sm" onClick={() => toggle(horario)}>
                                                <Power className="size-4" />
                                            </Button>
                                        )}
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="grid gap-3 p-3 md:hidden">
                {horarios.map((horario) => (
                    <div key={horario.id_horario} className="space-y-3 rounded-md border p-4">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <h3 className="font-semibold">{horario.turno_label}</h3>
                                <p className="text-sm text-muted-foreground">
                                    {horario.hora_inicio} - {horario.hora_fin}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Asignaciones activas: {horario.asignaciones_activas}
                                </p>
                            </div>
                            <Badge variant={horario.activo ? 'default' : 'secondary'}>
                                {horario.activo ? 'Activo' : 'Inactivo'}
                            </Badge>
                        </div>
                        <div className="flex gap-2">
                            {canUpdate && (
                                <Button type="button" variant="outline" size="sm" onClick={() => onEdit(horario)}>
                                    Editar
                                </Button>
                            )}
                            {canToggle && (
                                <Button type="button" variant="outline" size="sm" onClick={() => toggle(horario)}>
                                    Estado
                                </Button>
                            )}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
