@php
    $fechaPago = optional($pago->fecha_confirmacion ?? $pago->fecha_inicio)->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i');
    $monto = number_format((float) $pago->monto, 2);
    $moneda = $pago->moneda ?: 'USD';
    $nombreCompleto = trim($user->nombre.' '.$user->apellido);
    $gestion = $postulacion->gestion?->nombre ?? 'No asignada';
    $carreraOpcion1 = $postulacion->carreraOpcion1?->nombre ?? 'No asignada';
    $carreraOpcion2 = $postulacion->carreraOpcion2?->nombre ?? 'Sin segunda opcion';
    $stripeSession = $session?->id ?? $pago->codigo_autorizacion;
    $stripeTransaction = $session?->payment_intent ?? $pago->numero_transaccion;
    $paymentMethod = is_array($session?->payment_method_types ?? null)
        ? strtoupper(implode(', ', $session->payment_method_types))
        : 'STRIPE';
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Recibo de pago CUP-FICCT</title>
</head>
<body style="margin:0; padding:0; background:#eef3f8; font-family:Arial, Helvetica, sans-serif; color:#0b1f3a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef3f8; padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="640" cellspacing="0" cellpadding="0" style="width:100%; max-width:640px; background:#ffffff; border-radius:14px; overflow:hidden; border:1px solid #d9e2ec;">
                    <tr>
                        <td style="background:#062044; padding:28px 34px; color:#ffffff;">
                            <div style="font-size:14px; letter-spacing:1.8px; text-transform:uppercase; opacity:.84;">CUP-FICCT</div>
                            <div style="font-size:26px; font-weight:700; margin-top:8px;">Recibo de pago de inscripcion</div>
                            <div style="font-size:14px; margin-top:6px; opacity:.86;">Curso Preuniversitario</div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:30px 34px 14px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="padding-bottom:18px;">
                                        <div style="font-size:12px; color:#607089; text-transform:uppercase; font-weight:700;">Recibo</div>
                                        <div style="font-size:20px; font-weight:700; color:#0b1f3a;">{{ $receiptNumber }}</div>
                                    </td>
                                    <td align="right" style="padding-bottom:18px;">
                                        <span style="display:inline-block; background:#dcfce7; color:#166534; font-size:12px; font-weight:700; padding:7px 12px; border-radius:999px;">
                                            {{ $pago->estado_pago }}
                                        </span>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse; margin-bottom:24px;">
                                <tr>
                                    <td style="width:33.33%; padding:12px; background:#f8fafc; border:1px solid #e5eaf0;">
                                        <div style="font-size:11px; color:#607089; text-transform:uppercase; font-weight:700;">Monto pagado</div>
                                        <div style="font-size:16px; font-weight:700; margin-top:5px;">{{ $monto }} {{ $moneda }}</div>
                                    </td>
                                    <td style="width:33.33%; padding:12px; background:#f8fafc; border:1px solid #e5eaf0;">
                                        <div style="font-size:11px; color:#607089; text-transform:uppercase; font-weight:700;">Fecha de pago</div>
                                        <div style="font-size:14px; font-weight:700; margin-top:5px;">{{ $fechaPago }}</div>
                                    </td>
                                    <td style="width:33.33%; padding:12px; background:#f8fafc; border:1px solid #e5eaf0;">
                                        <div style="font-size:11px; color:#607089; text-transform:uppercase; font-weight:700;">Metodo</div>
                                        <div style="font-size:14px; font-weight:700; margin-top:5px;">{{ $paymentMethod }}</div>
                                    </td>
                                </tr>
                            </table>

                            <div style="font-size:16px; font-weight:700; margin-bottom:10px;">Datos del postulante</div>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse; margin-bottom:24px;">
                                <tr>
                                    <td style="padding:8px 0; color:#607089;">Nombre completo</td>
                                    <td align="right" style="padding:8px 0; font-weight:700;">{{ $nombreCompleto }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0; color:#607089; border-top:1px solid #edf2f7;">CI</td>
                                    <td align="right" style="padding:8px 0; border-top:1px solid #edf2f7;">{{ $user->ci }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0; color:#607089; border-top:1px solid #edf2f7;">Usuario</td>
                                    <td align="right" style="padding:8px 0; border-top:1px solid #edf2f7;">{{ $user->username }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0; color:#607089; border-top:1px solid #edf2f7;">Correo registrado</td>
                                    <td align="right" style="padding:8px 0; border-top:1px solid #edf2f7;">{{ $user->correo }}</td>
                                </tr>
                            </table>

                            <div style="font-size:16px; font-weight:700; margin-bottom:10px;">Datos academicos</div>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse; margin-bottom:24px;">
                                <tr>
                                    <td style="padding:8px 0; color:#607089;">Gestion academica</td>
                                    <td align="right" style="padding:8px 0; font-weight:700;">{{ $gestion }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0; color:#607089; border-top:1px solid #edf2f7;">Carrera opcion 1</td>
                                    <td align="right" style="padding:8px 0; border-top:1px solid #edf2f7;">{{ $carreraOpcion1 }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0; color:#607089; border-top:1px solid #edf2f7;">Carrera opcion 2</td>
                                    <td align="right" style="padding:8px 0; border-top:1px solid #edf2f7;">{{ $carreraOpcion2 }}</td>
                                </tr>
                            </table>

                            <div style="font-size:16px; font-weight:700; margin-bottom:10px;">Resumen</div>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse; background:#f8fafc; border:1px solid #e5eaf0; border-radius:10px; overflow:hidden; margin-bottom:24px;">
                                <tr>
                                    <td style="padding:14px 16px;">Inscripcion CUP-FICCT x 1</td>
                                    <td align="right" style="padding:14px 16px;">{{ $monto }} {{ $moneda }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 16px; border-top:1px solid #e5eaf0; font-weight:700;">Total pagado</td>
                                    <td align="right" style="padding:14px 16px; border-top:1px solid #e5eaf0; font-weight:700;">{{ $monto }} {{ $moneda }}</td>
                                </tr>
                            </table>

                            <div style="font-size:16px; font-weight:700; margin-bottom:10px;">Datos Stripe</div>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse; margin-bottom:24px; font-size:13px;">
                                <tr>
                                    <td style="padding:8px 0; color:#607089;">Transaccion</td>
                                    <td align="right" style="padding:8px 0; word-break:break-all;">{{ $stripeTransaction }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0; color:#607089; border-top:1px solid #edf2f7;">Sesion Stripe</td>
                                    <td align="right" style="padding:8px 0; border-top:1px solid #edf2f7; word-break:break-all;">{{ $stripeSession }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0; color:#607089; border-top:1px solid #edf2f7;">Pasarela</td>
                                    <td align="right" style="padding:8px 0; border-top:1px solid #edf2f7;">{{ $pago->pasarela }}</td>
                                </tr>
                            </table>

                            <div style="border-top:1px solid #e5eaf0; padding-top:18px; color:#607089; font-size:13px; line-height:1.6;">
                                Este comprobante fue generado automaticamente por el sistema CUP-FICCT.
                                No constituye factura fiscal oficial.
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
