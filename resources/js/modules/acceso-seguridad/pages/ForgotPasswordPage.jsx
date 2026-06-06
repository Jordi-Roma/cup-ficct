import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { login } from '@/routes';
import InputError from '@/shared/components/input-error';
import TextLink from '@/shared/components/text-link';
import { Button } from '@/shared/components/ui/button';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';

export default function ForgotPassword({ status }) {
    return (
        <>
            <Head title="Recuperar contrasena" />

            {status && <div className="mb-4 text-center text-sm font-medium text-green-600">{status}</div>}

            <div className="space-y-6">
                <Form action="/forgot-password" method="post">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="username_or_email">Usuario o correo</Label>
                                <Input
                                    id="username_or_email"
                                    type="text"
                                    name="username_or_email"
                                    autoComplete="off"
                                    autoFocus
                                    placeholder="testuser o correo@example.com"
                                />
                                <InputError message={errors.username_or_email} />
                            </div>

                            <div className="my-6 flex items-center justify-start">
                                <Button className="w-full" disabled={processing} data-test="email-password-reset-link-button">
                                    {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                                    Enviar instrucciones
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                <div className="space-x-1 text-center text-sm text-muted-foreground">
                    <span>O vuelve a</span>
                    <TextLink href={login()}>iniciar sesion</TextLink>
                </div>
            </div>
        </>
    );
}

ForgotPassword.layout = {
    title: 'Recuperar contrasena',
    description: 'Ingresa tu usuario o correo registrado. Si existe una cuenta asociada, se enviaran instrucciones al correo institucional.',
};
