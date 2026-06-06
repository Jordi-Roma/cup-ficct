import { useForm } from '@inertiajs/react';
import InputError from '@/shared/components/input-error';
import { Button } from '@/shared/components/ui/button';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shared/components/ui/select';

const turnos = [
    { value: 'MANANA', label: 'Mañana', ejemplo: 'M001' },
    { value: 'TARDE', label: 'Tarde', ejemplo: 'T001' },
    { value: 'NOCHE', label: 'Noche', ejemplo: 'N001' },
];

export default function GrupoAcademicoForm({ grupo, canSubmit, onSuccess, }) {
    const { data, setData, post, put, processing, errors } = useForm({
        nombre: grupo?.nombre ?? '',
        turno: grupo?.turno ?? '',
        capacidad_maxima: grupo?.capacidad_maxima ?? 70,
    });
    const submit = (event) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess };
        if (grupo) {
            put(`/academico/grupos/${grupo.id_grupo}`, options);
            return;
        }
        post('/academico/grupos', options);
    };
    return (<form onSubmit={submit} className="space-y-5">
            <div className="grid gap-2">
                <Label>Turno</Label>
                <Select value={data.turno} onValueChange={(value) => setData('turno', value)}>
                    <SelectTrigger className="w-full">
                        <SelectValue placeholder="Selecciona turno"/>
                    </SelectTrigger>
                    <SelectContent>
                        {turnos.map((turno) => (
                            <SelectItem key={turno.value} value={turno.value}>
                                {turno.label} · sugerido {turno.ejemplo}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.turno}/>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="nombre">Nombre del grupo</Label>
                <Input id="nombre" value={data.nombre} onChange={(event) => setData('nombre', event.target.value)} placeholder="M001"/>
                <InputError message={errors.nombre}/>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="capacidad_maxima">Capacidad máxima</Label>
                <Input id="capacidad_maxima" type="number" min={1} max={70} value={data.capacidad_maxima} onChange={(event) => setData('capacidad_maxima', Number(event.target.value))}/>
                <InputError message={errors.capacidad_maxima}/>
            </div>

            <div className="flex justify-end gap-2">
                <Button type="button" variant="outline" onClick={onSuccess} disabled={processing}>
                    Cancelar
                </Button>
                <Button type="submit" disabled={processing || !canSubmit} className="bg-[#0D2B85] text-white hover:bg-[#0a2270]">
                    {grupo ? 'Guardar cambios' : 'Crear grupo'}
                </Button>
            </div>
        </form>);
}
