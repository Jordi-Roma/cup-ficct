import { Head, Link, usePage } from '@inertiajs/react';
import {
    BarChart3,
    CheckSquare,
    CreditCard,
    FileText,
    LogIn,
    UserPlus,
} from 'lucide-react';
import { dashboard, login, register } from '@/routes';
import { Button } from '@/shared/components/ui/button';

const processSteps = [
    {
        number: '01',
        title: 'POSTULA',
        description: 'Registra tus datos y envía tu postulación.',
        icon: FileText,
    },
    {
        number: '02',
        title: 'REQUISITOS',
        description: 'Completa tu documentación.',
        icon: CheckSquare,
    },
    {
        number: '03',
        title: 'PAGO CUP',
        description: 'Realiza el pago de inscripción de forma segura.',
        icon: CreditCard,
    },
    {
        number: '04',
        title: 'SIGUE TU PROCESO',
        description: 'Consulta estado, novedades y resultados de tu postulación.',
        icon: BarChart3,
    },
];

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Admisión CUP-FICCT" />

            <style>{`
                /* ── Circuit board pattern ── */
                .welcome-circuit-bg {
                    background-image:
                        linear-gradient(rgba(255,255,255,0.06) 1px, transparent 1px),
                        linear-gradient(90deg, rgba(255,255,255,0.06) 1px, transparent 1px);
                    background-size: 40px 40px;
                }
                .welcome-circuit-left {
                    background-image:
                        linear-gradient(rgba(180,40,100,0.12) 1px, transparent 1px),
                        linear-gradient(90deg, rgba(180,40,100,0.12) 1px, transparent 1px);
                    background-size: 36px 36px;
                }
                /* ── Step card 3D effect ── */
                @keyframes wcFloat {
                    0%, 100% { transform: translateY(0px); }
                    50%       { transform: translateY(-5px); }
                }
                .wc-step {
                    transform-style: preserve-3d;
                    transition: transform 0.35s cubic-bezier(0.34,1.56,0.64,1),
                                box-shadow 0.35s ease;
                    animation: wcFloat 5s ease-in-out infinite;
                }
                .wc-step:nth-child(1) { animation-delay: 0s; }
                .wc-step:nth-child(2) { animation-delay: 0.7s; }
                .wc-step:nth-child(3) { animation-delay: 1.4s; }
                .wc-step:nth-child(4) { animation-delay: 2.1s; }
                .wc-step:hover {
                    transform: translateY(-8px) rotateX(5deg) scale(1.03);
                    box-shadow: 0 20px 40px rgba(120,10,60,0.35), 0 6px 12px rgba(0,0,0,0.2);
                }
                /* ── Dot node pulse ── */
                @keyframes wcDot {
                    0%, 100% { opacity: 0.4; transform: scale(1); }
                    50%       { opacity: 0.9; transform: scale(1.4); }
                }
                .wc-dot { animation: wcDot 3s ease-in-out infinite; }
                .wc-dot:nth-child(2) { animation-delay: 0.6s; }
                .wc-dot:nth-child(3) { animation-delay: 1.2s; }
                .wc-dot:nth-child(4) { animation-delay: 1.8s; }
                /* ── Building filter — line-art on pink ── */
                .wc-building {
                    filter: sepia(1) saturate(0.3) brightness(0.55) contrast(1.4);
                    mix-blend-mode: multiply;
                }
            `}</style>

            {/* ════════════════════════════════════════
                FULL SCREEN WRAPPER
            ════════════════════════════════════════ */}
            <main className="flex min-h-screen flex-col overflow-hidden">

                {/* ── CONTENT ROW ── */}
                <div className="flex flex-1">

                    {/* ════════════════════════════════
                        LEFT PANEL — light pink, building
                    ════════════════════════════════ */}
                    <aside
                        className="welcome-circuit-left relative flex w-64 shrink-0 flex-col overflow-hidden xl:w-72"
                        style={{ background: 'linear-gradient(180deg, #f0a0be 0%, #e890b2 40%, #e080a8 100%)' }}
                    >
                        {/* Decorative circuit nodes */}
                        <div className="pointer-events-none absolute inset-0 overflow-hidden">
                            {[
                                { top: '12%', left: '20%' }, { top: '28%', right: '15%' },
                                { top: '55%', left: '35%' }, { top: '75%', right: '20%' },
                            ].map((pos, i) => (
                                <div
                                    key={i}
                                    className="wc-dot absolute size-2 rounded-full bg-white/60"
                                    style={pos}
                                />
                            ))}
                        </div>

                        {/* Shield logo + faculty name */}
                        <div className="relative z-10 flex flex-col items-center gap-2 px-4 pt-4">
                            <img
                                src="/images/ficct-shield-oval.png"
                                alt="Escudo FICCT"
                                className="h-24 w-auto object-contain xl:h-28"
                                style={{
                                    filter: 'contrast(18) brightness(0.45)',
                                    mixBlendMode: 'multiply',
                                    opacity: 0.72,
                                }}
                            />
                            <div className="text-center">
                                <p className="text-[9px] font-bold leading-tight tracking-wide text-[#4a0030] uppercase">
                                    Facultad de Ingeniería
                                </p>
                                <p className="text-[9px] font-semibold leading-tight text-[#4a0030] uppercase">
                                    en Ciencias de la Computación
                                </p>
                                <p className="text-[9px] font-semibold leading-tight text-[#4a0030] uppercase">
                                    y Telecomunicaciones
                                </p>
                            </div>
                        </div>

                        {/* Big title */}
                        <div className="relative z-10 px-5 pb-2">
                            <h1 className="text-3xl font-extrabold leading-tight text-[#2c0020] xl:text-4xl">
                                ADMISIÓN{' '}
                                <span className="block">UNIVERSITARIA</span>
                            </h1>
                            <p
                                className="mt-1 text-2xl font-extrabold xl:text-3xl"
                                style={{ color: '#b01060' }}
                            >
                                CUP-FICCT
                            </p>
                        </div>

                        {/* Building line-art illustration */}
                        <div className="relative mt-auto overflow-hidden" style={{ height: '58%' }}>
                            {/* Fade top edge into panel */}
                            <div
                                className="absolute inset-x-0 top-0 z-10 h-10"
                                style={{ background: 'linear-gradient(to bottom, #e890b2, transparent)' }}
                            />
                            <img
                                src="/images/ficct-building-lineart.png"
                                alt="Edificio FICCT 236"
                                className="h-full w-full object-contain object-bottom"
                                style={{
                                    /* White bg → transparent via multiply;
                                       boost contrast so faint lines become solid */
                                    filter: 'contrast(2) brightness(0.75)',
                                    mixBlendMode: 'multiply',
                                    opacity: 0.88,
                                }}
                            />
                        </div>
                    </aside>

                    {/* ════════════════════════════════
                        RIGHT PANEL — magenta gradient
                    ════════════════════════════════ */}
                    <section
                        className="welcome-circuit-bg relative flex flex-1 flex-col overflow-hidden"
                        style={{
                            background: 'linear-gradient(145deg, #5e0a32 0%, #7a1244 20%, #6a1040 45%, #7e1448 65%, #520830 85%, #3e0624 100%)',
                        }}
                    >
                        {/* SVG circuit decoration lines */}
                        <svg
                            className="pointer-events-none absolute inset-0 h-full w-full opacity-15"
                            xmlns="http://www.w3.org/2000/svg"
                            preserveAspectRatio="none"
                        >
                            <line x1="0" y1="20%" x2="60%" y2="20%" stroke="white" strokeWidth="0.8" strokeDasharray="6 5" />
                            <line x1="0" y1="80%" x2="100%" y2="80%" stroke="white" strokeWidth="0.8" strokeDasharray="6 5" />
                            <line x1="55%" y1="0" x2="55%" y2="100%" stroke="white" strokeWidth="0.8" strokeDasharray="6 5" />
                            <circle cx="55%" cy="20%" r="5" fill="white" opacity="0.6" />
                            <circle cx="0" cy="80%" r="4" fill="white" opacity="0.5" />
                            <circle cx="55%" cy="80%" r="5" fill="white" opacity="0.6" />
                        </svg>

                        {/* Admin button (if logged in) */}
                        {auth.user && (
                            <div className="relative z-20 flex justify-end p-5">
                                <Button
                                    asChild
                                    className="border border-white/30 bg-white/15 text-white hover:bg-white/25"
                                >
                                    <a href={dashboard.url()}>Dashboard Administrativo</a>
                                </Button>
                            </div>
                        )}

                        {/* ── Main content flex ── */}
                        <div className="relative z-10 flex flex-1 items-center gap-6 px-8 py-8 xl:px-12">

                            {/* Welcome text + buttons */}
                            <div className="flex-1 space-y-6">
                                <div>
                                    <h2
                                        className="text-4xl font-extrabold leading-tight text-white xl:text-5xl"
                                        style={{ textShadow: '0 2px 12px rgba(0,0,0,0.3)' }}
                                    >
                                        TE DAMOS LA{' '}
                                        <span
                                            className="block"
                                            style={{ color: '#ffb8d8' }}
                                        >
                                            BIENVENIDA
                                        </span>
                                    </h2>
                                </div>

                                <p className="max-w-sm text-base leading-7 text-pink-100">
                                    Registra tu postulación al Curso Preuniversitario de la FICCT,
                                    consulta tu proceso de admisión y accede a tu información académica.
                                </p>

                                <div className="flex flex-wrap gap-3">
                                    {auth.user ? (
                                        <Button
                                            asChild
                                            className="h-10 border border-white/40 bg-white/15 px-5 text-sm font-bold text-white hover:bg-white/25"
                                        >
                                            <a href={dashboard.url()}>Ir al Dashboard</a>
                                        </Button>
                                    ) : (
                                        <>
                                            <Button
                                                asChild
                                                className="h-10 border border-white/50 bg-white/15 px-5 text-sm font-bold text-white hover:bg-white/28"
                                                style={{ backdropFilter: 'blur(6px)' }}
                                            >
                                                <Link href={register()}>
                                                    <UserPlus className="size-4" />
                                                    REGISTRARSE COMO POSTULANTE
                                                </Link>
                                            </Button>
                                            <Button
                                                asChild
                                                className="h-10 border border-white/50 bg-white/15 px-5 text-sm font-bold text-white hover:bg-white/28"
                                                style={{ backdropFilter: 'blur(6px)' }}
                                            >
                                                <Link href={login()}>
                                                    <LogIn className="size-4" />
                                                    INICIAR SESIÓN
                                                </Link>
                                            </Button>
                                        </>
                                    )}
                                </div>
                            </div>

                            {/* Step cards — right sub-column */}
                            <div
                                className="hidden w-72 flex-col gap-3 lg:flex xl:w-80"
                                style={{ perspective: '900px' }}
                            >
                                {processSteps.map((step) => (
                                    <div
                                        key={step.number}
                                        className="wc-step group relative overflow-hidden rounded-2xl px-4 py-3"
                                        style={{
                                            background: 'rgba(255,220,235,0.18)',
                                            border: '1px solid rgba(255,255,255,0.28)',
                                            backdropFilter: 'blur(12px)',
                                        }}
                                    >
                                        {/* Hover shimmer */}
                                        <div
                                            className="pointer-events-none absolute inset-0 rounded-2xl opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                                            style={{ background: 'linear-gradient(135deg, rgba(255,255,255,0.15) 0%, transparent 60%)' }}
                                        />

                                        <div className="relative flex items-center gap-3">
                                            {/* Icon box */}
                                            <div
                                                className="flex size-10 shrink-0 items-center justify-center rounded-xl"
                                                style={{ background: 'rgba(255,255,255,0.18)', border: '1px solid rgba(255,255,255,0.3)' }}
                                            >
                                                <step.icon className="size-5 text-white" />
                                            </div>

                                            <div className="min-w-0">
                                                <p className="text-xs font-bold tracking-wide text-white">
                                                    <span
                                                        className="mr-1.5 text-pink-200"
                                                        style={{ fontVariantNumeric: 'tabular-nums' }}
                                                    >
                                                        {step.number}
                                                    </span>
                                                    {step.title}
                                                </p>
                                                <p className="mt-0.5 text-xs leading-4 text-pink-100/90">
                                                    {step.description}
                                                </p>
                                            </div>
                                        </div>

                                        {/* Bottom glow on hover */}
                                        <div
                                            className="absolute bottom-0 left-6 right-6 h-px opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                                            style={{ background: 'linear-gradient(90deg, transparent, rgba(255,255,255,0.7), transparent)' }}
                                        />
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* ── Bottom bar ── */}
                        <div
                            className="relative z-10 border-t border-white/15 py-2 text-center text-xs text-pink-200/80"
                            style={{ background: 'rgba(0,0,0,0.12)' }}
                        >
                            • Facultad de Ingeniería en Ciencias de la Computación y Telecomunicaciones •
                        </div>
                    </section>
                </div>
            </main>
        </>
    );
}
