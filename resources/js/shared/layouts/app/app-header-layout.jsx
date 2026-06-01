import { AppContent } from '@/shared/components/app-content';
import { AppHeader } from '@/shared/components/app-header';
import { AppShell } from '@/shared/components/app-shell';
export default function AppHeaderLayout({ children, breadcrumbs, }) {
    return (<AppShell variant="header">
            <AppHeader breadcrumbs={breadcrumbs}/>
            <AppContent variant="header">{children}</AppContent>
        </AppShell>);
}
