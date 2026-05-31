import { Badge } from '@/shared/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/shared/components/ui/dialog';
import type { GrupoAcademico } from '../types/grupo-academico';

type Props = {
    grupo: GrupoAcademico | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export default function GrupoPostulantesDialog({
    grupo,
    open,
    onOpenChange,
}: Props) {
    const postulantes = grupo?.postulantes ?? [];

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-5xl">
                <DialogHeader>
                    <DialogTitle>
                        Postulantes de {grupo?.nombre ?? 'grupo'}
                    </DialogTitle>
                    <DialogDescription>
                        {postulantes.length} asignados.{' '}
                        {grupo?.cupos_disponibles ?? 0} cupos disponibles.
                    </DialogDescription>
                </DialogHeader>

                {postulantes.length === 0 ? (
                    <div className="rounded-md border p-6 text-sm text-muted-foreground">
                        Este grupo todavia no tiene postulantes asignados.
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-md border">
                        <div className="hidden overflow-x-auto xl:block">
                            <table className="w-full min-w-[1000px] text-sm">
                                <thead className="bg-slate-50 text-left">
                                    <tr>
                                        <th className="px-4 py-3">CI</th>
                                        <th className="px-4 py-3">Postulante</th>
                                        <th className="px-4 py-3">Ciudad</th>
                                        <th className="px-4 py-3">Colegio</th>
                                        <th className="px-4 py-3">Documentacion</th>
                                        <th className="px-4 py-3">Carrera opcion 1</th>
                                        <th className="px-4 py-3">Estado</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {postulantes.map((postulante) => (
                                        <tr key={postulante.id_postulante}>
                                            <td className="px-4 py-3">
                                                {postulante.ci}
                                            </td>
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
                                                {postulante.colegio_procedencia ??
                                                    '-'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant={
                                                        postulante.documentacion_completa
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {postulante.documentacion_completa
                                                        ? 'Completa'
                                                        : 'Pendiente'}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                {postulante.carrera_opcion1 ?? '-'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {postulante.estado_admision}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <div className="grid gap-3 p-3 xl:hidden">
                            {postulantes.map((postulante) => (
                                <div
                                    key={postulante.id_postulante}
                                    className="space-y-2 rounded-md border p-4"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <h3 className="font-semibold">
                                                {postulante.nombre_completo}
                                            </h3>
                                            <p className="text-sm text-muted-foreground">
                                                {postulante.ci} ·{' '}
                                                {postulante.correo}
                                            </p>
                                        </div>
                                        <Badge
                                            variant={
                                                postulante.documentacion_completa
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {postulante.documentacion_completa
                                                ? 'Completa'
                                                : 'Pendiente'}
                                        </Badge>
                                    </div>
                                    <p className="text-sm">
                                        {postulante.ciudad ?? '-'} ·{' '}
                                        {postulante.colegio_procedencia ?? '-'}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Carrera: {postulante.carrera_opcion1 ?? '-'}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Estado: {postulante.estado_admision}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
