import { Form, Head } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, History } from 'lucide-react';
import { Badge } from '@/shared/components/ui/badge';
import { Button } from '@/shared/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/shared/components/ui/card';
import { Label } from '@/shared/components/ui/label';
import InputError from '@/shared/components/input-error';
import { Spinner } from '@/shared/components/ui/spinner';

const ESTADO_LABEL = {
    NO_ADMITIDO: 'No admitido',
    ADMITIDO: 'Admitido',
    PENDIENTE: 'Pendiente',
};

const PROCESO_LABEL = {
    HABILITADO_CUP: 'Habilitado CUP',
    VALIDADO_PENDIENTE_PAGO: 'Pendiente de pago',
    PENDIENTE_VALIDACION: 'Pendiente validación',
    RECHAZADO: 'Rechazado',
};

const TURNOS = [
    { value: 'MANANA', label: 'Mañana' },
    { value: 'TARDE', label: 'Tarde' },
    { value: 'NOCHE', label: 'Noche' },
];

export default function RepostulacionPage({ postulante, gestion_activa, carreras, historial }) {
    return (
        <>
            <Head title="Repostulación" />

            <style>{`
                @keyframes fadeUp {
                    from { opacity: 0; transform: translateY(16px); }
                    to   { opacity: 1; transform: translateY(0); }
                }
                .repost-card { animation: fadeUp 0.45s ease-out both; }
                .repost-card:nth-child(2) { animation-delay: 0.1s; }
                .repost-card:nth-child(3) { animation-delay: 0.2s; }
            `}</style>

            <div className="mx-auto max-w-2xl space-y-6 p-4 md:p-8">

                {/* Header */}
                <div className="space-y-1">
                    <h1 className="text-2xl font-bold tracking-tight text-foreground">
                        Nueva postulación
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Gestión activa: <span className="font-semibold text-foreground">{gestion_activa.nombre}</span>
                    </p>
                </div>

                {/* Info del postulante */}
                <Card className="repost-card border-blue-200 bg-blue-50 dark:border-blue-900/50 dark:bg-blue-950/30">
                    <CardHeader className="pb-3">
                        <CardTitle className="flex items-center gap-2 text-base text-blue-800 dark:text-blue-300">
                            <CheckCircle2 className="size-4 shrink-0" />
                            Tu documentación ya fue validada
                        </CardTitle>
                        <CardDescription className="text-blue-700 dark:text-blue-400">
                            {postulante.nombre_completo} · CI {postulante.ci} · {postulante.correo}
                        </CardDescription>
                    </CardHeader>
                </Card>

                {/* Historial breve */}
                {historial.length > 0 && (
                    <Card className="repost-card">
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-2 text-sm font-medium">
                                <History className="size-4 text-muted-foreground" />
                                Historial de postulaciones anteriores
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                {historial.map((item, i) => (
                                    <div
                                        key={i}
                                        className="flex items-center justify-between rounded-lg border px-3 py-2 text-sm"
                                    >
                                        <span className="font-medium text-foreground">{item.gestion ?? '—'}</span>
                                        <div className="flex items-center gap-2">
                                            <Badge variant="outline" className="text-xs">
                                                {PROCESO_LABEL[item.estado_proceso] ?? item.estado_proceso}
                                            </Badge>
                                            <Badge
                                                variant={item.estado_admision === 'ADMITIDO' ? 'success' : 'destructive'}
                                                className="text-xs"
                                            >
                                                {ESTADO_LABEL[item.estado_admision] ?? item.estado_admision}
                                            </Badge>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Formulario de repostulación */}
                <Card className="repost-card">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <AlertTriangle className="size-4 text-amber-500" />
                            Confirma tu nueva postulación
                        </CardTitle>
                        <CardDescription>
                            Al confirmar, serás redirigido al pago de matrícula ($50 USD).
                            Tu cuenta y contraseña no cambian.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            method="post"
                            action="/postulante/repostulacion"
                            className="space-y-5"
                        >
                            {({ processing, errors }) => (
                                <>
                                    {/* Carrera opción 1 */}
                                    <div className="grid gap-2">
                                        <Label htmlFor="id_carrera_opcion1">Carrera principal</Label>
                                        <select
                                            id="id_carrera_opcion1"
                                            name="id_carrera_opcion1"
                                            required
                                            className="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                        >
                                            <option value="">Seleccione una carrera</option>
                                            {carreras.map((c) => (
                                                <option key={c.id} value={c.id}>{c.nombre}</option>
                                            ))}
                                        </select>
                                        <InputError message={errors.id_carrera_opcion1} />
                                    </div>

                                    {/* Carrera opción 2 */}
                                    <div className="grid gap-2">
                                        <Label htmlFor="id_carrera_opcion2">
                                            Carrera secundaria{' '}
                                            <span className="text-xs text-muted-foreground">(opcional)</span>
                                        </Label>
                                        <select
                                            id="id_carrera_opcion2"
                                            name="id_carrera_opcion2"
                                            className="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                        >
                                            <option value="">Sin segunda opción</option>
                                            {carreras.map((c) => (
                                                <option key={c.id} value={c.id}>{c.nombre}</option>
                                            ))}
                                        </select>
                                        <InputError message={errors.id_carrera_opcion2} />
                                    </div>

                                    {/* Turno */}
                                    <div className="grid gap-2">
                                        <Label htmlFor="turno_preferido">Turno preferido</Label>
                                        <select
                                            id="turno_preferido"
                                            name="turno_preferido"
                                            required
                                            className="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                        >
                                            <option value="">Seleccione un turno</option>
                                            {TURNOS.map((t) => (
                                                <option key={t.value} value={t.value}>{t.label}</option>
                                            ))}
                                        </select>
                                        <InputError message={errors.turno_preferido} />
                                    </div>

                                    {/* Aviso de pago */}
                                    <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300">
                                        Al confirmar se creará tu postulación para la gestión{' '}
                                        <strong>{gestion_activa.nombre}</strong> y serás redirigido al
                                        pago de matrícula mediante Stripe.
                                    </div>

                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="w-full"
                                        data-test="confirmar-repostulacion-button"
                                    >
                                        {processing && <Spinner />}
                                        Confirmar repostulación y pagar matrícula
                                    </Button>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
