import AppLogoIcon from '@/shared/components/app-logo-icon';
export default function AppLogo() {
    return (<>
            <div className="flex aspect-square size-9 items-center justify-center overflow-hidden rounded-full bg-white p-0.5">
                <AppLogoIcon className="size-full"/>
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    CUP-FICCT
                </span>
            </div>
        </>);
}
