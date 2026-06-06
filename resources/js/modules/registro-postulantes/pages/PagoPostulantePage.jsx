import { Head, router } from '@inertiajs/react';
import { CreditCard } from 'lucide-react';
import { Badge } from '@/shared/components/ui/badge';
import { Button } from '@/shared/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/shared/components/ui/card';

export default function PagoPostulantePage({ postulante, postulacion, puede_pagar, monto }) {
    const pagar = () => {
        router.post('/postulante/pago/stripe', undefined, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Pago de inscripción" />

            <div className="mx-auto max-w-3xl space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold text-foreground">Pago de inscripción</h1>
                    <p className="text-sm text-muted-foreground">
                        Completa el pago de inscripción al CUP-FICCT mediante Stripe Checkout en modo prueba.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>{postulante.nombre_completo}</CardTitle>
                        <CardDescription>CI {postulante.ci} - {postulante.correo}</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4">
                        <div className="grid gap-3 rounded-xl border p-4 sm:grid-cols-2">
                            <Info label="Monto" value={`${monto?.valor ?? '50.00'} ${monto?.moneda ?? 'USD'}`} />
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

                        <Button type="button" disabled={!puede_pagar} onClick={pagar} className="bg-[#0D2B85] text-white hover:bg-[#0a2270]">
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
