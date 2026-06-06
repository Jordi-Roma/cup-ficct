import { Head, router, usePage } from '@inertiajs/react';
import { CheckCircle2, XCircle } from 'lucide-react';
import { Badge } from '@/shared/components/ui/badge';
import { Button } from '@/shared/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/shared/components/ui/card';

export default function SolicitudesPostulantesPage({ solicitudes }) {
    const { auth } = usePage().props;
    const canUpdate = auth.permissions.includes('postulantes:update');

    const confirmar = (solicitud) => {
        router.patch(`/postulantes/solicitudes/${solicitud.id_postulante}/confirmar`, undefined, {
            preserveScroll: true,
        });
    };

    const rechazar = (solicitud) => {
        router.patch(`/postulantes/solicitudes/${solicitud.id_postulante}/rechazar`, undefined, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Solicitudes pendientes" />

            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold text-foreground">Solicitudes pendientes</h1>
                    <p className="text-sm text-muted-foreground">
                        Revisa solicitudes de registro antes de generar credenciales de acceso.
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle>{solicitudes.length}</CardTitle>
                            <CardDescription>Solicitudes pendientes</CardDescription>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>{solicitudes.filter((item) => item.presento_titulo_bachiller).length}</CardTitle>
                            <CardDescription>Con título declarado</CardDescription>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>{solicitudes.filter((item) => item.presento_fotocopia_carnet).length}</CardTitle>
                            <CardDescription>Con fotocopia declarada</CardDescription>
                        </CardHeader>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Listado de solicitudes</CardTitle>
                        <CardDescription>Confirma solo solicitudes con ambos documentos presentados.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="hidden overflow-x-auto xl:block">
                            <table className="w-full min-w-[1100px] text-sm">
                                <thead className="bg-slate-100 text-left text-slate-700 dark:bg-slate-700/60 dark:text-slate-100">
                                    <tr>
                                        <th className="px-4 py-3">CI</th>
                                        <th className="px-4 py-3">Postulante</th>
                                        <th className="px-4 py-3">Correo</th>
                                        <th className="px-4 py-3">Carrera 1</th>
                                        <th className="px-4 py-3">Carrera 2</th>
                                        <th className="px-4 py-3">Turno</th>
                                        <th className="px-4 py-3">Título</th>
                                        <th className="px-4 py-3">Carnet</th>
                                        <th className="px-4 py-3">Estado</th>
                                        <th className="px-4 py-3 text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {solicitudes.map((solicitud) => (
                                        <tr key={solicitud.id_postulante}>
                                            <td className="px-4 py-3">{solicitud.ci}</td>
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{solicitud.nombre_completo}</div>
                                                <div className="text-xs text-muted-foreground">{solicitud.telefono ?? '-'}</div>
                                            </td>
                                            <td className="px-4 py-3">{solicitud.correo}</td>
                                            <td className="px-4 py-3">{solicitud.carrera_opcion1 ?? '-'}</td>
                                            <td className="px-4 py-3">{solicitud.carrera_opcion2 ?? '-'}</td>
                                            <td className="px-4 py-3">{solicitud.turno_preferido_label ?? '-'}</td>
                                            <td className="px-4 py-3"><StatusBadge value={solicitud.presento_titulo_bachiller} /></td>
                                            <td className="px-4 py-3"><StatusBadge value={solicitud.presento_fotocopia_carnet} /></td>
                                            <td className="px-4 py-3"><Badge variant="secondary">{solicitud.estado_proceso}</Badge></td>
                                            <td className="px-4 py-3">
                                                {canUpdate && (
                                                    <div className="flex justify-end gap-2">
                                                        <Button type="button" size="sm" className="bg-[#001f3f] text-white hover:bg-[#06345f]" onClick={() => confirmar(solicitud)}>
                                                            <CheckCircle2 className="size-4" />
                                                            Confirmar
                                                        </Button>
                                                        <Button type="button" variant="outline" size="sm" onClick={() => rechazar(solicitud)}>
                                                            <XCircle className="size-4" />
                                                            Rechazar
                                                        </Button>
                                                    </div>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <div className="grid gap-3 xl:hidden">
                            {solicitudes.map((solicitud) => (
                                <div key={solicitud.id_postulante} className="space-y-3 rounded-md border p-4">
                                    <div>
                                        <h3 className="font-semibold">{solicitud.nombre_completo}</h3>
                                        <p className="text-sm text-muted-foreground">{solicitud.ci} - {solicitud.correo}</p>
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        <Badge variant="outline">{solicitud.turno_preferido_label ?? 'Sin turno'}</Badge>
                                        <StatusBadge label="Titulo" value={solicitud.presento_titulo_bachiller} />
                                        <StatusBadge label="Carnet" value={solicitud.presento_fotocopia_carnet} />
                                    </div>
                                    {canUpdate && (
                                        <div className="flex flex-wrap gap-2">
                                            <Button type="button" size="sm" onClick={() => confirmar(solicitud)}>Confirmar</Button>
                                            <Button type="button" variant="outline" size="sm" onClick={() => rechazar(solicitud)}>Rechazar</Button>
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>

                        {solicitudes.length === 0 && (
                            <p className="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground">
                                No hay solicitudes pendientes.
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function StatusBadge({ value, label }) {
    return (
        <Badge variant={value ? 'default' : 'secondary'}>
            {label ? `${label}: ` : ''}{value ? 'Si' : 'No'}
        </Badge>
    );
}
