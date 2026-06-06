import { useForm } from '@inertiajs/react';
import InputError from '@/shared/components/input-error';
import PasswordInput from '@/shared/components/password-input';
import { Button } from '@/shared/components/ui/button';
import { Checkbox } from '@/shared/components/ui/checkbox';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shared/components/ui/select';

const habilitacionTipos = [
    ['PROFESIONAL_AREA', 'Profesional en el area'],
    ['DIPLOMADO', 'Diplomado en'],
    ['MAESTRIA', 'Maestria en'],
];

export default function DocenteForm({ docente, materias = [], canSubmit, onSuccess }) {
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
        diplomado_educacion_superior: docente?.diplomado_educacion_superior ?? false,
        maestria_educacion_superior: docente?.maestria_educacion_superior ?? false,
        habilitaciones: {
            PROFESIONAL_AREA: (docente?.habilitaciones?.PROFESIONAL_AREA ?? []).map((materia) => materia.id_materia),
            DIPLOMADO: (docente?.habilitaciones?.DIPLOMADO ?? []).map((materia) => materia.id_materia),
            MAESTRIA: (docente?.habilitaciones?.MAESTRIA ?? []).map((materia) => materia.id_materia),
        },
        contratado: docente?.contratado ?? false,
        activo: docente?.activo ?? true,
    });

    const submit = (event) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess };
        if (docente) {
            put(`/academico/docentes/${docente.id_docente}`, options);
            return;
        }
        post('/academico/docentes', options);
    };

    const toggleMateria = (tipo, idMateria, checked) => {
        const current = data.habilitaciones[tipo] ?? [];
        setData('habilitaciones', {
            ...data.habilitaciones,
            [tipo]: checked
                ? [...new Set([...current, idMateria])]
                : current.filter((id) => id !== idMateria),
        });
    };

    const hasMateria = Object.values(data.habilitaciones).some((items) => items.length > 0);
    const canContract = data.maestria_educacion_superior && hasMateria;

    return (
        <form onSubmit={submit} className="space-y-5">
            <div className="grid gap-4 sm:grid-cols-2">
                <Field label="CI" value={data.ci} onChange={(value) => setData('ci', value)} error={errors.ci} />
                <Field label="Usuario" value={data.username} onChange={(value) => setData('username', value)} error={errors.username} />
                <Field label="Nombre" value={data.nombre} onChange={(value) => setData('nombre', value)} error={errors.nombre} />
                <Field label="Apellido" value={data.apellido} onChange={(value) => setData('apellido', value)} error={errors.apellido} />
                <Field label="Correo" value={data.correo} onChange={(value) => setData('correo', value)} error={errors.correo} />
                <Field label="Telefono" value={data.telefono} onChange={(value) => setData('telefono', value)} error={errors.telefono} />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label>Sexo</Label>
                    <Select value={data.sexo} onValueChange={(value) => setData('sexo', value)}>
                        <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
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
                        <Select value={data.estado_acceso} onValueChange={(value) => setData('estado_acceso', value)}>
                            <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="HABILITADO">Habilitado</SelectItem>
                                <SelectItem value="BLOQUEADO">Bloqueado</SelectItem>
                                <SelectItem value="SUSPENDIDO">Suspendido</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.estado_acceso} />
                    </div>
                )}
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label>{docente ? 'Nueva contrasena' : 'Contrasena'}</Label>
                    <PasswordInput value={data.password} onChange={(event) => setData('password', event.target.value)} />
                    <InputError message={errors.password} />
                </div>
                <div className="grid gap-2">
                    <Label>Confirmar contrasena</Label>
                    <PasswordInput value={data.password_confirmation} onChange={(event) => setData('password_confirmation', event.target.value)} />
                </div>
            </div>

            <div className="space-y-4 rounded-md border p-4">
                <Label className="flex items-center gap-3">
                    <Checkbox checked={data.maestria_educacion_superior} onCheckedChange={(checked) => setData('maestria_educacion_superior', checked === true)} />
                    Maestria en educacion superior
                </Label>
                {habilitacionTipos.map(([tipo, label]) => (
                    <MateriaChecklist key={tipo} label={label} materias={materias} selected={data.habilitaciones[tipo] ?? []} onChange={(id, checked) => toggleMateria(tipo, id, checked)} />
                ))}
                <Label className="flex items-center gap-3">
                    <Checkbox checked={data.contratado} disabled={!canContract} onCheckedChange={(checked) => setData('contratado', checked === true)} />
                    Docente contratado
                </Label>
                {!canContract && data.contratado && <p className="text-sm text-destructive">Para contratar debe tener maestria en educacion superior y al menos una materia habilitada.</p>}
                <InputError message={errors.maestria_educacion_superior} />
                <InputError message={errors.habilitaciones} />
                <InputError message={errors.contratado} />
            </div>

            {docente && (
                <div className="grid gap-3 sm:grid-cols-2">
                    <Label className="flex items-center gap-3 rounded-md border p-3">
                        <Checkbox checked={data.activo} onCheckedChange={(checked) => setData('activo', checked === true)} />
                        Perfil docente activo
                    </Label>
                    <Label className="flex items-center gap-3 rounded-md border p-3">
                        <Checkbox checked={data.usuario_activo} onCheckedChange={(checked) => setData('usuario_activo', checked === true)} />
                        Usuario activo
                    </Label>
                </div>
            )}

            <div className="flex justify-end gap-2">
                <Button type="button" variant="outline" onClick={onSuccess} disabled={processing}>Cancelar</Button>
                <Button type="submit" disabled={processing || !canSubmit} className="bg-[#0D2B85] text-white hover:bg-[#0a2270]">
                    {docente ? 'Guardar cambios' : 'Crear docente'}
                </Button>
            </div>
        </form>
    );
}

function Field({ label, value, onChange, error }) {
    return (
        <div className="grid gap-2">
            <Label>{label}</Label>
            <Input value={value} onChange={(event) => onChange(event.target.value)} />
            <InputError message={error} />
        </div>
    );
}

function MateriaChecklist({ label, materias, selected, onChange }) {
    return (
        <div className="space-y-2">
            <p className="text-sm font-medium">{label}</p>
            <div className="grid gap-2 sm:grid-cols-2">
                {materias.map((materia) => (
                    <Label key={materia.id_materia} className="flex items-center gap-3 rounded-md border p-3">
                        <Checkbox checked={selected.includes(materia.id_materia)} onCheckedChange={(checked) => onChange(materia.id_materia, checked === true)} />
                        {materia.nombre}
                    </Label>
                ))}
            </div>
        </div>
    );
}
