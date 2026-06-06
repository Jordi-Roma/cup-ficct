import { router } from '@inertiajs/react';
import { Edit2, Eye, Power } from 'lucide-react';
import { Badge } from '@/shared/components/ui/badge';
import { Button } from '@/shared/components/ui/button';
export default function GruposAcademicosTable({ grupos, canUpdate, canToggle, canViewPostulantes, onEdit, onViewPostulantes, }) {
    const toggle = (grupo) => {
        router.patch(`/academico/grupos/${grupo.id_grupo}/toggle`, undefined, {
            preserveScroll: true,
        });
    };
    return (<div className="overflow-hidden rounded-md border">
            <div className="hidden md:block">
                <table className="w-full text-sm">
                    <thead className="bg-slate-100 text-left text-slate-700 dark:bg-slate-700/60 dark:text-slate-100">
                        <tr>
                            <th className="px-4 py-3">Grupo</th>
                            <th className="px-4 py-3">Turno</th>
                            <th className="px-4 py-3">Capacidad</th>
                            <th className="px-4 py-3">Asignados</th>
                            <th className="px-4 py-3">Disponibles</th>
                            <th className="px-4 py-3">Estado</th>
                            <th className="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {grupos.map((grupo) => (<tr key={grupo.id_grupo}>
                                <td className="px-4 py-3 font-medium">
                                    {grupo.nombre}
                                </td>
                                <td className="px-4 py-3">
                                    {grupo.turno_label ?? 'Sin turno'}
                                </td>
                                <td className="px-4 py-3">
                                    {grupo.capacidad_maxima}
                                </td>
                                <td className="px-4 py-3">
                                    {grupo.postulantes_asignados}
                                </td>
                                <td className="px-4 py-3">
                                    {grupo.cupos_disponibles}
                                </td>
                                <td className="px-4 py-3">
                                    <Badge variant={grupo.activo
                ? 'default'
                : 'secondary'}>
                                        {grupo.activo ? 'Activo' : 'Inactivo'}
                                    </Badge>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex justify-end gap-2">
                                        {canUpdate && (<Button type="button" variant="outline" size="sm" onClick={() => onEdit(grupo)}>
                                                <Edit2 className="size-4"/>
                                            </Button>)}
                                        {canViewPostulantes && (<Button type="button" variant="outline" size="sm" onClick={() => onViewPostulantes(grupo)}>
                                                <Eye className="size-4"/>
                                            </Button>)}
                                        {canToggle && (<Button type="button" variant="outline" size="sm" onClick={() => toggle(grupo)}>
                                                <Power className="size-4"/>
                                            </Button>)}
                                    </div>
                                </td>
                            </tr>))}
                    </tbody>
                </table>
            </div>

            <div className="grid gap-3 p-3 md:hidden">
                {grupos.map((grupo) => (<div key={grupo.id_grupo} className="space-y-3 rounded-md border p-4">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <h3 className="font-semibold">
                                    {grupo.nombre}
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    {grupo.turno_label ?? 'Sin turno'} · {grupo.postulantes_asignados}/
                                    {grupo.capacidad_maxima} postulantes
                                </p>
                            </div>
                            <Badge variant={grupo.activo ? 'default' : 'secondary'}>
                                {grupo.activo ? 'Activo' : 'Inactivo'}
                            </Badge>
                        </div>
                        <div className="text-sm text-muted-foreground">
                            {grupo.cupos_disponibles} cupos disponibles
                        </div>
                        <div className="flex gap-2">
                            {canUpdate && (<Button type="button" variant="outline" size="sm" onClick={() => onEdit(grupo)}>
                                    Editar
                                </Button>)}
                            {canViewPostulantes && (<Button type="button" variant="outline" size="sm" onClick={() => onViewPostulantes(grupo)}>
                                    Ver postulantes
                                </Button>)}
                            {canToggle && (<Button type="button" variant="outline" size="sm" onClick={() => toggle(grupo)}>
                                    Estado
                                </Button>)}
                        </div>
                    </div>))}
            </div>
        </div>);
}
