import { Form, Head, usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import ProfileController from '@/actions/App/Modules/AccesoSeguridad/Controllers/ProfileController';
import DeleteUser from '@/modules/acceso-seguridad/components/DeleteUser';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import Heading from '@/shared/components/heading';
import InputError from '@/shared/components/input-error';
import { Button } from '@/shared/components/ui/button';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';
export default function Profile({ mustVerifyEmail, status, }) {
    const { auth } = usePage().props;
    return (<>
            <Head title="Configuracion de perfil"/>

            <h1 className="sr-only">Configuracion de perfil</h1>

            <div className="space-y-6">
                <Heading variant="small" title="Perfil" description="Actualiza tu nombre y correo electronico"/>

                <Form {...ProfileController.update.form()} options={{
            preserveScroll: true,
        }} className="space-y-6">
                    {({ processing, errors }) => (<>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nombre</Label>

                                <Input id="name" className="mt-1 block w-full" defaultValue={auth.user.name} name="name" required autoComplete="name" placeholder="Nombre completo"/>

                                <InputError className="mt-2" message={errors.name}/>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Correo electronico</Label>

                                <Input id="email" type="email" className="mt-1 block w-full" defaultValue={auth.user.email} name="email" required autoComplete="username" placeholder="Correo electronico"/>

                                <InputError className="mt-2" message={errors.email}/>
                            </div>

                            {mustVerifyEmail &&
                auth.user.email_verified_at === null && (<div>
                                        <p className="-mt-4 text-sm text-muted-foreground">
                                            Tu correo electronico no esta verificado.{' '}
                                            <Link href={send()} as="button" className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500">
                                                Haz clic aqui para reenviar el
                                                correo de verificacion.
                                            </Link>
                                        </p>

                                        {status ===
                    'verification-link-sent' && (<div className="mt-2 text-sm font-medium text-green-600">
                                                Se envio un nuevo enlace de
                                                verificacion a tu correo.
                                            </div>)}
                                    </div>)}

                            <div className="flex items-center gap-4">
                                <Button disabled={processing} data-test="update-profile-button">
                                    Guardar
                                </Button>
                            </div>
                        </>)}
                </Form>
            </div>

            <DeleteUser />
        </>);
}
Profile.layout = {
    breadcrumbs: [
        {
            title: 'Configuracion de perfil',
            href: edit(),
        },
    ],
};
