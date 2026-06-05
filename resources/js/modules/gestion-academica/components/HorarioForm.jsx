import { useForm } from '@inertiajs/react';
import InputError from '@/shared/components/input-error';
import { Button } from '@/shared/components/ui/button';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shared/components/ui/select';

const dias = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO'];

export default function HorarioForm({ horario, canSubmit, onSuccess }) {
    const { data, setData, post, put, processing, errors } = useForm({
        dia: horario?.dia ?? '',
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
                <Label>Dia</Label>
                <Select value={data.dia} onValueChange={(value) => setData('dia', value)}>
                    <SelectTrigger className="w-full">
                        <SelectValue placeholder="Selecciona un dia" />
                    </SelectTrigger>
                    <SelectContent>
                        {dias.map((dia) => (
                            <SelectItem key={dia} value={dia}>
                                {dia}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.dia} />
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
                    className="bg-[#e30613] text-white hover:bg-[#bb0710]"
                >
                    {horario ? 'Guardar cambios' : 'Crear horario'}
                </Button>
            </div>
        </form>
    );
}
