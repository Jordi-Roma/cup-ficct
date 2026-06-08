import { usePage } from '@inertiajs/react';
import { AlertCircle, AlertTriangle, CheckCircle, Info, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Button } from '@/shared/components/ui/button';

const flashConfig = {
    error: {
        icon: AlertCircle,
        title: 'Error',
        className:
            'border-red-300 bg-red-50 text-red-950 dark:border-red-800 dark:bg-red-950 dark:text-red-50',
        iconClassName: 'text-red-600 dark:text-red-300',
    },
    warning: {
        icon: AlertTriangle,
        title: 'Atencion',
        className:
            'border-amber-300 bg-amber-50 text-amber-950 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-50',
        iconClassName: 'text-amber-600 dark:text-amber-300',
    },
    success: {
        icon: CheckCircle,
        title: 'Exito',
        className:
            'border-emerald-300 bg-emerald-50 text-emerald-950 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-50',
        iconClassName: 'text-emerald-600 dark:text-emerald-300',
    },
    info: {
        icon: Info,
        title: 'Informacion',
        className:
            'border-sky-300 bg-sky-50 text-sky-950 dark:border-sky-800 dark:bg-sky-950 dark:text-sky-50',
        iconClassName: 'text-sky-600 dark:text-sky-300',
    },
};

export default function FlashMessageCenter() {
    const flash = usePage().props.flash ?? {};
    const message = useMemo(() => {
        for (const type of ['error', 'warning', 'success', 'info']) {
            if (flash[type]) {
                return {
                    type,
                    text: flash[type],
                    key: `${type}-${flash[type]}`,
                };
            }
        }

        return null;
    }, [flash]);
    const [visibleMessage, setVisibleMessage] = useState(message);

    useEffect(() => {
        setVisibleMessage(message);
    }, [message]);

    useEffect(() => {
        if (!visibleMessage) {
            return undefined;
        }

        const timeout = window.setTimeout(() => {
            setVisibleMessage(null);
        }, 4000);

        return () => window.clearTimeout(timeout);
    }, [visibleMessage]);

    if (!visibleMessage) {
        return null;
    }

    const config = flashConfig[visibleMessage.type];
    const Icon = config.icon;

    return (
        <div className="pointer-events-none fixed top-6 left-1/2 z-[100] w-[calc(100%-2rem)] max-w-xl -translate-x-1/2">
            <div
                key={visibleMessage.key}
                className={`pointer-events-auto flex items-start gap-3 rounded-lg border p-4 shadow-lg ${config.className}`}
                role="status"
                aria-live="polite"
            >
                <Icon className={`mt-0.5 size-5 shrink-0 ${config.iconClassName}`} />
                <div className="min-w-0 flex-1">
                    <div className="text-sm font-semibold">{config.title}</div>
                    <div className="mt-1 text-sm leading-5">{visibleMessage.text}</div>
                </div>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="h-7 w-7 shrink-0 rounded-md opacity-75 hover:opacity-100"
                    onClick={() => setVisibleMessage(null)}
                    aria-label="Cerrar mensaje"
                >
                    <X className="size-4" />
                </Button>
            </div>
        </div>
    );
}
