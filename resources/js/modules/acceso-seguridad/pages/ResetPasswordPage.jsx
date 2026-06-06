import { Form, Head } from '@inertiajs/react';
import InputError from '@/shared/components/input-error';
import PasswordInput from '@/shared/components/password-input';
import { Button } from '@/shared/components/ui/button';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';
import { Spinner } from '@/shared/components/ui/spinner';

export default function ResetPassword({ token, username_or_email, passwordRules }) {
    return (
        <>
            <Head title="Restablecer contrasena" />

            <Form
                action="/reset-password"
                method="post"
                transform={(data) => ({ ...data, token, username_or_email: data.username_or_email || username_or_email })}
                resetOnSuccess={['password', 'password_confirmation']}
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="username_or_email">Usuario o correo</Label>
                            <Input
                                id="username_or_email"
                                type="text"
                                name="username_or_email"
                                autoComplete="username"
                                defaultValue={username_or_email}
                                className="mt-1 block w-full"
                            />
                            <InputError message={errors.username_or_email} className="mt-2" />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">Nueva contrasena</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                autoComplete="new-password"
                                className="mt-1 block w-full"
                                autoFocus
                                placeholder="Nueva contrasena"
                            />
                            <InputError message={errors.password} />
                            {passwordRules && (
                                <p className="text-xs text-muted-foreground">
                                    Minimo 8 caracteres, con mayuscula, minuscula, numero y simbolo.
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">Confirmar contrasena</Label>
                            <PasswordInput
                                id="password_confirmation"
                                name="password_confirmation"
                                autoComplete="new-password"
                                className="mt-1 block w-full"
                                placeholder="Confirmar contrasena"
                            />
                            <InputError message={errors.password_confirmation} className="mt-2" />
                        </div>

                        <InputError message={errors.token} />

                        <Button type="submit" className="mt-4 w-full" disabled={processing} data-test="reset-password-button">
                            {processing && <Spinner />}
                            Cambiar contrasena
                        </Button>
                    </div>
                )}
            </Form>
        </>
    );
}

ResetPassword.layout = {
    title: 'Restablecer contrasena',
    description: 'Ingresa tu nueva contrasena',
};
