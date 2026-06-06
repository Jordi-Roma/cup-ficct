import { Slot } from '@radix-ui/react-slot';
import { cva } from 'class-variance-authority';
import * as React from 'react';
import { cn } from '@/shared/lib/utils';

const badgeVariants = cva(
    'inline-flex w-fit shrink-0 items-center justify-center gap-1 overflow-hidden rounded-md border px-2.5 py-0.5 text-xs font-medium whitespace-nowrap transition-all duration-150 focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 [&>svg]:pointer-events-none [&>svg]:size-3',
    {
        variants: {
            variant: {
                default:
                    'border-transparent bg-primary text-primary-foreground [a&]:hover:bg-primary/90',
                secondary:
                    'border-transparent bg-secondary text-secondary-foreground [a&]:hover:bg-secondary/90',
                destructive:
                    'border-transparent bg-destructive text-white focus-visible:ring-destructive/20 dark:bg-destructive/60 dark:focus-visible:ring-destructive/40 [a&]:hover:bg-destructive/90',
                outline:
                    'text-foreground [a&]:hover:bg-accent [a&]:hover:text-accent-foreground',

                /* ── Estados en tablas ── */

                /* Activo / Habilitado — Celeste pastel / Azul */
                active:
                    'border-transparent bg-[#E8F5FE] text-[#1A4FA3] dark:bg-[#1A2D5A] dark:text-[#90BEF0]',

                /* Inactivo / Bloqueado — Gris azulado pastel / Azul grisáceo */
                inactive:
                    'border-transparent bg-[#F0F0F8] text-[#5A6A9A] dark:bg-[#221E3E] dark:text-[#B0A8D8]',

                /* Pendiente / Suspendido — Lavanda / Lila */
                pending:
                    'border-transparent bg-[#EEF0FF] text-[#3D52B0] dark:bg-[#1A1E42] dark:text-[#9BA8E0]',

                /* Completado — Celeste más saturado */
                completed:
                    'border-transparent bg-[#DBF0FF] text-[#1060A8] dark:bg-[#112840] dark:text-[#7ABCE0]',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    },
);

function Badge({ className, variant, asChild = false, ...props }) {
    const Comp = asChild ? Slot : 'span';
    return (
        <Comp
            data-slot="badge"
            className={cn(badgeVariants({ variant }), className)}
            {...props}
        />
    );
}

export { Badge, badgeVariants };
