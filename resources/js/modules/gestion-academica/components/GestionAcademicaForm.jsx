import { useForm } from '@inertiajs/react';
import InputError from '@/shared/components/input-error';
import { Button } from '@/shared/components/ui/button';
import { Checkbox } from '@/shared/components/ui/checkbox';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';

export default function GestionAcademicaForm({ gestion, canSubmit, onSuccess }) {
    const { data, setData, post, put, processing, errors } = useForm({
        nombre: gestion?.nombre ?? '',
        fecha_inicio: gestion?.fecha_inicio ?? '',
        fecha_fin: gestion?.fecha_fin ?? '',
        activo: gestion?.activo ?? false,
    });

    const submit = (event) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess };

        if (gestion) {
            put(`/academico/gestiones/${gestion.id_gestion}`, options);
            return;
        }

        post('/academico/gestiones', options);
    };

    return (
        <form onSubmit={submit} className="space-y-5">
            <div className="grid gap-2">
                <Label htmlFor="nombre">Nombre de la gestión</Label>
                <Input
                    id="nombre"
                    value={data.nombre}
                    onChange={(event) => setData('nombre', event.target.value)}
                    placeholder="1-2026"
                />
                <InputError message={errors.nombre} />
            </div>

            <div className="grid gap-4 md:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="fecha_inicio">Fecha inicio</Label>
                    <Input
                        id="fecha_inicio"
                        type="date"
                        value={data.fecha_inicio}
                        onChange={(event) => setData('fecha_inicio', event.target.value)}
                    />
                    <InputError message={errors.fecha_inicio} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="fecha_fin">Fecha fin</Label>
                    <Input
                        id="fecha_fin"
                        type="date"
                        value={data.fecha_fin}
                        onChange={(event) => setData('fecha_fin', event.target.value)}
                    />
                    <InputError message={errors.fecha_fin} />
                </div>
            </div>

            <label className="flex items-center gap-3 rounded-md border p-3 text-sm">
                <Checkbox checked={data.activo} onCheckedChange={(checked) => setData('activo', Boolean(checked))} />
                Marcar como gestión activa
            </label>

            <InputError message={errors.activo} />
            <InputError message={errors.gestion} />

            <div className="flex justify-end gap-2">
                <Button type="button" variant="outline" onClick={onSuccess} disabled={processing}>
                    Cancelar
                </Button>
                <Button
                    type="submit"
                    disabled={processing || !canSubmit}
                    className="bg-[#0D2B85] text-white hover:bg-[#0a2270]"
                >
                    {gestion ? 'Guardar cambios' : 'Crear gestión'}
                </Button>
            </div>
        </form>
    );
}
