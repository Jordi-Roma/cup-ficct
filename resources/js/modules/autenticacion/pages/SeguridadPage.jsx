import { Form, Head } from '@inertiajs/react';
import { useRef } from 'react';
import SecurityController from '@/actions/App/Modules/Autenticacion/Controllers/SecurityController';
import { edit } from '@/routes/security';
import Heading from '@/shared/components/heading';
import InputError from '@/shared/components/input-error';
import PasswordInput from '@/shared/components/password-input';
import { Button } from '@/shared/components/ui/button';
import { Label } from '@/shared/components/ui/label';
export default function Security(props) {
    const passwordInput = useRef(null);
    const currentPasswordInput = useRef(null);
    return (<>
            <Head title="Configuracion de seguridad"/>

            <h1 className="sr-only">Configuracion de seguridad</h1>

            <div className="space-y-6">
                <Heading variant="small" title="Actualizar contrasena" description="Usa una contrasena segura para proteger tu cuenta"/>

                <Form {...SecurityController.update.form()} options={{
            preserveScroll: true,
        }} resetOnError={[
            'password',
            'password_confirmation',
            'current_password',
        ]} resetOnSuccess onError={(errors) => {
            if (errors.password) {
                passwordInput.current?.focus();
            }
            if (errors.current_password) {
                currentPasswordInput.current?.focus();
            }
        }} className="space-y-6">
                    {({ errors, processing }) => (<>
                            <div className="grid gap-2">
                                <Label htmlFor="current_password">
                                    Contrasena actual
                                </Label>

                                <PasswordInput id="current_password" ref={currentPasswordInput} name="current_password" className="mt-1 block w-full" autoComplete="current-password" placeholder="Contrasena actual"/>

                                <InputError message={errors.current_password}/>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Nueva contrasena</Label>

                                <PasswordInput id="password" ref={passwordInput} name="password" className="mt-1 block w-full" autoComplete="new-password" placeholder="Nueva contrasena" passwordrules={props.passwordRules}/>

                                <InputError message={errors.password}/>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Confirmar contrasena
                                </Label>

                                <PasswordInput id="password_confirmation" name="password_confirmation" className="mt-1 block w-full" autoComplete="new-password" placeholder="Confirmar contrasena" passwordrules={props.passwordRules}/>

                                <InputError message={errors.password_confirmation}/>
                            </div>

                            <div className="flex items-center gap-4">
                                <Button disabled={processing} data-test="update-password-button">
                                    Guardar
                                </Button>
                            </div>
                        </>)}
                </Form>
            </div>

        </>);
}
Security.layout = {
    breadcrumbs: [
        {
            title: 'Configuracion de seguridad',
            href: edit(),
        },
    ],
};
