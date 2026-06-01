import { Head, Link, usePage } from '@inertiajs/react';
import { BarChart3, CalendarDays, CheckSquare, CreditCard, FileText, LogIn, Monitor, ShieldCheck, UserPlus, UsersRound, } from 'lucide-react';
import { dashboard, login, register } from '@/routes';
import { Button } from '@/shared/components/ui/button';
const processSteps = [
    {
        title: 'Postula',
        description: 'Registra tus datos y envía tu postulación.',
        icon: FileText,
    },
    {
        title: 'Cumple requisitos',
        description: 'Marca tu documentación como completa.',
        icon: CheckSquare,
    },
    {
        title: 'Realiza tu pago',
        description: 'Asocia el pago de inscripción a tu proceso.',
        icon: CreditCard,
    },
    {
        title: 'Sigue tu proceso',
        description: 'Consulta estado, grupo, notas y resultado.',
        icon: BarChart3,
    },
];
const subjects = [
    { name: 'Computación', icon: Monitor },
    { name: 'Matemáticas', icon: BarChart3 },
    { name: 'Inglés', icon: FileText },
    { name: 'Física', icon: CheckSquare },
];
export default function Welcome() {
    const { auth } = usePage().props;
    return (<>
            <Head title="Admisión CUP-FICCT"/>

            <main className="min-h-screen overflow-hidden bg-slate-50 text-slate-950">
                <section className="relative mx-auto flex min-h-screen w-full max-w-[1600px] flex-col px-5 py-5 sm:px-8 lg:px-12">
                    <header className="relative z-20 flex items-center justify-between gap-4 py-4">
                        <Link href="/" className="flex items-center gap-3">
                            <span className="flex size-10 items-center justify-center rounded-md bg-[#061d3b] text-white">
                                <span className="text-sm font-bold">FC</span>
                            </span>
                            <div className="leading-tight">
                                <p className="font-semibold text-[#061d3b]">
                                    CUP-FICCT
                                </p>
                                <p className="text-xs text-slate-500">
                                    Admisión universitaria
                                </p>
                            </div>
                        </Link>

                        {auth.user && (<Button asChild>
                                <Link href={dashboard()}>Panel</Link>
                            </Button>)}
                    </header>

                    <div className="relative z-10 grid flex-1 items-center gap-10 py-10 lg:grid-cols-[0.9fr_1.1fr] lg:py-6">
                        <div className="max-w-2xl space-y-8">
                            <div className="space-y-5">
                                <div className="inline-flex items-center gap-2 rounded-full border border-red-100 bg-white px-3 py-1 text-sm font-medium text-[#e30613] shadow-sm">
                                    <ShieldCheck className="size-4"/>
                                    Proceso de admisión CUP 2026
                                </div>

                                <div className="space-y-3">
                                    <h1 className="text-5xl leading-tight font-bold tracking-normal text-balance text-[#061d3b] sm:text-6xl lg:text-7xl">
                                        CUP-FICCT{' '}
                                        <span className="text-[#e30613]">
                                            Admisión
                                        </span>{' '}
                                        Universitaria
                                    </h1>
                                    <div className="h-1.5 w-24 rounded-full bg-[#e30613]"/>
                                </div>

                                <p className="max-w-xl text-lg leading-8 text-slate-700">
                                    Registra tu postulación al Curso
                                    Preuniversitario de la FICCT, consulta tu
                                    proceso de admisión y accede a tu
                                    información académica.
                                </p>
                            </div>

                            <div className="flex flex-col gap-3 sm:flex-row">
                                {auth.user ? (<Button asChild className="h-12 bg-[#061d3b] px-6 text-base hover:bg-[#0b2a52]">
                                        <Link href={dashboard()}>
                                            Ir al dashboard
                                        </Link>
                                    </Button>) : (<>
                                        <Button asChild className="h-12 bg-[#061d3b] px-6 text-base hover:bg-[#0b2a52]">
                                            <Link href={login()}>
                                                <LogIn className="size-5"/>
                                                Iniciar sesión
                                            </Link>
                                        </Button>
                                        <Button asChild className="h-12 bg-[#e30613] px-6 text-base hover:bg-[#bb0710]">
                                            <Link href={register()}>
                                                <UserPlus className="size-5"/>
                                                Registrarse como postulante
                                            </Link>
                                        </Button>
                                    </>)}
                            </div>

                            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                {processSteps.map((step) => (<div key={step.title} className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                                        <step.icon className="mb-3 size-6 text-[#e30613]"/>
                                        <h2 className="font-semibold text-[#061d3b]">
                                            {step.title}
                                        </h2>
                                        <p className="mt-1 text-sm leading-5 text-slate-600">
                                            {step.description}
                                        </p>
                                    </div>))}
                            </div>

                            <div className="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-3">
                                <div className="flex items-center gap-3">
                                    <CalendarDays className="size-7 text-[#e30613]"/>
                                    <div>
                                        <p className="font-semibold text-[#061d3b]">
                                            Gestión activa
                                        </p>
                                        <p className="text-sm text-slate-600">
                                            CUP 2026
                                        </p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-3">
                                    <UsersRound className="size-7 text-[#061d3b]"/>
                                    <div>
                                        <p className="font-semibold text-[#061d3b]">
                                            Cupos por grupo
                                        </p>
                                        <p className="text-sm text-slate-600">
                                            70 estudiantes
                                        </p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-3">
                                    <ShieldCheck className="size-7 text-[#e30613]"/>
                                    <div>
                                        <p className="font-semibold text-[#061d3b]">
                                            Proceso seguro
                                        </p>
                                        <p className="text-sm text-slate-600">
                                            Plataforma confiable
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="lg:hidden">
                                <p className="mb-3 text-sm font-semibold text-[#061d3b]">
                                    Áreas de evaluación
                                </p>
                                <div className="grid grid-cols-2 gap-3">
                                    {subjects.map((subject) => (<div key={subject.name} className="flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-3 text-sm font-medium text-slate-700 shadow-sm">
                                            <subject.icon className="size-5 text-[#e30613]"/>
                                            {subject.name}
                                        </div>))}
                                </div>
                            </div>
                        </div>

                        <div className="relative hidden min-h-[680px] lg:block">
                            <div className="absolute inset-y-0 -right-12 left-0 overflow-hidden rounded-l-[2rem] border border-slate-200 bg-white shadow-2xl">
                                <img src="/images/cup-ficct-hero.png" alt="Postulantes frente a la Facultad de Ingeniería de la FICCT" className="h-full w-full object-cover object-[72%_center]"/>
                                <div className="absolute inset-y-0 left-0 w-32 bg-gradient-to-r from-slate-50 to-transparent"/>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </>);
}
