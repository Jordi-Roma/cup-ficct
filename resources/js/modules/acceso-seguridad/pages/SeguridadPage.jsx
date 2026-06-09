import { Form, Head } from '@inertiajs/react';
import { useRef, useState } from 'react';
import SecurityController from '@/actions/App/Modules/AccesoSeguridad/Controllers/SecurityController';
import { edit } from '@/routes/security';
import Heading from '@/shared/components/heading';
import InputError from '@/shared/components/input-error';
import PasswordInput from '@/shared/components/password-input';
import PasswordRequirements, { validatePasswordRequirements } from '@/shared/components/password-requirements';
import { Button } from '@/shared/components/ui/button';
import { Label } from '@/shared/components/ui/label';
export default function Security(props) {
    const passwordInput = useRef(null);
    const currentPasswordInput = useRef(null);
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const passwordStatus = validatePasswordRequirements(password);
    const passwordConfirmationMatches = password !== '' && password === passwordConfirmation;
    return (<>
            <Head title="Cambiar contraseña"/>

            <h1 className="sr-only">Cambiar contraseña</h1>

            <div className="space-y-6">
                <Heading variant="small" title="Actualizar contraseña" description="Usa una contraseña segura para proteger tu cuenta"/>

                <Form {...SecurityController.update.form()} options={{
            preserveScroll: true,
        }} resetOnError={[
            'password',
            'password_confirmation',
            'current_password',
        ]} resetOnSuccess onSuccess={() => {
            setPassword('');
            setPasswordConfirmation('');
        }} onError={(errors) => {
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
                                    Contraseña actual
                                </Label>

                                <PasswordInput id="current_password" ref={currentPasswordInput} name="current_password" className="mt-1 block w-full" autoComplete="current-password" placeholder="Contraseña actual"/>

                                <InputError message={errors.current_password}/>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Nueva contraseña</Label>

                                <PasswordInput id="password" ref={passwordInput} name="password" className="mt-1 block w-full" autoComplete="new-password" placeholder="Nueva contraseña" passwordrules={props.passwordRules} value={password} onChange={(event) => setPassword(event.target.value)}/>

                                <PasswordRequirements password={password}/>

                                <InputError message={errors.password}/>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Confirmar contraseña
                                </Label>

                                <PasswordInput id="password_confirmation" name="password_confirmation" className="mt-1 block w-full" autoComplete="new-password" placeholder="Confirmar contraseña" passwordrules={props.passwordRules} value={passwordConfirmation} onChange={(event) => setPasswordConfirmation(event.target.value)}/>

                                {passwordConfirmation && !passwordConfirmationMatches && (
                                    <p className="text-sm text-destructive">Las contraseñas no coinciden.</p>
                                )}

                                <InputError message={errors.password_confirmation}/>
                            </div>

                            <div className="flex items-center gap-4">
                                <Button disabled={processing || !passwordStatus.isValid || !passwordConfirmationMatches} data-test="update-password-button">
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
            title: 'Cambiar contraseña',
            href: edit(),
        },
    ],
};
