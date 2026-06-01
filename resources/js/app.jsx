import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import AuthLayout from '@/modules/autenticacion/layouts/AuthLayout';
import { Toaster } from '@/shared/components/ui/sonner';
import { TooltipProvider } from '@/shared/components/ui/tooltip';
import { initializeTheme } from '@/shared/hooks/use-appearance';
import AppLayout from '@/shared/layouts/app-layout';
import SettingsLayout from '@/shared/layouts/settings/layout';
const appName = import.meta.env.VITE_APP_NAME || 'CUP-FICCT';
const pages = import.meta.glob('./modules/**/pages/**/*.jsx');
const pageAliases = {
    welcome: './modules/autenticacion/pages/WelcomePage.jsx',
    dashboard: './modules/reportes-monitoreo/pages/DashboardPage.jsx',
    'auth/confirm-password': './modules/autenticacion/pages/ConfirmPasswordPage.jsx',
    'auth/forgot-password': './modules/autenticacion/pages/ForgotPasswordPage.jsx',
    'auth/login': './modules/autenticacion/pages/SesionPage.jsx',
    'auth/register': './modules/registro-postulantes/pages/RegistroPage.jsx',
    'auth/reset-password': './modules/autenticacion/pages/ResetPasswordPage.jsx',
    'auth/verify-email': './modules/autenticacion/pages/VerifyEmailPage.jsx',
    'admin/permisos': './modules/autenticacion/pages/PermisosPage.jsx',
    'admin/roles': './modules/autenticacion/pages/RolesPage.jsx',
    'admin/usuarios': './modules/autenticacion/pages/UsuariosPage.jsx',
    'examenes/historial': './modules/examenes/pages/HistorialAcademicoPage.jsx',
    'examenes/notas': './modules/examenes/pages/NotasPage.jsx',
    'gestion-academica/admision-cupos': './modules/gestion-academica/pages/AdmisionCuposPage.jsx',
    'gestion-academica/asignaciones': './modules/gestion-academica/pages/AsignacionesAcademicasPage.jsx',
    'gestion-academica/grupos': './modules/gestion-academica/pages/GruposAcademicosPage.jsx',
    'gestion-academica/docentes': './modules/gestion-academica/pages/DocentesPage.jsx',
    'gestion-academica/materias': './modules/gestion-academica/pages/MateriasCupPage.jsx',
    'registro-postulantes/postulantes': './modules/registro-postulantes/pages/PostulantesPage.jsx',
    'reportes-monitoreo/reportes': './modules/reportes-monitoreo/pages/ReportesPage.jsx',
    'settings/appearance': './modules/autenticacion/pages/AparienciaPage.jsx',
    'settings/profile': './modules/autenticacion/pages/PerfilPage.jsx',
    'settings/security': './modules/autenticacion/pages/SeguridadPage.jsx',
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
