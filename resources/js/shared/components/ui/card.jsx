import * as React from 'react';
import { cn } from '@/shared/lib/utils';

function Card({ className, ...props }) {
    return (
        <div
            data-slot="card"
            className={cn(
                'flex flex-col gap-6 rounded-2xl border bg-card py-6 text-card-foreground',
                'shadow-[0_2px_12px_0_rgba(13,43,133,0.07),0_1px_3px_0_rgba(13,43,133,0.04)]',
                'dark:shadow-[0_2px_16px_0_rgba(0,0,0,0.3),0_1px_4px_0_rgba(0,0,0,0.2)]',
                'transition-shadow duration-200 hover:shadow-[0_4px_20px_0_rgba(13,43,133,0.11),0_2px_6px_0_rgba(13,43,133,0.06)]',
                'dark:hover:shadow-[0_4px_24px_0_rgba(0,0,0,0.4),0_2px_8px_0_rgba(0,0,0,0.25)]',
                className,
            )}
            {...props}
        />
    );
}

function CardHeader({ className, ...props }) {
    return (
        <div
            data-slot="card-header"
            className={cn('flex flex-col gap-1.5 px-6', className)}
            {...props}
        />
    );
}

function CardTitle({ className, ...props }) {
    return (
        <div
            data-slot="card-title"
            className={cn('leading-none font-semibold text-foreground', className)}
            {...props}
        />
    );
}

function CardDescription({ className, ...props }) {
    return (
        <div
            data-slot="card-description"
            className={cn('text-sm text-muted-foreground', className)}
            {...props}
        />
    );
}

function CardContent({ className, ...props }) {
    return (
        <div data-slot="card-content" className={cn('px-6', className)} {...props} />
    );
}

function CardFooter({ className, ...props }) {
    return (
        <div
            data-slot="card-footer"
            className={cn('flex items-center px-6', className)}
            {...props}
        />
    );
}

export { Card, CardHeader, CardFooter, CardTitle, CardDescription, CardContent };
