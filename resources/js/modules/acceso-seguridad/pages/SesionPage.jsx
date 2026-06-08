import { Form, Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import InputError from '@/shared/components/input-error';
import PasswordInput from '@/shared/components/password-input';
import TextLink from '@/shared/components/text-link';
import { Button } from '@/shared/components/ui/button';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';
import { Spinner } from '@/shared/components/ui/spinner';

export default function Login({ status, canResetPassword, loginRateLimit }) {
    const [username, setUsername] = useState('');
    const [rememberChecked, setRememberChecked] = useState(false);
    const shouldShowRateLimit =
        loginRateLimit?.attempts > 0 || loginRateLimit?.locked;

    // Cargar desde localStorage únicamente en el cliente después de montar el componente
    // Esto previene errores en entornos SSR (Server-Side Rendering) donde localStorage no existe
    useEffect(() => {
        if (typeof window !== 'undefined' && window.localStorage) {
            const stored = localStorage.getItem('cup_remember_username');
            if (stored) {
                setUsername(stored);
                setRememberChecked(true);
            }
        }
    }, []);

    return (
        <>
            <Head title="Iniciar sesión" />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
                onSubmit={() => {
                    if (typeof window !== 'undefined' && window.localStorage) {
                        if (rememberChecked) {
                            localStorage.setItem('cup_remember_username', username);
                        } else {
                            localStorage.removeItem('cup_remember_username');
                        }
                    }
                }}
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="username">Usuario</Label>
                                <Input
                                    id="username"
                                    type="text"
                                    name="username"
                                    required
                                    autoFocus={!username}
                                    tabIndex={1}
                                    autoComplete="username"
                                    placeholder="tu.usuario"
                                    value={username}
                                    onChange={(e) => setUsername(e.target.value)}
                                />
                                <InputError message={errors.username} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center">
                                    <Label htmlFor="password">Contraseña</Label>
                                    {canResetPassword && (
                                        <TextLink
                                            href={request()}
                                            className="ml-auto text-sm text-[#0D2B85] hover:text-[#0a2270]"
                                            tabIndex={5}
                                        >
                                            ¿Olvidaste tu contraseña?
                                        </TextLink>
                                    )}
                                </div>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    autoFocus={!!username}
                                    placeholder="Contraseña"
                                />
                                <InputError message={errors.password} />
                            </div>

                            {shouldShowRateLimit && (
                                <div
                                    className={`rounded-md border px-3 py-2 text-sm ${
                                        loginRateLimit.locked
                                            ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300'
                                            : 'border-yellow-200 bg-yellow-50 text-yellow-700 dark:border-yellow-900/60 dark:bg-yellow-950/40 dark:text-yellow-300'
                                    }`}
                                >
                                    {loginRateLimit.locked ? (
                                        <span>
                                            Cuenta temporalmente bloqueada. Intenta nuevamente en{' '}
                                            {formatLockTime(loginRateLimit.availableIn)}.
                                        </span>
                                    ) : (
                                        <span>
                                            Intento {loginRateLimit.attempts} de{' '}
                                            {loginRateLimit.maxAttempts}. Te quedan{' '}
                                            {loginRateLimit.remaining} intento
                                            {loginRateLimit.remaining === 1 ? '' : 's'} antes del bloqueo.
                                        </span>
                                    )}
                                </div>
                            )}

                            <div className="flex items-center space-x-3">
                                <input
                                    id="remember"
                                    type="checkbox"
                                    name="remember"
                                    value="1"
                                    checked={rememberChecked}
                                    onChange={(e) => {
                                        setRememberChecked(e.target.checked);
                                        if (e.target.checked === false && typeof window !== 'undefined' && window.localStorage) {
                                            localStorage.removeItem('cup_remember_username');
                                        }
                                    }}
                                    tabIndex={3}
                                    className="size-4 shrink-0 rounded-[4px] border border-input shadow-xs transition-shadow accent-[#0D2B85] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                />
                                <Label htmlFor="remember">Recordarme</Label>
                            </div>

                            <Button
                                type="submit"
                                className="mt-4 w-full"
                                tabIndex={4}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                Iniciar sesión
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            ¿Postulas al CUP-FICCT?{' '}
                            <TextLink
                                href={register()}
                                tabIndex={5}
                                className="text-[#0D2B85] hover:text-[#0a2270]"
                            >
                                Solicita un cupo aquí
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}
        </>
    );
}

Login.layout = {
    title: 'Bienvenido al CUP-FICCT',
    description: 'Ingresa con tu usuario para continuar con el proceso de admisión.',
};

function formatLockTime(seconds) {
    if (!seconds || seconds <= 0) {
        return 'unos segundos';
    }

    if (seconds < 60) {
        return `${seconds} segundo${seconds === 1 ? '' : 's'}`;
    }

    const minutes = Math.ceil(seconds / 60);

    return `${minutes} minuto${minutes === 1 ? '' : 's'}`;
}
