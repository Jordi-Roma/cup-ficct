import { Link, usePage } from '@inertiajs/react';
import {
    BarChart3,
    BookCheck,
    BookMarked,
    BookOpen,
    BookText,
    CalendarCheck,
    ClipboardCheck,
    ClipboardList,
    Clock,
    DoorOpen,
    GraduationCap,
    KeyRound,
    Layers,
    LayoutGrid,
    Shield,
    UserCog,
    UserRound,
    UserRoundCheck,
    Users,
} from 'lucide-react';
import { dashboard } from '@/routes';
import AppLogo from '@/shared/components/app-logo';
import { NavMain } from '@/shared/components/nav-main';
import { NavUser } from '@/shared/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/shared/components/ui/sidebar';

const mainNavGroups = [
    {
        title: 'Acceso / Seguridad',
        icon: Shield,
        items: [
            {
                title: 'Perfil',
                href: '/settings/profile',
                icon: UserRound,
            },
            {
                title: 'Roles',
                href: '/admin/roles',
                icon: Shield,
                permission: 'roles:read',
            },
            {
                title: 'Permisos',
                href: '/admin/permisos',
                icon: KeyRound,
                permission: 'permisos:read',
            },
            {
                title: 'Usuarios',
                href: '/admin/usuarios',
                icon: Users,
                permission: 'usuarios:read',
            },
            {
                title: 'Bitácora',
                href: '/admin/bitacora',
                icon: BookText,
                permission: 'bitacora:read',
            },
        ],
    },
    {
        title: 'Postulaciones',
        icon: UserRoundCheck,
        items: [
            {
                title: 'Postulantes',
                href: '/postulantes',
                icon: UserRoundCheck,
                permission: 'postulantes:read',
            },
            {
                title: 'Solicitudes pendientes',
                href: '/postulantes/solicitudes',
                icon: ClipboardList,
                permission: 'postulantes:update',
            },
            {
                title: 'Admisión por cupos',
                href: '/postulantes/admision-cupos',
                icon: ClipboardCheck,
                permission: 'admision:read',
            },
        ],
    },
    {
        title: 'Gestión Académica',
        icon: GraduationCap,
        items: [
            {
                title: 'Materias CUP',
                href: '/academico/materias',
                icon: BookText,
                permission: 'materias:read',
            },
            {
                title: 'Grupos académicos',
                href: '/academico/grupos',
                icon: Layers,
                permission: 'grupos:read',
            },
            {
                title: 'Docentes',
                href: '/academico/docentes',
                icon: GraduationCap,
                permission: 'docentes:read',
            },
            {
                title: 'Aulas',
                href: '/academico/aulas',
                icon: DoorOpen,
                permission: 'aulas:read',
            },
            {
                title: 'Horarios',
                href: '/academico/horarios',
                icon: Clock,
                permission: 'horarios:read',
            },
            {
                title: 'Asignación académica',
                href: '/academico/asignaciones',
                icon: CalendarCheck,
                permission: 'asignaciones:read',
            },
        ],
    },
    {
        title: 'Evaluaciones',
        icon: BookMarked,
        items: [
            {
                title: 'Mis asignaciones',
                href: '/examenes/mis-asignaciones',
                icon: CalendarCheck,
                permission: 'mis-asignaciones:read',
            },
            {
                title: 'Notas',
                href: '/examenes/notas',
                icon: BookCheck,
                permission: 'notas:read',
            },
            {
                title: 'Historial académico',
                href: '/examenes/historial',
                icon: BookOpen,
                permission: 'historial:read-own',
            },
        ],
    },
    {
        title: 'Reportes y Monitoreo',
        icon: UserCog,
        items: [
            {
                title: 'Dashboard Adm.',
                href: dashboard.url(),
                icon: LayoutGrid,
            },
            {
                title: 'Reportes',
                href: '/reportes',
                icon: BarChart3,
                permission: 'reportes:read',
            },
        ],
    },
];

export function AppSidebar() {
    const { auth } = usePage().props;
    const permissions = auth.permissions ?? [];
    const visibleMainNavGroups = mainNavGroups
        .map((group) => ({
            ...group,
            items: group.items.filter(
                (item) => !item.permission || permissions.includes(item.permission),
            ),
        }))
        .filter((group) => group.items.length > 0);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard.url()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain groups={visibleMainNavGroups} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
