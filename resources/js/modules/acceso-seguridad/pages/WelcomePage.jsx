import { Head, Link, usePage } from '@inertiajs/react';
import { BarChart3, CheckSquare, CreditCard, FileText, LogIn, UserPlus } from 'lucide-react';
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
                .welcome-grid {
                    background-image:
                        linear-gradient(rgba(255,255,255,0.055) 1px, transparent 1px),
                        linear-gradient(90deg, rgba(255,255,255,0.055) 1px, transparent 1px);
                    background-size: 42px 42px;
                }

                .welcome-glow {
                    background:
                        radial-gradient(circle at 18% 12%, rgba(226, 70, 145, 0.45), transparent 28%),
                        radial-gradient(circle at 92% 88%, rgba(156, 24, 92, 0.36), transparent 30%),
                        linear-gradient(135deg, #2b071b 0%, #210515 38%, #5f0b34 100%);
                }

                @keyframes welcomeFloat {
                    0%, 100% { transform: translateY(0); }
                    50% { transform: translateY(-5px); }
                }

                .welcome-step {
                    animation: welcomeFloat 5.5s ease-in-out infinite;
                }

                .welcome-step:nth-child(2) { animation-delay: .5s; }
                .welcome-step:nth-child(3) { animation-delay: 1s; }
                .welcome-step:nth-child(4) { animation-delay: 1.5s; }
            `}</style>

            <main className="welcome-glow welcome-grid min-h-screen overflow-x-hidden text-white">
                <div className="mx-auto flex min-h-screen w-full max-w-7xl flex-col px-5 py-6 sm:px-8 lg:px-10">
                    <header className="relative z-20 flex items-center justify-end gap-4">
                        {auth.user && (
                            <Button asChild className="shrink-0 border border-white/25 bg-white/12 text-white hover:bg-white/20">
                                <Link href={dashboard()}>Ir al Sistema</Link>
                            </Button>
                        )}
                    </header>

                    <section className="relative z-10 grid flex-1 items-center gap-8 py-10 lg:grid-cols-[minmax(0,1fr)_minmax(360px,0.85fr)] lg:gap-10 lg:py-8">
                        <div className="relative min-w-0">
                            <div className="mb-8 flex justify-center lg:justify-start">
                                <div className="relative flex size-24 items-center justify-center rounded-full border border-pink-200/18 bg-white/8 shadow-2xl shadow-pink-950/30 backdrop-blur sm:size-28">
                                    <span className="absolute inset-[-10px] rounded-full border border-dashed border-pink-300/25" />
                                    <img
                                        src="/images/ficct-shield-oval.png"
                                        alt="Escudo FICCT"
                                        className="size-16 rounded-full object-contain sm:size-20"
                                    />
                                </div>
                            </div>

                            <p className="mb-3 text-xs font-extrabold tracking-wide text-pink-200 sm:text-sm">
                                ADMISIÓN UNIVERSITARIA
                            </p>

                            <h1 className="max-w-2xl text-4xl font-black leading-tight tracking-tight sm:text-5xl lg:text-6xl">
                                TE DAMOS LA <span className="block text-pink-200">BIENVENIDA</span>
                            </h1>

                            <p className="mt-4 text-3xl font-black tracking-tight text-pink-300 sm:text-4xl">CUP-FICCT</p>

                            <p className="mt-6 max-w-xl text-base leading-7 text-pink-50/88 sm:text-lg">
                                Registra tu postulación al Curso Preuniversitario de la FICCT,
                                consulta tu proceso de admisión y accede a tu información académica.
                            </p>

                            <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                                {auth.user ? (
                                    <Button
                                        asChild
                                        className="h-12 w-full border border-white/35 bg-white/14 px-6 text-sm font-bold text-white hover:bg-white/24 sm:w-auto"
                                    >
                                        <Link href={dashboard()}>Ir al Sistema</Link>
                                    </Button>
                                ) : (
                                    <>
                                        <Button
                                            asChild
                                            className="h-12 w-full border border-white/35 bg-white/14 px-6 text-sm font-bold text-white hover:bg-white/24 sm:w-auto"
                                        >
                                            <Link href={register()}>
                                                <UserPlus className="size-4" />
                                                REGISTRARSE COMO POSTULANTE
                                            </Link>
                                        </Button>
                                        <Button
                                            asChild
                                            className="h-12 w-full border border-white/35 bg-white/14 px-6 text-sm font-bold text-white hover:bg-white/24 sm:w-auto"
                                        >
                                            <Link href={login()}>
                                                <LogIn className="size-4" />
                                                INICIAR SESIÓN
                                            </Link>
                                        </Button>
                                    </>
                                )}
                            </div>

                            <div className="mt-8 grid gap-3 sm:max-w-xl">
                                {processSteps.map((step) => (
                                    <div
                                        key={step.number}
                                        className="welcome-step grid grid-cols-[auto_auto_minmax(0,1fr)] items-center gap-3 rounded-xl border border-white/10 bg-white/8 px-3 py-3 shadow-lg shadow-black/10 backdrop-blur"
                                    >
                                        <span className="flex size-9 items-center justify-center rounded-xl bg-pink-500/55 text-xs font-black text-white">
                                            {step.number}
                                        </span>
                                        <step.icon className="size-4 text-pink-200" />
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-bold text-white">{step.title}</p>
                                            <p className="text-xs leading-5 text-pink-50/75">{step.description}</p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="relative min-h-[320px] overflow-hidden rounded-[2rem] border border-white/10 bg-white/[0.035] p-4 shadow-2xl shadow-black/20 sm:min-h-[420px] lg:min-h-[620px]">
                            <div className="absolute inset-0 welcome-grid opacity-70" />
                            <img
                                src="/images/ficct-building-lineart.png"
                                alt="Edificio FICCT 236"
                                className="absolute bottom-0 left-1/2 max-h-[82%] w-[118%] max-w-none -translate-x-1/2 object-contain object-bottom opacity-80 mix-blend-screen sm:max-h-[88%] lg:w-[125%]"
                            />

                            <div className="absolute inset-x-0 bottom-0 h-36 bg-gradient-to-t from-[#210515] to-transparent" />
                        </div>
                    </section>

                    <footer className="relative z-10 border-t border-white/10 py-4 text-xs text-pink-100/65">
                        • Facultad de Ingeniería en Ciencias de la Computación y Telecomunicaciones •
                    </footer>
                </div>
            </main>
        </>
    );
}
