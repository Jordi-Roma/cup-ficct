import { useForm } from '@inertiajs/react';
import InputError from '@/shared/components/input-error';
import { Button } from '@/shared/components/ui/button';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';

export default function AulaForm({ aula, canSubmit, onSuccess }) {
    const { data, setData, post, put, processing, errors } = useForm({
        nombre: aula?.nombre ?? '',
        capacidad: aula?.capacidad ?? 70,
        activo: aula?.activo ?? true,
    });

    const submit = (event) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess };

        if (aula) {
            put(`/academico/aulas/${aula.id_aula}`, options);
            return;
        }

        post('/academico/aulas', options);
    };

    return (
        <form onSubmit={submit} className="space-y-5">
            <div className="grid gap-2">
                <Label htmlFor="nombre">Nombre del aula</Label>
                <Input
                    id="nombre"
                    value={data.nombre}
                    onChange={(event) => setData('nombre', event.target.value)}
                    placeholder="Aula 1"
                />
                <InputError message={errors.nombre} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="capacidad">Capacidad</Label>
                <Input
                    id="capacidad"
                    type="number"
                    min="1"
                    value={data.capacidad}
                    onChange={(event) => setData('capacidad', event.target.value)}
                    placeholder="70"
                />
                <InputError message={errors.capacidad} />
            </div>

            <InputError message={errors.aula} />

            <div className="flex justify-end gap-2">
                <Button type="button" variant="outline" onClick={onSuccess} disabled={processing}>
                    Cancelar
                </Button>
                <Button
                    type="submit"
                    disabled={processing || !canSubmit}
                    className="bg-[#e30613] text-white hover:bg-[#bb0710]"
                >
                    {aula ? 'Guardar cambios' : 'Crear aula'}
                </Button>
            </div>
        </form>
    );
}
