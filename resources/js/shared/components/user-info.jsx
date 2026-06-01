import { Avatar, AvatarFallback, AvatarImage, } from '@/shared/components/ui/avatar';
import { useInitials } from '@/shared/hooks/use-initials';
export function UserInfo({ user, showEmail = false, }) {
    const getInitials = useInitials();
    const displayName = user.name ||
        [user.nombre, user.apellido].filter(Boolean).join(' ') ||
        user.username ||
        'Usuario';
    const displayEmail = user.email || user.correo || '';
    return (<>
            <Avatar className="h-8 w-8 overflow-hidden rounded-full">
                <AvatarImage src={user.avatar} alt={displayName}/>
                <AvatarFallback className="rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                    {getInitials(displayName)}
                </AvatarFallback>
            </Avatar>
            <div className="grid flex-1 text-left text-sm leading-tight">
                <span className="truncate font-medium">{displayName}</span>
                {showEmail && (<span className="truncate text-xs text-muted-foreground">
                        {displayEmail}
                    </span>)}
            </div>
        </>);
}
