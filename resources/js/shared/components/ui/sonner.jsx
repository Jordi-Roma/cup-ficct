import { Toaster as Sonner } from 'sonner';
import { useAppearance } from '@/shared/hooks/use-appearance';
import { useFlashToast } from '@/shared/hooks/use-flash-toast';
function Toaster({ ...props }) {
    const { appearance } = useAppearance();
    useFlashToast();
    return (<Sonner theme={appearance} className="toaster group" position="bottom-right" style={{
            '--normal-bg': 'var(--popover)',
            '--normal-text': 'var(--popover-foreground)',
            '--normal-border': 'var(--border)',
        }} {...props}/>);
}
export { Toaster };
