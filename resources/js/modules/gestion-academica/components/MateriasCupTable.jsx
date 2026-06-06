import { router } from '@inertiajs/react';
import { Edit2, Power } from 'lucide-react';
import { Badge } from '@/shared/components/ui/badge';
import { Button } from '@/shared/components/ui/button';
export default function MateriasCupTable({ materias, canUpdate, canToggle, onEdit, }) {
    const toggle = (materia) => {
        router.patch(`/academico/materias/${materia.id_materia}/toggle`, undefined, {
            preserveScroll: true,
        });
    };
    return (<div className="overflow-hidden rounded-md border">
            <div className="hidden md:block">
                <table className="w-full text-sm">
                    <thead className="bg-slate-100 text-left text-slate-700 dark:bg-slate-700/60 dark:text-slate-100">
                        <tr>
                            <th className="px-4 py-3">Materia</th>
                            <th className="px-4 py-3">Estado</th>
                            <th className="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {materias.map((materia) => (<tr key={materia.id_materia}>
                                <td className="px-4 py-3 font-medium">
                                    {materia.nombre}
                                </td>
                                <td className="px-4 py-3">
                                    <Badge variant={materia.activo
                ? 'default'
                : 'secondary'}>
                                        {materia.activo ? 'Activo' : 'Inactivo'}
                                    </Badge>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex justify-end gap-2">
                                        {canUpdate && (<Button type="button" variant="outline" size="sm" onClick={() => onEdit(materia)}>
                                                <Edit2 className="size-4"/>
                                            </Button>)}
                                        {canToggle && (<Button type="button" variant="outline" size="sm" onClick={() => toggle(materia)}>
                                                <Power className="size-4"/>
                                            </Button>)}
                                    </div>
                                </td>
                            </tr>))}
                    </tbody>
                </table>
            </div>

            <div className="grid gap-3 p-3 md:hidden">
                {materias.map((materia) => (<div key={materia.id_materia} className="space-y-3 rounded-md border p-4">
                        <div className="flex items-start justify-between gap-3">
                            <h3 className="font-semibold">{materia.nombre}</h3>
                            <Badge variant={materia.activo ? 'default' : 'secondary'}>
                                {materia.activo ? 'Activo' : 'Inactivo'}
                            </Badge>
                        </div>
                        <div className="flex gap-2">
                            {canUpdate && (<Button type="button" variant="outline" size="sm" onClick={() => onEdit(materia)}>
                                    Editar
                                </Button>)}
                            {canToggle && (<Button type="button" variant="outline" size="sm" onClick={() => toggle(materia)}>
                                    Estado
                                </Button>)}
                        </div>
                    </div>))}
            </div>
        </div>);
}
