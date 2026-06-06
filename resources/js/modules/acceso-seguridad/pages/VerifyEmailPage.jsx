// Components
import { Form, Head } from '@inertiajs/react';
import { logout } from '@/routes';
import { send } from '@/routes/verification';
import TextLink from '@/shared/components/text-link';
import { Button } from '@/shared/components/ui/button';
import { Spinner } from '@/shared/components/ui/spinner';
export default function VerifyEmail({ status }) {
    return (<>
            <Head title="Verificación de correo"/>

            {status === 'verification-link-sent' && (<div className="mb-4 text-center text-sm font-medium text-green-600">
                    Se envió un nuevo enlace de verificación al correo que
                    indicaste durante el registro.
                </div>)}

            <Form {...send.form()} className="space-y-6 text-center">
                {({ processing }) => (<>
                         <Button disabled={processing} variant="secondary">
                             {processing && <Spinner />}
                             Reenviar correo de verificación
                         </Button>

                         <TextLink href={logout()} className="mx-auto block text-sm">
                             Cerrar sesión
                         </TextLink>
                     </>)}
             </Form>
         </>);
 }
 VerifyEmail.layout = {
     title: 'Verificación de correo',
     description: 'Verifica tu correo haciendo clic en el enlace que acabamos de enviarte.',
 };
