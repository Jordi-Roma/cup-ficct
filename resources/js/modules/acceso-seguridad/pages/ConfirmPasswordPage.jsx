import { Form, Head } from '@inertiajs/react';
import { store } from '@/routes/password/confirm';
import InputError from '@/shared/components/input-error';
import PasswordInput from '@/shared/components/password-input';
import { Button } from '@/shared/components/ui/button';
import { Label } from '@/shared/components/ui/label';
import { Spinner } from '@/shared/components/ui/spinner';
export default function ConfirmPassword() {
    return (<>
            <Head title="Confirmar contraseña"/>

            <Form {...store.form()} resetOnSuccess={['password']}>
                {({ processing, errors }) => (<div className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="password">Contraseña</Label>
                            <PasswordInput id="password" name="password" placeholder="Contraseña" autoComplete="current-password" autoFocus/>

                            <InputError message={errors.password}/>
                        </div>

                        <div className="flex items-center">
                            <Button className="w-full" disabled={processing} data-test="confirm-password-button">
                                {processing && <Spinner />}
                                Confirmar contraseña
                            </Button>
                        </div>
                    </div>)}
            </Form>
        </>);
}
ConfirmPassword.layout = {
    title: 'Confirmar contraseña',
    description: 'Esta es un área segura de la aplicación. Confirma tu contraseña antes de continuar.',
};
