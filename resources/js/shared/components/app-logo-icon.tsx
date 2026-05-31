import type { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon(props: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img
            {...props}
            src="/images/ficct-logo-icon.png"
            alt="Logo FICCT"
            className={`object-contain ${props.className ?? ''}`}
        />
    );
}
