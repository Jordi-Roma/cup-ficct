import { useForm } from '@inertiajs/react';
import InputError from '@/shared/components/input-error';
import { Button } from '@/shared/components/ui/button';
import { Checkbox } from '@/shared/components/ui/checkbox';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';
export default function MateriaCupForm({ materia, canSubmit, onSuccess, }) {
    const { data, setData, post, put, processing, errors } = useForm({
        nombre: materia?.nombre ?? '',
        activo: materia?.activo ?? true,
    });
    const submit = (event) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess };
        if (materia) {
            put(`/academico/materias/${materia.id_materia}`, options);
            return;
        }
        post('/academico/materias', options);
    };
    return (<form onSubmit={submit} className="space-y-5">
            <div className="grid gap-2">
                <Label htmlFor="nombre">Nombre de la materia</Label>
                <Input id="nombre" value={data.nombre} onChange={(event) => setData('nombre', event.target.value)} placeholder="Computación"/>
                <InputError message={errors.nombre}/>
            </div>

            <Label className="flex items-center gap-3 rounded-md border p-3">
                <Checkbox checked={data.activo} onCheckedChange={(checked) => setData('activo', checked === true)}/>
                Materia activa
            </Label>

            <div className="flex justify-end gap-2">
                <Button type="button" variant="outline" onClick={onSuccess} disabled={processing}>
                    Cancelar
                </Button>
                <Button type="submit" disabled={processing || !canSubmit} className="bg-[#0D2B85] text-white hover:bg-[#0a2270]">
                    {materia ? 'Guardar cambios' : 'Crear materia'}
                </Button>
            </div>
        </form>);
}
