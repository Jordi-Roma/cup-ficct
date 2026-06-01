import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    BookText,
    CalendarCheck,
    GraduationCap,
    Layers,
    KeyRound,
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
import type { Auth, NavGroup } from '@/shared/types';

const mainNavGroups: NavGroup[] = [
    {
        title: 'Sistema y Acceso',
        icon: Shield,
        items: [
            {
                title: 'Panel',
                href: dashboard(),
                icon: LayoutGrid,
            },
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
                title: 'Asignacion academica',
                href: '/academico/asignaciones',
                icon: CalendarCheck,
                permission: 'asignaciones:read',
            },
        ],
    },
    {
        title: 'Exámenes',
        icon: BookOpen,
        items: [],
    },
    {
        title: 'Gestion de Postulantes',
        icon: UserRoundCheck,
        items: [
            {
                title: 'Postulantes',
                href: '/postulantes',
                icon: UserRoundCheck,
                permission: 'postulantes:read',
            },
        ],
    },
    {
        title: 'Reportes y Monitoreo',
        icon: UserCog,
        items: [],
    },
];

export function AppSidebar() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const permissions = auth.permissions ?? [];
    const visibleMainNavGroups = mainNavGroups
        .map((group) => ({
            ...group,
            items: group.items.filter(
                (item) =>
                    !item.permission || permissions.includes(item.permission),
            ),
        }))
        .filter((group) => group.items.length > 0);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
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
