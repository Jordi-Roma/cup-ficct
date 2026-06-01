import { AppContent } from '@/shared/components/app-content';
import { AppShell } from '@/shared/components/app-shell';
import { AppSidebar } from '@/shared/components/app-sidebar';
import { AppSidebarHeader } from '@/shared/components/app-sidebar-header';
export default function AppSidebarLayout({ children, breadcrumbs = [], }) {
    return (<AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <AppSidebarHeader breadcrumbs={breadcrumbs}/>
                {children}
            </AppContent>
        </AppShell>);
}
