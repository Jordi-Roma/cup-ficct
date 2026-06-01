import { useForm } from '@inertiajs/react';
import InputError from '@/shared/components/input-error';
import { Button } from '@/shared/components/ui/button';
import { Label } from '@/shared/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue, } from '@/shared/components/ui/select';
export default function AsignacionAcademicaForm({ asignacion, options, canSubmit, onSuccess, }) {
    const { data, setData, post, put, processing, errors } = useForm({
        id_grupo: asignacion?.grupo.id_grupo.toString() ?? '',
        id_materia: asignacion?.materia.id_materia.toString() ?? '',
        id_docente: asignacion?.docente.id_docente.toString() ?? '',
        id_aula: asignacion?.aula.id_aula.toString() ?? '',
        id_horario: asignacion?.horario.id_horario.toString() ?? '',
        activo: asignacion?.activo ?? true,
    });
    const submit = (event) => {
        event.preventDefault();
        const requestOptions = {
            preserveScroll: true,
            onSuccess,
        };
        if (asignacion) {
            put(`/academico/asignaciones/${asignacion.id_asignacion}`, requestOptions);
            return;
        }
        post('/academico/asignaciones', requestOptions);
    };
    return (<form onSubmit={submit} className="space-y-5">
            <div className="grid gap-4 sm:grid-cols-2">
                <SelectField label="Grupo" value={data.id_grupo} onChange={(value) => setData('id_grupo', value)} error={errors.id_grupo} placeholder="Selecciona un grupo" items={options.grupos.map((grupo) => ({
            value: grupo.id_grupo.toString(),
            label: `${grupo.nombre} (${grupo.capacidad_maxima})`,
        }))}/>
                <SelectField label="Materia" value={data.id_materia} onChange={(value) => setData('id_materia', value)} error={errors.id_materia} placeholder="Selecciona una materia" items={options.materias.map((materia) => ({
            value: materia.id_materia.toString(),
            label: materia.nombre,
        }))}/>
                <SelectField label="Docente" value={data.id_docente} onChange={(value) => setData('id_docente', value)} error={errors.id_docente} placeholder="Selecciona un docente" items={options.docentes.map((docente) => ({
            value: docente.id_docente.toString(),
            label: docente.nombre_completo,
        }))}/>
                <SelectField label="Aula" value={data.id_aula} onChange={(value) => setData('id_aula', value)} error={errors.id_aula} placeholder="Selecciona un aula" items={options.aulas.map((aula) => ({
            value: aula.id_aula.toString(),
            label: `${aula.nombre} (${aula.capacidad})`,
        }))}/>
                <div className="sm:col-span-2">
                    <SelectField label="Horario" value={data.id_horario} onChange={(value) => setData('id_horario', value)} error={errors.id_horario} placeholder="Selecciona un horario" items={options.horarios.map((horario) => ({
            value: horario.id_horario.toString(),
            label: `${horario.dia} ${horario.hora_inicio}-${horario.hora_fin}`,
        }))}/>
                </div>
            </div>
            <InputError message={errors.asignacion}/>

            <div className="flex justify-end gap-2">
                <Button type="button" variant="outline" onClick={onSuccess} disabled={processing}>
                    Cancelar
                </Button>
                <Button type="submit" disabled={processing || !canSubmit} className="bg-[#e30613] text-white hover:bg-[#bb0710]">
                    {asignacion ? 'Guardar cambios' : 'Crear asignacion'}
                </Button>
            </div>
        </form>);
}
function SelectField({ label, value, onChange, error, placeholder, items, }) {
    return (<div className="grid gap-2">
            <Label>{label}</Label>
            <Select value={value} onValueChange={onChange}>
                <SelectTrigger className="w-full">
                    <SelectValue placeholder={placeholder}/>
                </SelectTrigger>
                <SelectContent>
                    {items.map((item) => (<SelectItem key={item.value} value={item.value}>
                            {item.label}
                        </SelectItem>))}
                </SelectContent>
            </Select>
            <InputError message={error}/>
        </div>);
}
