import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import PasswordInput from '@/shared/components/password-input';
import InputError from '@/shared/components/input-error';
import { Button } from '@/shared/components/ui/button';
import { Checkbox } from '@/shared/components/ui/checkbox';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/shared/components/ui/select';
import type { Docente } from '../types/docente';

type Props = {
    docente?: Docente | null;
    canSubmit: boolean;
    onSuccess: () => void;
};

export default function DocenteForm({ docente, canSubmit, onSuccess }: Props) {
    const { data, setData, post, put, processing, errors } = useForm({
        ci: docente?.ci ?? '',
        nombre: docente?.nombre ?? '',
        apellido: docente?.apellido ?? '',
        username: docente?.username ?? '',
        correo: docente?.correo ?? '',
        password: '',
        password_confirmation: '',
        telefono: docente?.telefono ?? '',
        sexo: docente?.sexo ?? 'O',
        estado_acceso: docente?.estado_acceso ?? 'HABILITADO',
        usuario_activo: docente?.usuario_activo ?? true,
        profesional_area: docente?.profesional_area ?? false,
        maestria: docente?.maestria ?? false,
        diplomado_educacion_superior:
            docente?.diplomado_educacion_superior ?? false,
        contratado: docente?.contratado ?? false,
        activo: docente?.activo ?? true,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const options = { preserveScroll: true, onSuccess };

        if (docente) {
            put(`/academico/docentes/${docente.id_docente}`, options);
            return;
        }

        post('/academico/docentes', options);
    };

    return (
        <form onSubmit={submit} className="space-y-5">
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="ci">CI</Label>
                    <Input
                        id="ci"
                        value={data.ci}
                        onChange={(event) => setData('ci', event.target.value)}
                    />
                    <InputError message={errors.ci} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="username">Usuario</Label>
                    <Input
                        id="username"
                        value={data.username}
                        onChange={(event) =>
                            setData('username', event.target.value)
                        }
                    />
                    <InputError message={errors.username} />
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="nombre">Nombre</Label>
                    <Input
                        id="nombre"
                        value={data.nombre}
                        onChange={(event) =>
                            setData('nombre', event.target.value)
                        }
                    />
                    <InputError message={errors.nombre} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="apellido">Apellido</Label>
                    <Input
                        id="apellido"
                        value={data.apellido}
                        onChange={(event) =>
                            setData('apellido', event.target.value)
                        }
                    />
                    <InputError message={errors.apellido} />
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="correo">Correo</Label>
                    <Input
                        id="correo"
                        value={data.correo}
                        onChange={(event) =>
                            setData('correo', event.target.value)
                        }
                    />
                    <InputError message={errors.correo} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="telefono">Teléfono</Label>
                    <Input
                        id="telefono"
                        value={data.telefono}
                        onChange={(event) =>
                            setData('telefono', event.target.value)
                        }
                    />
                    <InputError message={errors.telefono} />
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label>Sexo</Label>
                    <Select
                        value={data.sexo}
                        onValueChange={(value) =>
                            setData('sexo', value as 'M' | 'F' | 'O')
                        }
                    >
                        <SelectTrigger className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="M">Masculino</SelectItem>
                            <SelectItem value="F">Femenino</SelectItem>
                            <SelectItem value="O">Otro</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.sexo} />
                </div>
                {docente && (
                    <div className="grid gap-2">
                        <Label>Estado de acceso</Label>
                        <Select
                            value={data.estado_acceso}
                            onValueChange={(value) =>
                                setData(
                                    'estado_acceso',
                                    value as
                                        | 'HABILITADO'
                                        | 'BLOQUEADO'
                                        | 'SUSPENDIDO',
                                )
                            }
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="HABILITADO">
                                    Habilitado
                                </SelectItem>
                                <SelectItem value="BLOQUEADO">
                                    Bloqueado
                                </SelectItem>
                                <SelectItem value="SUSPENDIDO">
                                    Suspendido
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.estado_acceso} />
                    </div>
                )}
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="password">
                        {docente ? 'Nueva contraseña' : 'Contraseña'}
                    </Label>
                    <PasswordInput
                        id="password"
                        value={data.password}
                        onChange={(event) =>
                            setData('password', event.target.value)
                        }
                    />
                    <InputError message={errors.password} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="password_confirmation">
                        Confirmar contraseña
                    </Label>
                    <PasswordInput
                        id="password_confirmation"
                        value={data.password_confirmation}
                        onChange={(event) =>
                            setData(
                                'password_confirmation',
                                event.target.value,
                            )
                        }
                    />
                </div>
            </div>

            <div className="grid gap-3 sm:grid-cols-2">
                <Label className="flex items-center gap-3 rounded-md border p-3">
                    <Checkbox
                        checked={data.profesional_area}
                        onCheckedChange={(checked) =>
                            setData('profesional_area', checked === true)
                        }
                    />
                    Profesional en el área
                </Label>
                <Label className="flex items-center gap-3 rounded-md border p-3">
                    <Checkbox
                        checked={data.maestria}
                        onCheckedChange={(checked) =>
                            setData('maestria', checked === true)
                        }
                    />
                    Maestría
                </Label>
                <Label className="flex items-center gap-3 rounded-md border p-3">
                    <Checkbox
                        checked={data.diplomado_educacion_superior}
                        onCheckedChange={(checked) =>
                            setData(
                                'diplomado_educacion_superior',
                                checked === true,
                            )
                        }
                    />
                    Diplomado en educación superior
                </Label>
                <Label className="flex items-center gap-3 rounded-md border p-3">
                    <Checkbox
                        checked={data.contratado}
                        onCheckedChange={(checked) =>
                            setData('contratado', checked === true)
                        }
                    />
                    Docente contratado
                </Label>
                {docente && (
                    <>
                        <Label className="flex items-center gap-3 rounded-md border p-3">
                            <Checkbox
                                checked={data.activo}
                                onCheckedChange={(checked) =>
                                    setData('activo', checked === true)
                                }
                            />
                            Perfil docente activo
                        </Label>
                        <Label className="flex items-center gap-3 rounded-md border p-3">
                            <Checkbox
                                checked={data.usuario_activo}
                                onCheckedChange={(checked) =>
                                    setData('usuario_activo', checked === true)
                                }
                            />
                            Usuario activo
                        </Label>
                    </>
                )}
            </div>
            <InputError message={errors.contratado} />

            <div className="flex justify-end gap-2">
                <Button
                    type="button"
                    variant="outline"
                    onClick={onSuccess}
                    disabled={processing}
                >
                    Cancelar
                </Button>
                <Button
                    type="submit"
                    disabled={processing || !canSubmit}
                    className="bg-[#e30613] text-white hover:bg-[#bb0710]"
                >
                    {docente ? 'Guardar cambios' : 'Crear docente'}
                </Button>
            </div>
        </form>
    );
}
