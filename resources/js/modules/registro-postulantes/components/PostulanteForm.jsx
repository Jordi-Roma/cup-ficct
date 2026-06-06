import { useForm } from '@inertiajs/react';
import InputError from '@/shared/components/input-error';
import { Button } from '@/shared/components/ui/button';
import { Checkbox } from '@/shared/components/ui/checkbox';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue, } from '@/shared/components/ui/select';

const turnos = [
    { value: 'MANANA', label: 'Mañana' },
    { value: 'TARDE', label: 'Tarde' },
    { value: 'NOCHE', label: 'Noche' },
];

export default function PostulanteForm({ postulante, carreras, canSubmit, onSuccess, }) {
    const { data, setData, put, processing, errors } = useForm({
        correo: postulante.correo ?? '',
        telefono: postulante.telefono ?? '',
        fecha_nacimiento: postulante.fecha_nacimiento ?? '',
        direccion: postulante.direccion ?? '',
        colegio_procedencia: postulante.colegio_procedencia ?? '',
        ciudad: postulante.ciudad ?? '',
        documentacion_completa: postulante.documentacion_completa,
        id_carrera_opcion1: postulante.postulacion?.carrera_opcion1?.id_carrera?.toString() ??
            '',
        id_carrera_opcion2: postulante.postulacion?.carrera_opcion2?.id_carrera?.toString() ??
            '',
        turno_preferido: postulante.postulacion?.turno_preferido ?? '',
    });
    const submit = (event) => {
        event.preventDefault();
        put(`/postulantes/${postulante.id_postulante}`, {
            preserveScroll: true,
            onSuccess,
        });
    };
    return (<form onSubmit={submit} className="space-y-5">
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="correo">Correo</Label>
                    <Input id="correo" value={data.correo} onChange={(event) => setData('correo', event.target.value)}/>
                    <InputError message={errors.correo}/>
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="telefono">Teléfono</Label>
                    <Input id="telefono" value={data.telefono} onChange={(event) => setData('telefono', event.target.value)}/>
                    <InputError message={errors.telefono}/>
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="fecha_nacimiento">
                        Fecha de nacimiento
                    </Label>
                    <Input id="fecha_nacimiento" type="date" value={data.fecha_nacimiento} onChange={(event) => setData('fecha_nacimiento', event.target.value)}/>
                    <InputError message={errors.fecha_nacimiento}/>
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="ciudad">Ciudad</Label>
                    <Input id="ciudad" value={data.ciudad} onChange={(event) => setData('ciudad', event.target.value)}/>
                    <InputError message={errors.ciudad}/>
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="direccion">Dirección</Label>
                <Input id="direccion" value={data.direccion} onChange={(event) => setData('direccion', event.target.value)}/>
                <InputError message={errors.direccion}/>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="colegio_procedencia">
                    Colegio de procedencia
                </Label>
                <Input id="colegio_procedencia" value={data.colegio_procedencia} onChange={(event) => setData('colegio_procedencia', event.target.value)}/>
                <InputError message={errors.colegio_procedencia}/>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label>Carrera opción 1</Label>
                    <Select value={data.id_carrera_opcion1} onValueChange={(value) => setData('id_carrera_opcion1', value)}>
                        <SelectTrigger className="w-full">
                            <SelectValue placeholder="Selecciona carrera"/>
                        </SelectTrigger>
                        <SelectContent>
                            {carreras.map((carrera) => (<SelectItem key={carrera.id_carrera} value={carrera.id_carrera.toString()}>
                                    {carrera.nombre}
                                </SelectItem>))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.id_carrera_opcion1}/>
                </div>
                <div className="grid gap-2">
                    <Label>Carrera opción 2</Label>
                    <Select value={data.id_carrera_opcion2 || 'none'} onValueChange={(value) => setData('id_carrera_opcion2', value === 'none' ? '' : value)}>
                        <SelectTrigger className="w-full">
                            <SelectValue placeholder="Opcional"/>
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="none">Sin segunda opción</SelectItem>
                            {carreras.map((carrera) => (<SelectItem key={carrera.id_carrera} value={carrera.id_carrera.toString()}>
                                    {carrera.nombre}
                                </SelectItem>))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.id_carrera_opcion2}/>
                </div>
            </div>

            <div className="grid gap-2">
                <Label>Turno preferido</Label>
                <Select value={data.turno_preferido} onValueChange={(value) => setData('turno_preferido', value)}>
                    <SelectTrigger className="w-full">
                        <SelectValue placeholder="Selecciona turno"/>
                    </SelectTrigger>
                    <SelectContent>
                        {turnos.map((turno) => (
                            <SelectItem key={turno.value} value={turno.value}>
                                {turno.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.turno_preferido}/>
            </div>

            <Label className="flex items-center gap-3 rounded-md border p-3">
                <Checkbox checked={data.documentacion_completa} onCheckedChange={(checked) => setData('documentacion_completa', checked === true)}/>
                Requisitos presentados verificados
            </Label>
            <InputError message={errors.documentacion_completa}/>

            <div className="flex justify-end gap-2">
                <Button type="button" variant="outline" onClick={onSuccess} disabled={processing}>
                    Cancelar
                </Button>
                <Button type="submit" disabled={processing || !canSubmit} className="bg-[#0D2B85] text-white hover:bg-[#0a2270]">
                    Guardar cambios
                </Button>
            </div>
        </form>);
}
