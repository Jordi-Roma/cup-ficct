import { usePage } from '@inertiajs/react';
import FlashMessageCenter from '@/shared/components/flash-message-center';
import { SidebarProvider } from '@/shared/components/ui/sidebar';
export function AppShell({ children, variant = 'sidebar' }) {
    const isOpen = usePage().props.sidebarOpen;
    if (variant === 'header') {
        return (<div className="flex min-h-screen w-full flex-col">
            <FlashMessageCenter />
            {children}
        </div>);
    }
    return <SidebarProvider defaultOpen={isOpen}>
        <FlashMessageCenter />
        {children}
    </SidebarProvider>;
}
