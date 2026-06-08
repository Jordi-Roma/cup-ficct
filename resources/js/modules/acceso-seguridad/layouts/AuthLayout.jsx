import { Link } from '@inertiajs/react';
import { CheckSquare, CreditCard, FileText, BarChart3 } from 'lucide-react';
import { home } from '@/routes';
import AppLogoIcon from '@/shared/components/app-logo-icon';

const steps = [
    { icon: FileText, label: 'Postula tu solicitud' },
    { icon: CheckSquare, label: 'Completa requisitos' },
    { icon: CreditCard, label: 'Pago de inscripción' },
    { icon: BarChart3, label: 'Sigue tu proceso' },
];

export default function AuthLayout({ title = '', description = '', children }) {
    const isRegister = title.toLowerCase().includes('registro');

    return (
        <>
            <style>{`
                @keyframes authFloatOrb {
                    0%, 100% { transform: translateY(0px) scale(1); }
                    50% { transform: translateY(-18px) scale(1.04); }
                }
                @keyframes authSlideIn {
                    from { opacity: 0; transform: translateX(-24px); }
                    to { opacity: 1; transform: translateX(0); }
                }
                @keyframes authFadeUp {
                    from { opacity: 0; transform: translateY(20px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                @keyframes authPulse {
                    0%, 100% { opacity: 0.3; }
                    50% { opacity: 0.7; }
                }
                @keyframes authSpinSlow {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
                .auth-panel-left { animation: authSlideIn 0.6s ease-out both; }
                .auth-panel-right { animation: authFadeUp 0.6s ease-out 0.15s both; }
                .auth-orb-1 { animation: authFloatOrb 7s ease-in-out infinite; }
                .auth-orb-2 { animation: authFloatOrb 9s ease-in-out infinite; animation-delay: 2.5s; }
                .auth-step-item {
                    transition: transform 0.3s ease, background 0.3s ease;
                }
                .auth-step-item:hover {
                    transform: translateX(6px);
                    background: rgba(255,255,255,0.12) !important;
                }
                .auth-logo-ring {
                    animation: authSpinSlow 20s linear infinite;
                }
                .auth-bg-grid {
                    background-image: linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px),
                                      linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px);
                    background-size: 48px 48px;
                }
                .auth-form-field { animation: authFadeUp 0.5s ease-out both; }
                .auth-form-field:nth-child(1) { animation-delay: 0.2s; }
                .auth-form-field:nth-child(2) { animation-delay: 0.3s; }
                .auth-form-field:nth-child(3) { animation-delay: 0.4s; }
                .auth-form-field:nth-child(4) { animation-delay: 0.5s; }
            `}</style>

            <main className="grid min-h-svh lg:grid-cols-[1fr_1fr]">

                {/* ─── LEFT PANEL ─── */}
                <section
                    className="auth-panel-left relative hidden overflow-hidden lg:flex lg:flex-col"
                    style={{
                        background: 'linear-gradient(145deg, #1a0c14 0%, #200e1a 35%, #18081a 65%, #140a10 100%)',
                    }}
                >
                    {/* Grid texture */}
                    <div className="auth-bg-grid absolute inset-0" />

                    {/* Glow orbs */}
                    <div
                        className="auth-orb-1 pointer-events-none absolute -top-20 -left-20 h-72 w-72 rounded-full"
                        style={{ background: 'radial-gradient(circle, rgba(180,30,90,0.55) 0%, transparent 70%)' }}
                    />
                    <div
                        className="auth-orb-2 pointer-events-none absolute bottom-10 right-0 h-64 w-64 rounded-full"
                        style={{ background: 'radial-gradient(circle, rgba(120,20,80,0.5) 0%, transparent 70%)' }}
                    />
                    <div
                        className="pointer-events-none absolute top-1/2 left-1/2 h-48 w-48 -translate-x-1/2 -translate-y-1/2 rounded-full"
                            style={{
                                background: 'radial-gradient(circle, rgba(200,40,100,0.22) 0%, transparent 70%)',
                                animation: 'authPulse 5s ease-in-out infinite',
                            }}
                    />

                    {/* Content */}
                    <div className="relative z-10 flex h-full flex-col justify-between p-10 xl:p-14">

                        {/* Logo */}
                        <Link href={home()} className="flex w-fit items-center gap-3 text-base font-semibold text-white">
                            <span
                                className="flex size-11 items-center justify-center rounded-xl ring-1 ring-white/20"
                                style={{ background: 'rgba(255,255,255,0.1)', backdropFilter: 'blur(10px)' }}
                            >
                                <AppLogoIcon className="size-6 fill-current text-white" />
                            </span>
                            <span className="tracking-wide">CUP-FICCT</span>
                        </Link>

                        {/* Central content */}
                        <div className="max-w-sm space-y-8">

                            {/* Decorative ring around icon */}
                            <div className="relative flex h-24 w-24 items-center justify-center">
                                <div
                                    className="auth-logo-ring absolute inset-0 rounded-full border border-dashed"
                                    style={{ borderColor: 'rgba(200,80,160,0.4)' }}
                                />
                                <div
                                    className="absolute inset-3 rounded-full border"
                                    style={{ borderColor: 'rgba(255,255,255,0.1)', background: 'rgba(255,255,255,0.06)' }}
                                />
                                <AppLogoIcon className="relative z-10 size-10 fill-current text-white/80" />
                            </div>

                            <div className="space-y-3">
                                <p className="text-xs font-semibold tracking-widest text-pink-300 uppercase">
                                    Admisión universitaria
                                </p>
                                <h2 className="text-3xl font-bold leading-tight text-white xl:text-4xl">
                                    Sistema de ingreso al Curso Preuniversitario
                                </h2>
                                <p className="text-sm leading-6 text-slate-300">
                                    Gestiona tu postulación, seguimiento académico y acceso al proceso de admisión de la FICCT.
                                </p>
                            </div>

                            {/* Steps list */}
                            <div className="space-y-2">
                                {steps.map((step, i) => (
                                    <div
                                        key={i}
                                        className="auth-step-item flex items-center gap-3 rounded-xl px-3 py-2.5"
                                        style={{ background: 'rgba(255,255,255,0.06)' }}
                                    >
                                        <span
                                            className="flex size-8 shrink-0 items-center justify-center rounded-lg text-xs font-bold text-white"
                                            style={{ background: 'rgba(200,50,120,0.5)', border: '1px solid rgba(255,100,160,0.3)' }}
                                        >
                                            0{i + 1}
                                        </span>
                                        <step.icon className="size-4 shrink-0 text-pink-300" />
                                        <span className="text-sm text-slate-200">{step.label}</span>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Footer */}
                        <p className="text-xs text-slate-500">
                            © 2026 CUP-FICCT · Facultad de Ingeniería en Ciencias de la Computación y Telecomunicaciones
                        </p>
                    </div>
                </section>

                {/* ─── RIGHT PANEL — form ─── */}
                <section className="auth-panel-right flex min-h-svh items-center justify-center bg-background px-6 py-10 sm:px-8">
                    <div className={`w-full space-y-8 ${isRegister ? 'max-w-2xl' : 'max-w-md'}`}>

                        {/* Mobile logo */}
                        <div className="flex flex-col items-center gap-4 text-center lg:items-start lg:text-left">
                            <Link href={home()} className="flex items-center gap-3 font-semibold lg:hidden">
                                <span
                                    className="flex size-10 items-center justify-center rounded-xl"
                                    style={{ background: 'linear-gradient(135deg, #c0166a, #0a1628)' }}
                                >
                                    <AppLogoIcon className="size-6 fill-current text-white" />
                                </span>
                                <span className="text-foreground">CUP-FICCT</span>
                            </Link>

                            <div className="space-y-2 w-full">
                                <h1 className="text-2xl font-bold tracking-tight text-foreground">
                                    {title}
                                </h1>
                                {/* Accent underline using pink palette */}
                                <div
                                    className="mx-auto h-1 w-16 rounded-full lg:mx-0"
                                    style={{ background: 'linear-gradient(90deg, #c0166a, #e04090)' }}
                                />
                                <p className="text-sm leading-6 text-muted-foreground">
                                    {description}
                                </p>
                            </div>
                        </div>

                        {/* Form content */}
                        <div className="auth-form-field">
                            {children}
                        </div>
                    </div>
                </section>
            </main>
        </>
    );
}
