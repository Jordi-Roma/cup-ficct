import { Link, usePage } from '@inertiajs/react';
import { LayoutGrid, Menu, Search } from 'lucide-react';
import { dashboard } from '@/routes';
import AppLogo from '@/shared/components/app-logo';
import AppLogoIcon from '@/shared/components/app-logo-icon';
import { Breadcrumbs } from '@/shared/components/breadcrumbs';
import { Avatar, AvatarFallback, AvatarImage } from '@/shared/components/ui/avatar';
import { Button } from '@/shared/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/shared/components/ui/dropdown-menu';
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuList,
    navigationMenuTriggerStyle,
} from '@/shared/components/ui/navigation-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/shared/components/ui/sheet';
import { UserMenuContent } from '@/shared/components/user-menu-content';
import { useCurrentUrl } from '@/shared/hooks/use-current-url';
import { useInitials } from '@/shared/hooks/use-initials';
import { cn } from '@/shared/lib/utils';

const mainNavItems = [
    {
        title: 'Dashboard Administrativo',
        href: dashboard.url(),
        icon: LayoutGrid,
    },
];

const activeItemStyles =
    'text-[#0D2B85] font-semibold dark:text-[#C8B8F8]';

export function AppHeader({ breadcrumbs = [] }) {
    const page = usePage();
    const { auth } = page.props;
    const getInitials = useInitials();
    const { isCurrentUrl, whenCurrentUrl } = useCurrentUrl();

    return (
        <>
            {/* Barra principal */}
            <div className="border-b border-sidebar-border/60 bg-background/95 backdrop-blur-sm supports-[backdrop-filter]:bg-background/80">
                <div className="mx-auto flex h-16 items-center px-4 md:max-w-7xl">

                    {/* Mobile Menu */}
                    <div className="lg:hidden">
                        <Sheet>
                            <SheetTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="mr-2 h-[34px] w-[34px] hover:bg-accent"
                                >
                                    <Menu className="h-5 w-5" />
                                </Button>
                            </SheetTrigger>
                            <SheetContent
                                side="left"
                                className="flex h-full w-64 flex-col items-stretch justify-between bg-sidebar border-r border-sidebar-border"
                            >
                                <SheetTitle className="sr-only">
                                    Menú de navegación
                                </SheetTitle>
                                <SheetHeader className="flex justify-start text-left pt-4 px-4">
                                    <AppLogoIcon className="h-6 w-6 fill-current text-[#0D2B85] dark:text-[#C8B8F8]" />
                                </SheetHeader>
                                <div className="flex h-full flex-1 flex-col space-y-4 p-4">
                                    <div className="flex h-full flex-col justify-between text-sm">
                                        <div className="flex flex-col space-y-1">
                                            {mainNavItems.map((item) => (
                                                <Link
                                                    key={item.title}
                                                    href={item.href}
                                                    className="flex items-center gap-2 rounded-lg px-3 py-2 font-medium text-sidebar-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                                                >
                                                    {item.icon && (
                                                        <item.icon className="h-4 w-4" />
                                                    )}
                                                    <span>{item.title}</span>
                                                </Link>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            </SheetContent>
                        </Sheet>
                    </div>

                    {/* Logo */}
                    <Link
                        href={dashboard.url()}
                        prefetch
                        className="flex items-center space-x-2 transition-opacity hover:opacity-85"
                    >
                        <AppLogo />
                    </Link>

                    {/* Desktop Navigation */}
                    <div className="ml-6 hidden h-full items-center space-x-6 lg:flex">
                        <NavigationMenu className="flex h-full items-stretch">
                            <NavigationMenuList className="flex h-full items-stretch space-x-1">
                                {mainNavItems.map((item, index) => (
                                    <NavigationMenuItem
                                        key={index}
                                        className="relative flex h-full items-center"
                                    >
                                        <Link
                                            href={item.href}
                                            className={cn(
                                                navigationMenuTriggerStyle(),
                                                'h-9 cursor-pointer px-3 rounded-lg transition-all duration-150',
                                                whenCurrentUrl(item.href, activeItemStyles),
                                            )}
                                        >
                                            {item.icon && (
                                                <item.icon className="mr-2 h-4 w-4" />
                                            )}
                                            {item.title}
                                        </Link>
                                        {/* Indicador activo */}
                                        {isCurrentUrl(item.href) && (
                                            <div className="absolute bottom-0 left-0 h-0.5 w-full translate-y-px rounded-full bg-gradient-to-r from-[#F4747A] to-[#0D2B85] dark:from-[#9BA8E0] dark:to-[#C8B8F8]" />
                                        )}
                                    </NavigationMenuItem>
                                ))}
                            </NavigationMenuList>
                        </NavigationMenu>
                    </div>

                    {/* Actions: Search + Avatar */}
                    <div className="ml-auto flex items-center gap-2">
                        <Button
                            variant="ghost"
                            size="icon"
                            className="group h-9 w-9 cursor-pointer rounded-lg hover:bg-accent"
                        >
                            <Search className="!size-5 opacity-60 transition-opacity group-hover:opacity-100" />
                        </Button>

                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="ghost"
                                    className="size-10 rounded-full p-1 hover:bg-accent"
                                >
                                    <Avatar className="size-8 overflow-hidden rounded-full ring-2 ring-sidebar-border">
                                        <AvatarImage
                                            src={auth.user?.avatar}
                                            alt={auth.user?.name}
                                        />
                                        <AvatarFallback className="rounded-full bg-gradient-to-br from-[#F4747A] to-[#B8DFF5] text-[#0D2B85] font-semibold dark:from-[#0D2B5A] dark:to-[#1A3A6E] dark:text-[#A8D4F0]">
                                            {getInitials(auth.user?.name ?? '')}
                                        </AvatarFallback>
                                    </Avatar>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent
                                className="w-56 rounded-xl shadow-lg"
                                align="end"
                            >
                                {auth.user && <UserMenuContent user={auth.user} />}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </div>

            {/* Breadcrumbs */}
            {breadcrumbs.length > 1 && (
                <div className="flex w-full border-b border-sidebar-border/50 bg-background/50">
                    <div className="mx-auto flex h-10 w-full items-center justify-start px-4 text-muted-foreground md:max-w-7xl">
                        <Breadcrumbs breadcrumbs={breadcrumbs} />
                    </div>
                </div>
            )}
        </>
    );
}
