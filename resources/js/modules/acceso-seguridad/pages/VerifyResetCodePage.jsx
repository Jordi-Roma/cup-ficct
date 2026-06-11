import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import InputError from '@/shared/components/input-error';
import { Button } from '@/shared/components/ui/button';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';

export default function VerifyResetCode({ username_or_email = '', status }) {
    return (
        <>
            <Head title="Verificar código" />

            {status && <div className="mb-4 text-center text-sm font-medium text-green-600">{status}</div>}

            <Form action="/reset-password/verify" method="post">
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <input type="hidden" name="username_or_email" value={username_or_email} />

                        <div className="grid gap-2">
                            <Label htmlFor="token">Código de recuperación</Label>
                            <Input
                                id="token"
                                type="text"
                                name="token"
                                inputMode="numeric"
                                maxLength={6}
                                autoComplete="one-time-code"
                                autoFocus
                                className="mt-1 block w-full"
                                placeholder="123456"
                            />
                            <InputError message={errors.token || errors.username_or_email} className="mt-2" />
                        </div>

                        <Button type="submit" className="w-full" disabled={processing}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            Verificar código
                        </Button>
                    </div>
                )}
            </Form>
        </>
    );
}

VerifyResetCode.layout = {
    title: 'Verificar código',
    description: 'Ingresa el código que enviamos al correo configurado.',
};
