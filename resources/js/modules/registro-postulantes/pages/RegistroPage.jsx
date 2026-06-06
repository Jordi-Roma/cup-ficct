import { Form, Head } from '@inertiajs/react';
import { login } from '@/routes';
import { store } from '@/routes/register';
import InputError from '@/shared/components/input-error';
import TextLink from '@/shared/components/text-link';
import { Button } from '@/shared/components/ui/button';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';
import { Spinner } from '@/shared/components/ui/spinner';

const turnos = [
    { value: 'MANANA', label: 'Mañana' },
    { value: 'TARDE', label: 'Tarde' },
    { value: 'NOCHE', label: 'Noche' },
];

export default function Register({ gestiones, carreras }) {
    return (
        <>
            <Head title="Solicitud de registro" />
            <Form {...store.form()} disableWhileProcessing className="flex flex-col gap-6">
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="ci">CI</Label>
                                <Input id="ci" type="text" required autoFocus tabIndex={1} name="ci" placeholder="12345678" />
                                <InputError message={errors.ci} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="nombre">Nombre</Label>
                                    <Input id="nombre" type="text" required tabIndex={2} autoComplete="given-name" name="nombre" placeholder="Nombres" />
                                    <InputError message={errors.nombre} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="apellido">Apellido</Label>
                                    <Input id="apellido" type="text" required tabIndex={3} autoComplete="family-name" name="apellido" placeholder="Apellidos" />
                                    <InputError message={errors.apellido} />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="correo">Correo declarado</Label>
                                    <Input id="correo" type="email" required tabIndex={4} autoComplete="email" name="correo" placeholder="correo@ejemplo.com" />
                                    <InputError message={errors.correo} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="telefono">Teléfono</Label>
                                    <Input id="telefono" type="tel" tabIndex={5} autoComplete="tel" name="telefono" placeholder="70000000" />
                                    <InputError message={errors.telefono} />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="sexo">Sexo</Label>
                                    <select id="sexo" name="sexo" required tabIndex={6} className="h-9 rounded-md border border-slate-300 bg-white px-3 py-1 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-[#0D2B85] focus-visible:ring-[3px] focus-visible:ring-[#0D2B85]/20">
                                        <option value="">Seleccione</option>
                                        <option value="M">Masculino</option>
                                        <option value="F">Femenino</option>
                                        <option value="O">Otro</option>
                                    </select>
                                    <InputError message={errors.sexo} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="fecha_nacimiento">Fecha de nacimiento</Label>
                                    <Input id="fecha_nacimiento" type="date" required tabIndex={7} name="fecha_nacimiento" />
                                    <InputError message={errors.fecha_nacimiento} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="direccion">Dirección</Label>
                                <Input id="direccion" type="text" tabIndex={8} name="direccion" placeholder="Dirección" />
                                <InputError message={errors.direccion} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="colegio_procedencia">Colegio de procedencia</Label>
                                    <Input id="colegio_procedencia" type="text" tabIndex={9} name="colegio_procedencia" placeholder="Colegio" />
                                    <InputError message={errors.colegio_procedencia} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="ciudad">Ciudad</Label>
                                    <Input id="ciudad" type="text" tabIndex={10} name="ciudad" placeholder="Ciudad" />
                                    <InputError message={errors.ciudad} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="turno_preferido">Turno preferido</Label>
                                <select id="turno_preferido" name="turno_preferido" required tabIndex={13} className="h-9 rounded-md border border-sidebar-border bg-card px-3 py-1 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-[#0D2B85] focus-visible:ring-[3px] focus-visible:ring-[#0D2B85]/20">
                                    <option value="">Seleccione</option>
                                    {turnos.map((turno) => (
                                        <option key={turno.value} value={turno.value}>{turno.label}</option>
                                    ))}
                                </select>
                                <InputError message={errors.turno_preferido} />
                            </div>

                            <div className="grid gap-3 rounded-xl border p-4">
                                <div>
                                    <Label>Documentos declarados</Label>
                                    <p className="text-sm text-muted-foreground">
                                        Marca lo que entregaste. La administración validará la documentación antes de generar tus credenciales.
                                    </p>
                                </div>
                                <label className="flex items-center gap-3 text-sm">
                                    <input type="hidden" name="presento_titulo_bachiller" value="0" />
                                    <input type="checkbox" name="presento_titulo_bachiller" value="1" tabIndex={14} className="size-4 rounded border-sidebar-border" />
                                    Presentó título de bachiller
                                </label>
                                <InputError message={errors.presento_titulo_bachiller} />
                                <label className="flex items-center gap-3 text-sm">
                                    <input type="hidden" name="presento_fotocopia_carnet" value="0" />
                                    <input type="checkbox" name="presento_fotocopia_carnet" value="1" tabIndex={15} className="size-4 rounded border-sidebar-border" />
                                    Presentó fotocopia de carnet
                                </label>
                                <InputError message={errors.presento_fotocopia_carnet} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="id_gestion">Gestión</Label>
                                    <select id="id_gestion" name="id_gestion" required tabIndex={16} className="h-9 rounded-md border border-sidebar-border bg-card px-3 py-1 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-[#0D2B85] focus-visible:ring-[3px] focus-visible:ring-[#0D2B85]/20" defaultValue={gestiones[0]?.id.toString() ?? ''}>
                                        <option value="">Seleccione</option>
                                        {gestiones.map((gestion) => (
                                            <option key={gestion.id} value={gestion.id}>{gestion.nombre}</option>
                                        ))}
                                    </select>
                                    <InputError message={errors.id_gestion} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="id_carrera_opcion1">Carrera principal</Label>
                                    <select id="id_carrera_opcion1" name="id_carrera_opcion1" required tabIndex={17} className="h-9 rounded-md border border-sidebar-border bg-card px-3 py-1 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-[#0D2B85] focus-visible:ring-[3px] focus-visible:ring-[#0D2B85]/20">
                                        <option value="">Seleccione</option>
                                        {carreras.map((carrera) => (
                                            <option key={carrera.id} value={carrera.id}>{carrera.nombre}</option>
                                        ))}
                                    </select>
                                    <InputError message={errors.id_carrera_opcion1} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="id_carrera_opcion2">Carrera secundaria</Label>
                                <select id="id_carrera_opcion2" name="id_carrera_opcion2" tabIndex={18} className="h-9 rounded-md border border-sidebar-border bg-card px-3 py-1 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-[#0D2B85] focus-visible:ring-[3px] focus-visible:ring-[#0D2B85]/20">
                                    <option value="">Sin segunda opción</option>
                                    {carreras.map((carrera) => (
                                        <option key={carrera.id} value={carrera.id}>{carrera.nombre}</option>
                                    ))}
                                </select>
                                <InputError message={errors.id_carrera_opcion2} />
                            </div>

                            <Button type="submit" className="mt-2 w-full bg-[#0D2B85] text-white hover:bg-[#0a2270]" tabIndex={19} data-test="register-user-button">
                                {processing && <Spinner />}
                                Enviar solicitud
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            ¿Ya tienes una cuenta?{' '}
                            <TextLink href={login()} tabIndex={20} className="text-[#0D2B85] hover:text-[#0a2270]">
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
    title: 'Solicitud de registro',
    description: 'Envía tus datos para validación administrativa',
};
