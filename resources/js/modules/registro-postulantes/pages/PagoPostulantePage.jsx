import { Head, router } from '@inertiajs/react';
import { CreditCard, RefreshCw } from 'lucide-react';
import { Badge } from '@/shared/components/ui/badge';
import { Button } from '@/shared/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/shared/components/ui/card';

export default function PagoPostulantePage({ postulante, postulacion, puede_pagar, monto }) {
    const pagar = () => {
        router.post('/postulante/pago/stripe', undefined, {
            preserveScroll: true,
        });
    };

    // Si hay una gestión asociada y no es la primera postulación del historial
    // podemos inferir que es una repostulación (la gestión lo indica en el label)
    const esRepostulacion = Boolean(postulacion.gestion);

    return (
        <>
            <Head title="Pago de inscripción" />

            <div className="mx-auto max-w-3xl space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold text-foreground">
                        {esRepostulacion ? 'Pago de reinscripción' : 'Pago de inscripción'}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {esRepostulacion
                            ? `Gestión ${postulacion.gestion} — Completa el pago para habilitar tu acceso a la nueva gestión.`
                            : 'Completa el pago de inscripción al CUP-FICCT mediante Stripe Checkout en modo prueba.'}
                    </p>
                </div>

                {esRepostulacion && (
                    <div className="flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800 dark:border-blue-900/50 dark:bg-blue-950/30 dark:text-blue-300">
                        <RefreshCw className="mt-0.5 size-4 shrink-0" />
                        <span>
                            Estás realizando una <strong>repostulación</strong>. Tu cuenta, contraseña y datos
                            personales se mantienen. Solo debes completar el pago de matrícula para la nueva gestión.
                        </span>
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>{postulante.nombre_completo}</CardTitle>
                        <CardDescription>CI {postulante.ci} - {postulante.correo}</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4">
                        <div className="grid gap-3 rounded-xl border p-4 sm:grid-cols-2">
                            <Info label="Monto" value={`${monto?.valor ?? '50.00'} ${monto?.moneda ?? 'USD'}`} />
                            {postulacion.gestion && (
                                <Info label="Gestión académica" value={postulacion.gestion} />
                            )}
                            <Info label="Carrera principal" value={postulacion.carrera_opcion1 ?? '-'} />
                            <Info label="Carrera secundaria" value={postulacion.carrera_opcion2 ?? '-'} />
                            <Info label="Estado de admisión" value={postulacion.estado_admision} />
                            <div>
                                <p className="text-xs text-muted-foreground">Estado del proceso</p>
                                <Badge variant="pending">{postulacion.estado_proceso}</Badge>
                            </div>
                        </div>

                        <div className="rounded-xl bg-muted p-4 text-sm text-muted-foreground">
                            Puedes usar tarjetas de prueba de Stripe durante el entorno de desarrollo.
                        </div>

                        <Button
                            type="button"
                            disabled={!puede_pagar}
                            onClick={pagar}
                            className="bg-[#0D2B85] text-white hover:bg-[#0a2270]"
                        >
                            <CreditCard className="size-4" />
                            Pagar 50 USD con Stripe
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function Info({ label, value }) {
    return (
        <div>
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className="font-medium">{value}</p>
        </div>
    );
}

