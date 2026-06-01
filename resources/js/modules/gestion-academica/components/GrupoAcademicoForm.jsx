import { useForm } from '@inertiajs/react';
import InputError from '@/shared/components/input-error';
import { Button } from '@/shared/components/ui/button';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';
export default function GrupoAcademicoForm({ grupo, canSubmit, onSuccess, }) {
    const { data, setData, post, put, processing, errors } = useForm({
        nombre: grupo?.nombre ?? '',
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
                <Label htmlFor="nombre">Nombre del grupo</Label>
                <Input id="nombre" value={data.nombre} onChange={(event) => setData('nombre', event.target.value)} placeholder="Grupo A"/>
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
                <Button type="submit" disabled={processing || !canSubmit} className="bg-[#e30613] text-white hover:bg-[#bb0710]">
                    {grupo ? 'Guardar cambios' : 'Crear grupo'}
                </Button>
            </div>
        </form>);
}
