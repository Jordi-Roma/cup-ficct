import { router } from '@inertiajs/react';
import { Edit2, Power } from 'lucide-react';
import { Badge } from '@/shared/components/ui/badge';
import { Button } from '@/shared/components/ui/button';

export default function GestionesAcademicasTable({ gestiones, canUpdate, canToggle, onEdit }) {
    const toggle = (gestion) => {
        router.patch(`/academico/gestiones/${gestion.id_gestion}/toggle`, undefined, {
            preserveScroll: true,
        });
    };

    if (!gestiones.length) {
        return (
            <div className="rounded-md border p-6 text-center text-sm text-muted-foreground">
                Todavía no existen gestiones académicas.
            </div>
        );
    }

    return (
        <div className="overflow-hidden rounded-md border">
            <div className="hidden md:block">
                <table className="w-full text-sm">
                    <thead className="bg-slate-100 text-left text-slate-700 dark:bg-slate-700/60 dark:text-slate-100">
                        <tr>
                            <th className="px-4 py-3">Nombre</th>
                            <th className="px-4 py-3">Fecha inicio</th>
                            <th className="px-4 py-3">Fecha fin</th>
                            <th className="px-4 py-3">Grupos</th>
                            <th className="px-4 py-3">Postulaciones</th>
                            <th className="px-4 py-3">Estado</th>
                            <th className="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {gestiones.map((gestion) => (
                            <tr key={gestion.id_gestion}>
                                <td className="px-4 py-3 font-medium">{gestion.nombre}</td>
                                <td className="px-4 py-3">{gestion.fecha_inicio}</td>
                                <td className="px-4 py-3">{gestion.fecha_fin}</td>
                                <td className="px-4 py-3">{gestion.grupos_count}</td>
                                <td className="px-4 py-3">{gestion.postulaciones_count}</td>
                                <td className="px-4 py-3">
                                    <Badge variant={gestion.activo ? 'default' : 'secondary'}>
                                        {gestion.activo ? 'Activa' : 'Inactiva'}
                                    </Badge>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex justify-end gap-2">
                                        {canUpdate && (
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => onEdit(gestion)}
                                            >
                                                <Edit2 className="size-4" />
                                            </Button>
                                        )}
                                        {canToggle && (
                                            <Button type="button" variant="outline" size="sm" onClick={() => toggle(gestion)}>
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
                {gestiones.map((gestion) => (
                    <div key={gestion.id_gestion} className="space-y-3 rounded-md border p-4">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <h3 className="font-semibold">{gestion.nombre}</h3>
                                <p className="text-sm text-muted-foreground">
                                    {gestion.fecha_inicio} al {gestion.fecha_fin}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Grupos: {gestion.grupos_count} | Postulaciones: {gestion.postulaciones_count}
                                </p>
                            </div>
                            <Badge variant={gestion.activo ? 'default' : 'secondary'}>
                                {gestion.activo ? 'Activa' : 'Inactiva'}
                            </Badge>
                        </div>
                        <div className="flex gap-2">
                            {canUpdate && (
                                <Button type="button" variant="outline" size="sm" onClick={() => onEdit(gestion)}>
                                    Editar
                                </Button>
                            )}
                            {canToggle && (
                                <Button type="button" variant="outline" size="sm" onClick={() => toggle(gestion)}>
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
