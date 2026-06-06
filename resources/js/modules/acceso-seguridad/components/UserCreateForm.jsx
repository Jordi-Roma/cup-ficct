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
const turnos = [
    { value: 'MANANA', label: 'Mañana' },
    { value: 'TARDE', label: 'Tarde' },
    { value: 'NOCHE', label: 'Noche' },
];

export default function UserCreateForm({ materias, gestiones, carreras, canSubmit, onSuccess }) {
    const { data, setData, post, processing, errors } = useForm({
        tipo_usuario: 'DOCENTE',
        ci: '',
        nombre: '',
        apellido: '',
        username: '',
        correo: '',
        password: '',
        password_confirmation: '',
        telefono: '',
        sexo: 'O',
        estado_acceso: 'HABILITADO',
        activo: true,
        maestria_educacion_superior: false,
        contratado: false,
        habilitaciones: { PROFESIONAL_AREA: [], DIPLOMADO: [], MAESTRIA: [] },
        fecha_nacimiento: '',
        direccion: '',
        colegio_procedencia: '',
        ciudad: '',
        documentacion_completa: false,
        id_gestion: gestiones?.find((gestion) => gestion.activo)?.id_gestion?.toString() ?? '',
        turno_preferido: '',
        id_carrera_opcion1: '',
        id_carrera_opcion2: '',
    });

    const submit = (event) => {
        event.preventDefault();
        post('/admin/usuarios', { preserveScroll: true, onSuccess });
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
            <div className="grid gap-2">
                <Label>Tipo de usuario</Label>
                <Select value={data.tipo_usuario} onValueChange={(value) => setData('tipo_usuario', value)}>
                    <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="POSTULANTE">Postulante</SelectItem>
                        <SelectItem value="DOCENTE">Docente</SelectItem>
                        <SelectItem value="COORDINADOR_ACADEMICO">Coordinador academico</SelectItem>
                        <SelectItem value="ADMINISTRADOR">Administrador</SelectItem>
                    </SelectContent>
                </Select>
                <InputError message={errors.tipo_usuario} />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <Field label="CI" value={data.ci} onChange={(value) => setData('ci', value)} error={errors.ci} />
                <Field label="Usuario" value={data.username} onChange={(value) => setData('username', value)} error={errors.username} />
                <Field label="Nombre" value={data.nombre} onChange={(value) => setData('nombre', value)} error={errors.nombre} />
                <Field label="Apellido" value={data.apellido} onChange={(value) => setData('apellido', value)} error={errors.apellido} />
                <Field label="Correo" value={data.correo} onChange={(value) => setData('correo', value)} error={errors.correo} />
                <Field label="Telefono" value={data.telefono} onChange={(value) => setData('telefono', value)} error={errors.telefono} />
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
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label>Contraseña</Label>
                    <PasswordInput value={data.password} onChange={(event) => setData('password', event.target.value)} />
                    <InputError message={errors.password} />
                </div>
                <div className="grid gap-2">
                    <Label>Confirmar Contraseña</Label>
                    <PasswordInput value={data.password_confirmation} onChange={(event) => setData('password_confirmation', event.target.value)} />
                </div>
            </div>

            {data.tipo_usuario === 'DOCENTE' && (
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
                    <InputError message={errors.maestria_educacion_superior} />
                    <InputError message={errors.habilitaciones} />
                    <InputError message={errors.contratado} />
                </div>
            )}

            {data.tipo_usuario === 'POSTULANTE' && (
                <div className="grid gap-4 rounded-md border p-4 sm:grid-cols-2">
                    <Field label="Fecha de nacimiento" type="date" value={data.fecha_nacimiento} onChange={(value) => setData('fecha_nacimiento', value)} error={errors.fecha_nacimiento} />
                    <Field label="Ciudad" value={data.ciudad} onChange={(value) => setData('ciudad', value)} error={errors.ciudad} />
                    <Field label="Colegio de procedencia" value={data.colegio_procedencia} onChange={(value) => setData('colegio_procedencia', value)} error={errors.colegio_procedencia} />
                    <Field label="Direccion" value={data.direccion} onChange={(value) => setData('direccion', value)} error={errors.direccion} />
                    <SelectField label="Gestion" value={data.id_gestion} onChange={(value) => setData('id_gestion', value)} items={gestiones} idKey="id_gestion" labelKey="nombre" error={errors.id_gestion} />
                    <SelectField label="Turno preferido" value={data.turno_preferido} onChange={(value) => setData('turno_preferido', value)} items={turnos} idKey="value" labelKey="label" error={errors.turno_preferido} />
                    <SelectField label="Carrera opcion 1" value={data.id_carrera_opcion1} onChange={(value) => setData('id_carrera_opcion1', value)} items={carreras} idKey="id_carrera" labelKey="nombre" error={errors.id_carrera_opcion1} />
                    <SelectField label="Carrera opcion 2" value={data.id_carrera_opcion2 || 'none'} onChange={(value) => setData('id_carrera_opcion2', value === 'none' ? '' : value)} items={[{ id_carrera: 'none', nombre: 'Sin segunda opcion' }, ...carreras]} idKey="id_carrera" labelKey="nombre" error={errors.id_carrera_opcion2} />
                    <Label className="flex items-center gap-3">
                        <Checkbox checked={data.documentacion_completa} onCheckedChange={(checked) => setData('documentacion_completa', checked === true)} />
                        Documentacion completa
                    </Label>
                </div>
            )}

            <div className="flex justify-end gap-2">
                <Button type="button" variant="outline" onClick={onSuccess} disabled={processing}>Cancelar</Button>
                <Button type="submit" disabled={processing || !canSubmit} className="bg-[#0D2B85] text-white hover:bg-[#0a2270]">Crear usuario</Button>
            </div>
        </form>
    );
}

function Field({ label, value, onChange, error, type = 'text' }) {
    return (
        <div className="grid gap-2">
            <Label>{label}</Label>
            <Input type={type} value={value} onChange={(event) => onChange(event.target.value)} />
            <InputError message={error} />
        </div>
    );
}

function SelectField({ label, value, onChange, items, idKey, labelKey, error }) {
    return (
        <div className="grid gap-2">
            <Label>{label}</Label>
            <Select value={value ? value.toString() : ''} onValueChange={onChange}>
                <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                <SelectContent>
                    {items.map((item) => <SelectItem key={item[idKey]} value={item[idKey].toString()}>{item[labelKey]}</SelectItem>)}
                </SelectContent>
            </Select>
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
