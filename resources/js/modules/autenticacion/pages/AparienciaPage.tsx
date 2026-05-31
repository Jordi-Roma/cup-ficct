import { Head } from '@inertiajs/react';
import { edit as editAppearance } from '@/routes/appearance';
import AppearanceTabs from '@/shared/components/appearance-tabs';
import Heading from '@/shared/components/heading';

export default function Appearance() {
    return (
        <>
            <Head title="Configuracion de apariencia" />

            <h1 className="sr-only">Configuracion de apariencia</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Apariencia"
                    description="Actualiza el modo visual de tu cuenta"
                />
                <AppearanceTabs />
            </div>
        </>
    );
}

Appearance.layout = {
    breadcrumbs: [
        {
            title: 'Configuracion de apariencia',
            href: editAppearance(),
        },
    ],
};
