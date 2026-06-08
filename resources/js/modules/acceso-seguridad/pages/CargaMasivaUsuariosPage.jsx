import { Head, useForm } from '@inertiajs/react';
import { FileUp, LoaderCircle } from 'lucide-react';
import InputError from '@/shared/components/input-error';
import { Badge } from '@/shared/components/ui/badge';
import { Button } from '@/shared/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/shared/components/ui/card';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shared/components/ui/select';

const TIPOS = {
    POSTULANTE: {
        label: 'Postulantes',
        description: 'Crea postulantes habilitados para el CUP sin pasar por pago.',
        columns: [
            'ci',
            'nombre',
            'apellido',
            'correo',
            'telefono',
            'sexo',
            'fecha_nacimiento',
            'direccion',
            'colegio_procedencia',
            'ciudad',
            'carrera_opcion1',
            'carrera_opcion2',
            'turno_preferido',
            'password',
        ],
        example:
            '90000001,Ana,Rojas,ana@example.com,70000001,F,2005-01-10,Av 1,Colegio A,Santa Cruz,Ingenieria de Sistemas,Ingenieria Informatica,MANANA,Cup2026#01',
    },
    DOCENTE: {
        label: 'Docentes',
        description: 'Crea usuarios docentes y habilitaciones por materia si se declaran.',
        columns: [
            'ci',
            'nombre',
            'apellido',
            'correo',
            'telefono',
            'sexo',
            'profesional_area',
            'diplomado',
            'maestria',
            'maestria_educacion_superior',
            'contratado',
            'materias_profesional_area',
            'materias_diplomado',
            'materias_maestria',
            'password',
        ],
        example:
            '80000001,Braulio,Miranda,braulio@example.com,70000002,M,1,0,0,1,1,Matematicas;Fisica,,,Cup2026#02',
    },
    COORDINADOR_ACADEMICO: {
        label: 'Coordinadores academicos',
        description: 'Crea usuarios coordinadores con rol administrativo o coordinador si existe.',
        columns: ['ci', 'nombre', 'apellido', 'correo', 'telefono', 'sexo', 'password'],
        example: '70000001,Carla,Vargas,carla@example.com,70000003,F,Cup2026#03',
    },
    ADMINISTRADOR: {
        label: 'Administradores',
        description: 'Crea usuarios administradores con acceso habilitado.',
        columns: ['ci', 'nombre', 'apellido', 'correo', 'telefono', 'sexo', 'password'],
        example: '60000001,Marco,Rivero,marco@example.com,70000004,M,Cup2026#04',
    },
};

export default function CargaMasivaUsuariosPage({ resultado }) {
    const { data, setData, post, processing, errors } = useForm({
        tipo_usuario: 'POSTULANTE',
        archivo_csv: null,
    });
    const current = TIPOS[data.tipo_usuario];

    const submit = (event) => {
        event.preventDefault();
        post('/admin/carga-masiva', {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Carga masiva de usuarios" />

            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold text-foreground">Carga masiva de usuarios</h1>
                    <p className="text-sm text-muted-foreground">
                        Importa usuarios en lote desde archivos CSV exportados desde Excel. Cada fila debe incluir una
                        contrasena.
                    </p>
                </div>

                <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(320px,420px)]">
                    <Card>
                        <CardHeader>
                            <CardTitle>Importar CSV</CardTitle>
                            <CardDescription>
                                Selecciona el tipo de usuario y sube el archivo correspondiente. No se enviaran credenciales por
                                correo.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-5">
                                <div className="grid gap-2">
                                    <Label>Tipo de usuario</Label>
                                    <Select value={data.tipo_usuario} onValueChange={(value) => setData('tipo_usuario', value)}>
                                        <SelectTrigger className="w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {Object.entries(TIPOS).map(([value, option]) => (
                                                <SelectItem key={value} value={value}>
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.tipo_usuario} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="archivo_csv">Archivo CSV</Label>
                                    <Input
                                        id="archivo_csv"
                                        type="file"
                                        accept=".csv,.txt,text/csv"
                                        onChange={(event) => setData('archivo_csv', event.target.files?.[0] ?? null)}
                                    />
                                    <InputError message={errors.archivo_csv} />
                                </div>

                                <Button type="submit" disabled={processing || !data.archivo_csv}>
                                    {processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : <FileUp className="h-4 w-4" />}
                                    Importar CSV
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>{current.label}</CardTitle>
                            <CardDescription>{current.description}</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <p className="mb-2 text-sm font-medium">Columnas esperadas</p>
                                <p className="mb-3 text-sm text-muted-foreground">
                                    La columna password es obligatoria. Los postulantes importados por administracion quedan
                                    habilitados directamente y no requieren pago.
                                </p>
                                <div className="flex flex-wrap gap-2">
                                    {current.columns.map((column) => (
                                        <Badge key={column} variant="outline">
                                            {column}
                                        </Badge>
                                    ))}
                                </div>
                            </div>

                            <div>
                                <p className="mb-2 text-sm font-medium">Ejemplo</p>
                                <pre className="overflow-x-auto rounded-md border bg-muted p-3 text-xs text-muted-foreground">
                                    {current.columns.join(',')}
                                    {'\n'}
                                    {current.example}
                                </pre>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {resultado && <ResultadoCarga resultado={resultado} />}
            </div>
        </>
    );
}

function ResultadoCarga({ resultado }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Resultado de la carga</CardTitle>
                <CardDescription>
                    {resultado.creados} creados, {resultado.omitidos} omitidos de {resultado.total} filas procesadas.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                <div className="grid gap-4 md:grid-cols-4">
                    <Summary label="Tipo" value={resultado.tipo_usuario} />
                    <Summary label="Procesados" value={resultado.total} />
                    <Summary label="Creados" value={resultado.creados} />
                    <Summary label="Omitidos" value={resultado.omitidos} />
                </div>

                <div className="space-y-2">
                    <h2 className="font-semibold">Usuarios creados</h2>
                    <div className="overflow-x-auto rounded-md border">
                        <table className="w-full min-w-[760px] text-sm">
                            <thead className="bg-muted text-left">
                                <tr>
                                    <th className="p-3">Fila</th>
                                    <th className="p-3">CI</th>
                                    <th className="p-3">Nombre</th>
                                    <th className="p-3">Username</th>
                                    <th className="p-3">Contrasena</th>
                                    <th className="p-3">Rol</th>
                                </tr>
                            </thead>
                            <tbody>
                                {resultado.usuarios_creados?.length ? (
                                    resultado.usuarios_creados.map((usuario) => (
                                        <tr key={`${usuario.fila}-${usuario.ci}`} className="border-t">
                                            <td className="p-3">{usuario.fila}</td>
                                            <td className="p-3">{usuario.ci}</td>
                                            <td className="p-3">{usuario.nombre_completo}</td>
                                            <td className="p-3 font-medium">{usuario.username}</td>
                                            <td className="p-3 font-mono">{usuario.password}</td>
                                            <td className="p-3">{usuario.rol}</td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="6" className="p-4 text-center text-muted-foreground">
                                            No se crearon usuarios.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="space-y-2">
                    <h2 className="font-semibold">Errores</h2>
                    {resultado.errores?.length ? (
                        <div className="space-y-2">
                            {resultado.errores.map((error) => (
                                <div key={`${error.fila}-${error.ci ?? 'sin-ci'}`} className="rounded-md border p-3 text-sm">
                                    <span className="font-medium">Fila {error.fila}</span>
                                    {error.ci ? ` | CI: ${error.ci}` : ''}
                                    <ul className="mt-2 list-disc pl-5 text-muted-foreground">
                                        {error.errores.map((message) => (
                                            <li key={message}>{message}</li>
                                        ))}
                                    </ul>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-sm text-muted-foreground">No se registraron errores.</p>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

function Summary({ label, value }) {
    return (
        <div className="rounded-md border p-4">
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className="mt-1 text-lg font-semibold">{value}</p>
        </div>
    );
}
