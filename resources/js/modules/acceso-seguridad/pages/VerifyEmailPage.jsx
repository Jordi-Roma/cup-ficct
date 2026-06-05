// Components
import { Form, Head } from '@inertiajs/react';
import { logout } from '@/routes';
import { send } from '@/routes/verification';
import TextLink from '@/shared/components/text-link';
import { Button } from '@/shared/components/ui/button';
import { Spinner } from '@/shared/components/ui/spinner';
export default function VerifyEmail({ status }) {
    return (<>
            <Head title="Verificacion de correo"/>

            {status === 'verification-link-sent' && (<div className="mb-4 text-center text-sm font-medium text-green-600">
                    Se envio un nuevo enlace de verificacion al correo que
                    indicaste durante el registro.
                </div>)}

            <Form {...send.form()} className="space-y-6 text-center">
                {({ processing }) => (<>
                        <Button disabled={processing} variant="secondary">
                            {processing && <Spinner />}
                            Reenviar correo de verificacion
                        </Button>

                        <TextLink href={logout()} className="mx-auto block text-sm">
                            Cerrar sesion
                        </TextLink>
                    </>)}
            </Form>
        </>);
}
VerifyEmail.layout = {
    title: 'Verificacion de correo',
    description: 'Verifica tu correo haciendo clic en el enlace que acabamos de enviarte.',
};
