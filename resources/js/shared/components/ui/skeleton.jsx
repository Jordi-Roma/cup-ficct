import { cn } from '@/shared/lib/utils';
function Skeleton({ className, ...props }) {
    return (<div data-slot="skeleton" className={cn('animate-pulse rounded-md bg-primary/10', className)} {...props}/>);
}
export { Skeleton };
