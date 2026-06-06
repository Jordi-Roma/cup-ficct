import { useForm } from '@inertiajs/react';
import InputError from '@/shared/components/input-error';
import { Button } from '@/shared/components/ui/button';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shared/components/ui/select';

const turnos = [
    { value: 'MANANA', label: 'Mañana' },
    { value: 'TARDE', label: 'Tarde' },
    { value: 'NOCHE', label: 'Noche' },
];

export default function HorarioForm({ horario, canSubmit, onSuccess }) {
    const { data, setData, post, put, processing, errors } = useForm({
        turno: horario?.turno ?? '',
        hora_inicio: horario?.hora_inicio ?? '',
        hora_fin: horario?.hora_fin ?? '',
        activo: horario?.activo ?? true,
    });

    const submit = (event) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess };

        if (horario) {
            put(`/academico/horarios/${horario.id_horario}`, options);
            return;
        }

        post('/academico/horarios', options);
    };

    return (
        <form onSubmit={submit} className="space-y-5">
            <div className="grid gap-2">
                <Label>Turno</Label>
                <Select value={data.turno} onValueChange={(value) => setData('turno', value)}>
                    <SelectTrigger className="w-full">
                        <SelectValue placeholder="Selecciona turno" />
                    </SelectTrigger>
                    <SelectContent>
                        {turnos.map((turno) => (
                            <SelectItem key={turno.value} value={turno.value}>
                                {turno.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.turno} />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="hora_inicio">Hora inicio</Label>
                    <Input
                        id="hora_inicio"
                        type="time"
                        value={data.hora_inicio}
                        onChange={(event) => setData('hora_inicio', event.target.value)}
                    />
                    <InputError message={errors.hora_inicio} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="hora_fin">Hora fin</Label>
                    <Input
                        id="hora_fin"
                        type="time"
                        value={data.hora_fin}
                        onChange={(event) => setData('hora_fin', event.target.value)}
                    />
                    <InputError message={errors.hora_fin} />
                </div>
            </div>

            <InputError message={errors.horario} />

            <div className="flex justify-end gap-2">
                <Button type="button" variant="outline" onClick={onSuccess} disabled={processing}>
                    Cancelar
                </Button>
                <Button
                    type="submit"
                    disabled={processing || !canSubmit}
                    className="bg-[#0D2B85] text-white hover:bg-[#0a2270]"
                >
                    {horario ? 'Guardar cambios' : 'Crear horario'}
                </Button>
            </div>
        </form>
    );
}
