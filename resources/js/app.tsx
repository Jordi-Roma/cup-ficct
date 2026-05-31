import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { ComponentType } from 'react';
import AuthLayout from '@/modules/autenticacion/layouts/AuthLayout';
import { Toaster } from '@/shared/components/ui/sonner';
import { TooltipProvider } from '@/shared/components/ui/tooltip';
import { initializeTheme } from '@/shared/hooks/use-appearance';
import AppLayout from '@/shared/layouts/app-layout';
import SettingsLayout from '@/shared/layouts/settings/layout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pages = import.meta.glob<{ default: ComponentType }>(
    './modules/**/pages/**/*.tsx',
);
const pageAliases: Record<string, string> = {
    welcome: './modules/autenticacion/pages/WelcomePage.tsx',
    dashboard: './modules/reportes-monitoreo/pages/DashboardPage.tsx',
    'auth/confirm-password':
        './modules/autenticacion/pages/ConfirmPasswordPage.tsx',
    'auth/forgot-password':
        './modules/autenticacion/pages/ForgotPasswordPage.tsx',
    'auth/login': './modules/autenticacion/pages/SesionPage.tsx',
    'auth/register': './modules/registro-postulantes/pages/RegistroPage.tsx',
    'auth/reset-password':
        './modules/autenticacion/pages/ResetPasswordPage.tsx',
    'auth/two-factor-challenge':
        './modules/autenticacion/pages/TwoFactorChallengePage.tsx',
    'auth/verify-email': './modules/autenticacion/pages/VerifyEmailPage.tsx',
    'admin/permisos': './modules/autenticacion/pages/PermisosPage.tsx',
    'admin/roles': './modules/autenticacion/pages/RolesPage.tsx',
    'admin/usuarios': './modules/autenticacion/pages/UsuariosPage.tsx',
    'gestion-academica/asignaciones':
        './modules/gestion-academica/pages/AsignacionesAcademicasPage.tsx',
    'gestion-academica/grupos':
        './modules/gestion-academica/pages/GruposAcademicosPage.tsx',
    'gestion-academica/docentes':
        './modules/gestion-academica/pages/DocentesPage.tsx',
    'gestion-academica/materias':
        './modules/gestion-academica/pages/MateriasCupPage.tsx',
    'registro-postulantes/postulantes':
        './modules/registro-postulantes/pages/PostulantesPage.tsx',
    'settings/appearance': './modules/autenticacion/pages/AparienciaPage.tsx',
    'settings/profile': './modules/autenticacion/pages/PerfilPage.tsx',
    'settings/security': './modules/autenticacion/pages/SeguridadPage.tsx',
};

createInertiaApp({
    resolve: async (name) => {
        const pagePath = pageAliases[name];

        if (!pagePath) {
            throw new Error(`Page not found: ${name}`);
        }

        const page = await resolvePageComponent<{ default: ComponentType }>(
            pagePath,
            pages,
        );

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
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
