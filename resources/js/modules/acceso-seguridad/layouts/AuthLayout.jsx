import { Link } from '@inertiajs/react';
import { GraduationCap, ShieldCheck, UsersRound } from 'lucide-react';
import { home } from '@/routes';
import AppLogoIcon from '@/shared/components/app-logo-icon';
export default function AuthLayout({ title = '', description = '', children, }) {
    const isRegister = title.toLowerCase().includes('registro');
    return (<main className="grid min-h-svh bg-slate-50 lg:grid-cols-[1.05fr_0.95fr]">
            <section className="relative hidden overflow-hidden bg-[#061d3b] text-white lg:block">
                <img src="/images/cup-ficct-hero.png" alt="Edificio académico del CUP-FICCT" className="absolute inset-0 h-full w-full object-cover"/>
                <div className="absolute inset-0 bg-[#061d3b]/62"/>
                <div className="absolute inset-y-0 right-0 w-1/2 bg-gradient-to-l from-[#061d3b]/80 to-transparent"/>

                <div className="relative z-10 flex h-full flex-col justify-between p-10 xl:p-14">
                    <Link href={home()} className="flex w-fit items-center gap-3 text-base font-semibold">
                        <span className="flex size-10 items-center justify-center rounded-md bg-white/12 ring-1 ring-white/25">
                            <AppLogoIcon className="size-6 fill-current text-white"/>
                        </span>
                        CUP-FICCT
                    </Link>

                    <div className="max-w-xl space-y-8">
                        <div className="space-y-4">
                            <p className="text-sm font-medium tracking-wide text-red-200 uppercase">
                                Admisión universitaria
                            </p>
                            <h2 className="text-4xl leading-tight font-semibold text-balance xl:text-5xl">
                                Sistema de ingreso al Curso Preuniversitario
                            </h2>
                            <p className="max-w-lg text-base leading-7 text-slate-100">
                                Gestiona tu postulación, seguimiento académico y
                                acceso al proceso de admisión de la FICCT.
                            </p>
                        </div>

                        <div className="grid gap-3 text-sm text-slate-100">
                            <div className="flex items-center gap-3">
                                <ShieldCheck className="size-5 text-red-200"/>
                                Inicio de sesión seguro para usuarios del sistema
                            </div>
                            <div className="flex items-center gap-3">
                                <GraduationCap className="size-5 text-red-200"/>
                                Registro y seguimiento de postulantes CUP
                            </div>
                            <div className="flex items-center gap-3">
                                <UsersRound className="size-5 text-red-200"/>
                                Organización por grupos, materias y admisión
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section className="flex min-h-svh items-center justify-center px-6 py-10 sm:px-8">
                <div className={`w-full space-y-8 ${isRegister ? 'max-w-2xl' : 'max-w-md'}`}>
                    <div className="flex flex-col items-center gap-4 text-center lg:items-start lg:text-left">
                        <Link href={home()} className="flex items-center gap-3 font-semibold lg:hidden">
                            <span className="flex size-10 items-center justify-center rounded-md bg-[#061d3b] text-white">
                                <AppLogoIcon className="size-6 fill-current"/>
                            </span>
                            <span className="text-[#061d3b]">CUP-FICCT</span>
                        </Link>

                        <div className="space-y-2">
                            <h1 className="text-2xl font-semibold tracking-normal text-[#061d3b]">
                                {title}
                            </h1>
                            <div className="mx-auto h-1 w-16 rounded-full bg-[#e30613] lg:mx-0"/>
                            <p className="text-sm leading-6 text-slate-600">
                                {description}
                            </p>
                        </div>
                    </div>

                    {children}
                </div>
            </section>
        </main>);
}
