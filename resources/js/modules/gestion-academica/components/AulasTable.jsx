import { router } from '@inertiajs/react';
import { Edit2, Power } from 'lucide-react';
import { Badge } from '@/shared/components/ui/badge';
import { Button } from '@/shared/components/ui/button';

export default function AulasTable({ aulas, canUpdate, canToggle, onEdit }) {
    const toggle = (aula) => {
        router.patch(`/academico/aulas/${aula.id_aula}/toggle`, undefined, {
            preserveScroll: true,
        });
    };

    return (
        <div className="overflow-hidden rounded-md border">
            <div className="hidden md:block">
                <table className="w-full text-sm">
                    <thead className="bg-slate-50 text-left">
                        <tr>
                            <th className="px-4 py-3">Nombre</th>
                            <th className="px-4 py-3">Capacidad</th>
                            <th className="px-4 py-3">Asignaciones activas</th>
                            <th className="px-4 py-3">Estado</th>
                            <th className="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {aulas.map((aula) => (
                            <tr key={aula.id_aula}>
                                <td className="px-4 py-3 font-medium">{aula.nombre}</td>
                                <td className="px-4 py-3">{aula.capacidad}</td>
                                <td className="px-4 py-3">{aula.asignaciones_activas}</td>
                                <td className="px-4 py-3">
                                    <Badge variant={aula.activo ? 'default' : 'secondary'}>
                                        {aula.activo ? 'Activo' : 'Inactivo'}
                                    </Badge>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex justify-end gap-2">
                                        {canUpdate && (
                                            <Button type="button" variant="outline" size="sm" onClick={() => onEdit(aula)}>
                                                <Edit2 className="size-4" />
                                            </Button>
                                        )}
                                        {canToggle && (
                                            <Button type="button" variant="outline" size="sm" onClick={() => toggle(aula)}>
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
                {aulas.map((aula) => (
                    <div key={aula.id_aula} className="space-y-3 rounded-md border p-4">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <h3 className="font-semibold">{aula.nombre}</h3>
                                <p className="text-sm text-muted-foreground">Capacidad: {aula.capacidad}</p>
                                <p className="text-sm text-muted-foreground">
                                    Asignaciones activas: {aula.asignaciones_activas}
                                </p>
                            </div>
                            <Badge variant={aula.activo ? 'default' : 'secondary'}>
                                {aula.activo ? 'Activo' : 'Inactivo'}
                            </Badge>
                        </div>
                        <div className="flex gap-2">
                            {canUpdate && (
                                <Button type="button" variant="outline" size="sm" onClick={() => onEdit(aula)}>
                                    Editar
                                </Button>
                            )}
                            {canToggle && (
                                <Button type="button" variant="outline" size="sm" onClick={() => toggle(aula)}>
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
