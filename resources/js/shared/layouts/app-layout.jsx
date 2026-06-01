import AppLayoutTemplate from '@/shared/layouts/app/app-sidebar-layout';
export default function AppLayout({ breadcrumbs = [], children, }) {
    return (<AppLayoutTemplate breadcrumbs={breadcrumbs}>
            {children}
        </AppLayoutTemplate>);
}
