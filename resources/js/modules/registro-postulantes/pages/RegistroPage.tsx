import { Form, Head } from '@inertiajs/react';
import { login } from '@/routes';
import { store } from '@/routes/register';
import InputError from '@/shared/components/input-error';
import PasswordInput from '@/shared/components/password-input';
import TextLink from '@/shared/components/text-link';
import { Button } from '@/shared/components/ui/button';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';
import { Spinner } from '@/shared/components/ui/spinner';

type CatalogOption = {
    id: number;
    nombre: string;
};

type Props = {
    passwordRules: string;
    gestiones: CatalogOption[];
    carreras: CatalogOption[];
};

export default function Register({ passwordRules, gestiones, carreras }: Props) {
    return (
        <>
            <Head title="Registro de postulante" />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="ci">CI</Label>
                                    <Input
                                        id="ci"
                                        type="text"
                                        required
                                        autoFocus
                                        tabIndex={1}
                                        name="ci"
                                        placeholder="12345678"
                                    />
                                    <InputError message={errors.ci} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="username">Username</Label>
                                    <Input
                                        id="username"
                                        type="text"
                                        required
                                        tabIndex={2}
                                        autoComplete="username"
                                        name="username"
                                        placeholder="tu.usuario"
                                    />
                                    <InputError message={errors.username} />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="nombre">Nombre</Label>
                                    <Input
                                        id="nombre"
                                        type="text"
                                        required
                                        tabIndex={3}
                                        autoComplete="given-name"
                                        name="nombre"
                                        placeholder="Nombres"
                                    />
                                    <InputError message={errors.nombre} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="apellido">Apellido</Label>
                                    <Input
                                        id="apellido"
                                        type="text"
                                        required
                                        tabIndex={4}
                                        autoComplete="family-name"
                                        name="apellido"
                                        placeholder="Apellidos"
                                    />
                                    <InputError message={errors.apellido} />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="correo">Correo</Label>
                                    <Input
                                        id="correo"
                                        type="email"
                                        required
                                        tabIndex={5}
                                        autoComplete="email"
                                        name="correo"
                                        placeholder="correo@ejemplo.com"
                                    />
                                    <InputError message={errors.correo} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="telefono">Teléfono</Label>
                                    <Input
                                        id="telefono"
                                        type="tel"
                                        tabIndex={6}
                                        autoComplete="tel"
                                        name="telefono"
                                        placeholder="70000000"
                                    />
                                    <InputError message={errors.telefono} />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="sexo">Sexo</Label>
                                    <select
                                        id="sexo"
                                        name="sexo"
                                        required
                                        tabIndex={7}
                                        className="h-9 rounded-md border border-slate-300 bg-white px-3 py-1 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-[#e30613] focus-visible:ring-[3px] focus-visible:ring-[#e30613]/20"
                                    >
                                        <option value="">Seleccione</option>
                                        <option value="M">Masculino</option>
                                        <option value="F">Femenino</option>
                                        <option value="O">Otro</option>
                                    </select>
                                    <InputError message={errors.sexo} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="fecha_nacimiento">
                                        Fecha de nacimiento
                                    </Label>
                                    <Input
                                        id="fecha_nacimiento"
                                        type="date"
                                        required
                                        tabIndex={8}
                                        name="fecha_nacimiento"
                                    />
                                    <InputError
                                        message={errors.fecha_nacimiento}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="direccion">Dirección</Label>
                                <Input
                                    id="direccion"
                                    type="text"
                                    tabIndex={9}
                                    name="direccion"
                                    placeholder="Dirección"
                                />
                                <InputError message={errors.direccion} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="colegio_procedencia">
                                        Colegio de procedencia
                                    </Label>
                                    <Input
                                        id="colegio_procedencia"
                                        type="text"
                                        tabIndex={10}
                                        name="colegio_procedencia"
                                        placeholder="Colegio"
                                    />
                                    <InputError
                                        message={errors.colegio_procedencia}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="ciudad">Ciudad</Label>
                                    <Input
                                        id="ciudad"
                                        type="text"
                                        tabIndex={11}
                                        name="ciudad"
                                        placeholder="Ciudad"
                                    />
                                    <InputError message={errors.ciudad} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="documentacion_completa">
                                    Requisitos presentados
                                </Label>
                                <p className="text-sm text-muted-foreground">
                                    Título de Bachiller, Cédula de Identidad,
                                    Fotografía y otros documentos
                                </p>
                                <select
                                    id="documentacion_completa"
                                    name="documentacion_completa"
                                    required
                                    tabIndex={12}
                                    className="h-9 rounded-md border border-slate-300 bg-white px-3 py-1 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-[#e30613] focus-visible:ring-[3px] focus-visible:ring-[#e30613]/20"
                                    defaultValue=""
                                >
                                    <option value="">Seleccione</option>
                                    <option value="1">Sí</option>
                                    <option value="0">No</option>
                                </select>
                                <InputError
                                    message={errors.documentacion_completa}
                                />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="id_gestion">Gestión</Label>
                                    <select
                                        id="id_gestion"
                                        name="id_gestion"
                                        required
                                        tabIndex={13}
                                        className="h-9 rounded-md border border-slate-300 bg-white px-3 py-1 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-[#e30613] focus-visible:ring-[3px] focus-visible:ring-[#e30613]/20"
                                        defaultValue={
                                            gestiones[0]?.id.toString() ?? ''
                                        }
                                    >
                                        <option value="">Seleccione</option>
                                        {gestiones.map((gestion) => (
                                            <option
                                                key={gestion.id}
                                                value={gestion.id}
                                            >
                                                {gestion.nombre}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.id_gestion} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="id_carrera_opcion1">
                                        Carrera principal
                                    </Label>
                                    <select
                                        id="id_carrera_opcion1"
                                        name="id_carrera_opcion1"
                                        required
                                        tabIndex={14}
                                        className="h-9 rounded-md border border-slate-300 bg-white px-3 py-1 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-[#e30613] focus-visible:ring-[3px] focus-visible:ring-[#e30613]/20"
                                    >
                                        <option value="">Seleccione</option>
                                        {carreras.map((carrera) => (
                                            <option
                                                key={carrera.id}
                                                value={carrera.id}
                                            >
                                                {carrera.nombre}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError
                                        message={errors.id_carrera_opcion1}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="id_carrera_opcion2">
                                    Carrera secundaria
                                </Label>
                                <select
                                    id="id_carrera_opcion2"
                                    name="id_carrera_opcion2"
                                    tabIndex={15}
                                    className="h-9 rounded-md border border-slate-300 bg-white px-3 py-1 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-[#e30613] focus-visible:ring-[3px] focus-visible:ring-[#e30613]/20"
                                >
                                    <option value="">Sin segunda opción</option>
                                    {carreras.map((carrera) => (
                                        <option
                                            key={carrera.id}
                                            value={carrera.id}
                                        >
                                            {carrera.nombre}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={errors.id_carrera_opcion2}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Contraseña</Label>
                                <PasswordInput
                                    id="password"
                                    required
                                    tabIndex={16}
                                    autoComplete="new-password"
                                    name="password"
                                    placeholder="Contraseña"
                                    passwordrules={passwordRules}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Confirmar contraseña
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    required
                                    tabIndex={17}
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    placeholder="Confirmar contraseña"
                                    passwordrules={passwordRules}
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 w-full bg-[#e30613] text-white hover:bg-[#bb0710]"
                                tabIndex={18}
                                data-test="register-user-button"
                            >
                                {processing && <Spinner />}
                                Registrar postulación
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            ¿Ya tienes una cuenta?{' '}
                            <TextLink
                                href={login()}
                                tabIndex={19}
                                className="text-[#e30613] hover:text-[#bb0710]"
                            >
                                Iniciar sesión
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

Register.layout = {
    title: 'Registro de postulante',
    description: 'Crea tu cuenta para postular al CUP-FICCT',
};
