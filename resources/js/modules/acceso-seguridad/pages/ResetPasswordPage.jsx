import { Form, Head } from '@inertiajs/react';
import { update } from '@/routes/password';
import InputError from '@/shared/components/input-error';
import PasswordInput from '@/shared/components/password-input';
import { Button } from '@/shared/components/ui/button';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';
import { Spinner } from '@/shared/components/ui/spinner';
export default function ResetPassword({ token, email, passwordRules }) {
    return (<>
            <Head title="Restablecer contrasena"/>

            <Form {...update.form()} transform={(data) => ({ ...data, token, email })} resetOnSuccess={['password', 'password_confirmation']}>
                {({ processing, errors }) => (<div className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="email">Correo electronico</Label>
                            <Input id="email" type="email" name="email" autoComplete="email" value={email} className="mt-1 block w-full" readOnly/>
                            <InputError message={errors.email} className="mt-2"/>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">Contrasena</Label>
                            <PasswordInput id="password" name="password" autoComplete="new-password" className="mt-1 block w-full" autoFocus placeholder="Contrasena" passwordrules={passwordRules}/>
                            <InputError message={errors.password}/>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">
                                Confirmar contrasena
                            </Label>
                            <PasswordInput id="password_confirmation" name="password_confirmation" autoComplete="new-password" className="mt-1 block w-full" placeholder="Confirmar contrasena" passwordrules={passwordRules}/>
                            <InputError message={errors.password_confirmation} className="mt-2"/>
                        </div>

                        <Button type="submit" className="mt-4 w-full" disabled={processing} data-test="reset-password-button">
                            {processing && <Spinner />}
                            Restablecer contrasena
                        </Button>
                    </div>)}
            </Form>
        </>);
}
ResetPassword.layout = {
    title: 'Restablecer contrasena',
    description: 'Ingresa tu nueva contrasena',
};
