import { router } from '@inertiajs/react';
import { Edit2, Eye, Power } from 'lucide-react';
import { Badge } from '@/shared/components/ui/badge';
import { Button } from '@/shared/components/ui/button';
export default function PostulantesTable({ postulantes, canUpdate, onView, onEdit, }) {
    const toggle = (postulante) => {
        router.patch(`/postulantes/${postulante.id_postulante}/toggle`, undefined, {
            preserveScroll: true,
        });
    };
    return (<div className="overflow-hidden rounded-md border">
            <div className="hidden overflow-x-auto xl:block">
                <table className="w-full min-w-[1100px] text-sm">
                    <thead className="bg-slate-50 text-left">
                        <tr>
                            <th className="px-4 py-3">CI</th>
                            <th className="px-4 py-3">Postulante</th>
                            <th className="px-4 py-3">Ciudad</th>
                            <th className="px-4 py-3">Colegio</th>
                            <th className="px-4 py-3">Documentación</th>
                            <th className="px-4 py-3">Carrera opción 1</th>
                            <th className="px-4 py-3">Turno</th>
                            <th className="px-4 py-3">Estado</th>
                            <th className="px-4 py-3">Grupo</th>
                            <th className="px-4 py-3">Usuario</th>
                            <th className="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {postulantes.map((postulante) => (<tr key={postulante.id_postulante}>
                                <td className="px-4 py-3">{postulante.ci}</td>
                                <td className="px-4 py-3">
                                    <div className="font-medium">
                                        {postulante.nombre_completo}
                                    </div>
                                    <div className="text-xs text-muted-foreground">
                                        {postulante.correo}
                                    </div>
                                </td>
                                <td className="px-4 py-3">
                                    {postulante.ciudad ?? '-'}
                                </td>
                                <td className="px-4 py-3">
                                    {postulante.colegio_procedencia ?? '-'}
                                </td>
                                <td className="px-4 py-3">
                                    <Badge variant={postulante.documentacion_completa
                ? 'default'
                : 'secondary'}>
                                        {postulante.documentacion_completa
                ? 'Completa'
                : 'Pendiente'}
                                    </Badge>
                                </td>
                                <td className="px-4 py-3">
                                    {postulante.postulacion?.carrera_opcion1
                ?.nombre ?? '-'}
                                </td>
                                <td className="px-4 py-3">
                                    {postulante.postulacion?.turno_preferido_label ?? '-'}
                                </td>
                                <td className="px-4 py-3">
                                    {postulante.postulacion?.estado_admision ??
                '-'}
                                </td>
                                <td className="px-4 py-3">
                                    {postulante.postulacion?.grupo?.nombre ?? '-'}
                                </td>
                                <td className="px-4 py-3">
                                    <Badge variant={postulante.activo
                ? 'default'
                : 'secondary'}>
                                        {postulante.activo ? 'Activo' : 'Inactivo'}
                                    </Badge>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex justify-end gap-2">
                                        <Button type="button" variant="outline" size="sm" onClick={() => onView(postulante)}>
                                            <Eye className="size-4"/>
                                        </Button>
                                        {canUpdate && (<>
                                                <Button type="button" variant="outline" size="sm" onClick={() => onEdit(postulante)}>
                                                    <Edit2 className="size-4"/>
                                                </Button>
                                                <Button type="button" variant="outline" size="sm" onClick={() => toggle(postulante)}>
                                                    <Power className="size-4"/>
                                                </Button>
                                            </>)}
                                    </div>
                                </td>
                            </tr>))}
                    </tbody>
                </table>
            </div>

            <div className="grid gap-3 p-3 xl:hidden">
                {postulantes.map((postulante) => (<div key={postulante.id_postulante} className="space-y-3 rounded-md border p-4">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <h3 className="font-semibold">
                                    {postulante.nombre_completo}
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    {postulante.ci} · {postulante.correo}
                                </p>
                            </div>
                            <Badge variant={postulante.documentacion_completa
                ? 'default'
                : 'secondary'}>
                                {postulante.documentacion_completa
                ? 'Completa'
                : 'Pendiente'}
                            </Badge>
                        </div>
                        <div className="text-sm text-muted-foreground">
                            {postulante.postulacion?.carrera_opcion1?.nombre ??
                'Sin carrera'}{' '}
                            · {postulante.postulacion?.turno_preferido_label ?? 'Sin turno'}{' '}
                            · {postulante.postulacion?.grupo?.nombre ?? 'Sin grupo'}
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button type="button" variant="outline" size="sm" onClick={() => onView(postulante)}>
                                Ver
                            </Button>
                            {canUpdate && (<>
                                    <Button type="button" variant="outline" size="sm" onClick={() => onEdit(postulante)}>
                                        Editar
                                    </Button>
                                    <Button type="button" variant="outline" size="sm" onClick={() => toggle(postulante)}>
                                        Estado
                                    </Button>
                                </>)}
                        </div>
                    </div>))}
            </div>
        </div>);
}
