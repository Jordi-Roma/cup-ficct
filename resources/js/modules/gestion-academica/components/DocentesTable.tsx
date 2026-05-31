import { router } from '@inertiajs/react';
import { Edit2, Power } from 'lucide-react';
import { Badge } from '@/shared/components/ui/badge';
import { Button } from '@/shared/components/ui/button';
import type { Docente } from '../types/docente';

type Props = {
    docentes: Docente[];
    canUpdate: boolean;
    canToggle: boolean;
    onEdit: (docente: Docente) => void;
};

export default function DocentesTable({
    docentes,
    canUpdate,
    canToggle,
    onEdit,
}: Props) {
    const toggle = (docente: Docente) => {
        router.patch(`/academico/docentes/${docente.id_docente}/toggle`, undefined, {
            preserveScroll: true,
        });
    };

    const requirementBadge = (met: boolean, label: string) => (
        <Badge variant={met ? 'default' : 'secondary'}>{label}</Badge>
    );

    return (
        <div className="overflow-hidden rounded-md border">
            <div className="hidden overflow-x-auto xl:block">
                <table className="w-full min-w-[1000px] text-sm">
                    <thead className="bg-slate-50 text-left">
                        <tr>
                            <th className="px-4 py-3">CI</th>
                            <th className="px-4 py-3">Docente</th>
                            <th className="px-4 py-3">Teléfono</th>
                            <th className="px-4 py-3">Requisitos</th>
                            <th className="px-4 py-3">Contratado</th>
                            <th className="px-4 py-3">Activo</th>
                            <th className="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {docentes.map((docente) => (
                            <tr key={docente.id_docente}>
                                <td className="px-4 py-3">{docente.ci}</td>
                                <td className="px-4 py-3">
                                    <div className="font-medium">
                                        {docente.nombre_completo}
                                    </div>
                                    <div className="text-xs text-muted-foreground">
                                        {docente.username} · {docente.correo}
                                    </div>
                                </td>
                                <td className="px-4 py-3">
                                    {docente.telefono ?? '-'}
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex flex-wrap gap-1">
                                        {requirementBadge(
                                            docente.profesional_area,
                                            'Área',
                                        )}
                                        {requirementBadge(
                                            docente.maestria,
                                            'Maestría',
                                        )}
                                        {requirementBadge(
                                            docente.diplomado_educacion_superior,
                                            'Diplomado',
                                        )}
                                    </div>
                                </td>
                                <td className="px-4 py-3">
                                    <Badge
                                        variant={
                                            docente.contratado
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {docente.contratado ? 'Sí' : 'No'}
                                    </Badge>
                                </td>
                                <td className="px-4 py-3">
                                    <Badge
                                        variant={
                                            docente.activo
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {docente.activo ? 'Activo' : 'Inactivo'}
                                    </Badge>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex justify-end gap-2">
                                        {canUpdate && (
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => onEdit(docente)}
                                            >
                                                <Edit2 className="size-4" />
                                            </Button>
                                        )}
                                        {canToggle && (
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => toggle(docente)}
                                            >
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

            <div className="grid gap-3 p-3 xl:hidden">
                {docentes.map((docente) => (
                    <div
                        key={docente.id_docente}
                        className="space-y-3 rounded-md border p-4"
                    >
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <h3 className="font-semibold">
                                    {docente.nombre_completo}
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    {docente.ci} · {docente.correo}
                                </p>
                            </div>
                            <Badge
                                variant={
                                    docente.contratado
                                        ? 'default'
                                        : 'secondary'
                                }
                            >
                                {docente.contratado ? 'Contratado' : 'No contratado'}
                            </Badge>
                        </div>
                        <div className="flex flex-wrap gap-1">
                            {requirementBadge(docente.profesional_area, 'Área')}
                            {requirementBadge(docente.maestria, 'Maestría')}
                            {requirementBadge(
                                docente.diplomado_educacion_superior,
                                'Diplomado',
                            )}
                        </div>
                        <div className="flex gap-2">
                            {canUpdate && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => onEdit(docente)}
                                >
                                    Editar
                                </Button>
                            )}
                            {canToggle && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => toggle(docente)}
                                >
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
