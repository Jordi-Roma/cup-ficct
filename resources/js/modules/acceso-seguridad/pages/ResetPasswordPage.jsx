import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/shared/components/input-error';
import PasswordInput from '@/shared/components/password-input';
import PasswordRequirements, { validatePasswordRequirements } from '@/shared/components/password-requirements';
import { Button } from '@/shared/components/ui/button';
import { Label } from '@/shared/components/ui/label';
import { Spinner } from '@/shared/components/ui/spinner';

export default function ResetPassword({ status }) {
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const passwordStatus = validatePasswordRequirements(password);
    const passwordConfirmationMatches = password !== '' && password === passwordConfirmation;

    return (
        <>
            <Head title="Cambiar contraseña" />

            {status && <div className="mb-4 text-center text-sm font-medium text-green-600">{status}</div>}

            <Form
                action="/reset-password"
                method="post"
                resetOnSuccess={['password', 'password_confirmation']}
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="password">Nueva contraseña</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                autoComplete="new-password"
                                className="mt-1 block w-full"
                                autoFocus
                                placeholder="Nueva contraseña"
                                value={password}
                                onChange={(event) => setPassword(event.target.value)}
                            />
                            <PasswordRequirements password={password} />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">Confirmar contraseña</Label>
                            <PasswordInput
                                id="password_confirmation"
                                name="password_confirmation"
                                autoComplete="new-password"
                                className="mt-1 block w-full"
                                placeholder="Confirmar contraseña"
                                value={passwordConfirmation}
                                onChange={(event) => setPasswordConfirmation(event.target.value)}
                            />
                            {passwordConfirmation && !passwordConfirmationMatches && (
                                <p className="text-sm text-destructive">Las contraseñas no coinciden.</p>
                            )}
                            <InputError message={errors.password_confirmation} className="mt-2" />
                        </div>

                        <Button
                            type="submit"
                            className="mt-4 w-full"
                            disabled={processing || !passwordStatus.isValid || !passwordConfirmationMatches}
                            data-test="reset-password-button"
                        >
                            {processing && <Spinner />}
                            Cambiar contraseña
                        </Button>
                    </div>
                )}
            </Form>
        </>
    );
}

ResetPassword.layout = {
    title: 'Cambiar contraseña',
    description: 'Define una nueva contraseña segura para tu cuenta.',
};
