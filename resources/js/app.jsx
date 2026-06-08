import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import AuthLayout from '@/modules/acceso-seguridad/layouts/AuthLayout';
import { Toaster } from '@/shared/components/ui/sonner';
import { TooltipProvider } from '@/shared/components/ui/tooltip';
import { initializeTheme } from '@/shared/hooks/use-appearance';
import AppLayout from '@/shared/layouts/app-layout';
import SettingsLayout from '@/shared/layouts/settings/layout';
const appName = import.meta.env.VITE_APP_NAME || 'CUP-FICCT';
const pages = import.meta.glob('./modules/**/pages/**/*.jsx');
const pageAliases = {
    welcome: './modules/acceso-seguridad/pages/WelcomePage.jsx',
    dashboard: './modules/reportes-monitoreo/pages/DashboardPage.jsx',
    'auth/confirm-password': './modules/acceso-seguridad/pages/ConfirmPasswordPage.jsx',
    'auth/forgot-password': './modules/acceso-seguridad/pages/ForgotPasswordPage.jsx',
    'auth/login': './modules/acceso-seguridad/pages/SesionPage.jsx',
    'auth/register': './modules/registro-postulantes/pages/RegistroPage.jsx',
    'auth/reset-password': './modules/acceso-seguridad/pages/ResetPasswordPage.jsx',
    'auth/verify-email': './modules/acceso-seguridad/pages/VerifyEmailPage.jsx',
    'admin/permisos': './modules/acceso-seguridad/pages/PermisosPage.jsx',
    'admin/roles': './modules/acceso-seguridad/pages/RolesPage.jsx',
    'admin/usuarios': './modules/acceso-seguridad/pages/UsuariosPage.jsx',
    'acceso-seguridad/carga-masiva': './modules/acceso-seguridad/pages/CargaMasivaUsuariosPage.jsx',
    'examenes/mis-asignaciones': './modules/examenes/pages/MisAsignacionesPage.jsx',
    'examenes/historial': './modules/examenes/pages/HistorialAcademicoPage.jsx',
    'examenes/notas': './modules/examenes/pages/NotasPage.jsx',
    'registro-postulantes/admision-cupos': './modules/registro-postulantes/pages/AdmisionCuposPage.jsx',
    'gestion-academica/asignaciones': './modules/gestion-academica/pages/AsignacionesAcademicasPage.jsx',
    'gestion-academica/aulas': './modules/gestion-academica/pages/AulasPage.jsx',
    'gestion-academica/gestiones': './modules/gestion-academica/pages/GestionesAcademicasPage.jsx',
    'gestion-academica/grupos': './modules/gestion-academica/pages/GruposAcademicosPage.jsx',
    'gestion-academica/horarios': './modules/gestion-academica/pages/HorariosPage.jsx',
    'gestion-academica/docentes': './modules/gestion-academica/pages/DocentesPage.jsx',
    'gestion-academica/materias': './modules/gestion-academica/pages/MateriasCupPage.jsx',
    'registro-postulantes/postulantes': './modules/registro-postulantes/pages/PostulantesPage.jsx',
    'registro-postulantes/solicitudes': './modules/registro-postulantes/pages/SolicitudesPostulantesPage.jsx',
    'registro-postulantes/pago': './modules/registro-postulantes/pages/PagoPostulantePage.jsx',
    'reportes-monitoreo/reportes': './modules/reportes-monitoreo/pages/ReportesPage.jsx',
    'acceso-seguridad/bitacora': './modules/acceso-seguridad/pages/BitacoraPage.jsx',
    'settings/appearance': './modules/acceso-seguridad/pages/AparienciaPage.jsx',
    'settings/profile': './modules/acceso-seguridad/pages/PerfilPage.jsx',
    'settings/security': './modules/acceso-seguridad/pages/SeguridadPage.jsx',
};
createInertiaApp({
    resolve: async (name) => {
        const pagePath = pageAliases[name];
        if (!pagePath) {
            throw new Error(`Page not found: ${name}`);
        }
        const page = await resolvePageComponent(pagePath, pages);
        return page.default;
    },
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (<TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>);
    },
    progress: {
        color: '#4B5563',
    },
});
// This will set light / dark mode on load...
initializeTheme();
