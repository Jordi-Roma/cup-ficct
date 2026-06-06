import { Badge } from '@/shared/components/ui/badge';
export default function PostulanteDetail({ postulante }) {
    const detailRows = [
        ['CI', postulante.ci],
        ['Usuario', postulante.username],
        ['Correo', postulante.correo],
        ['Teléfono', postulante.telefono ?? 'Sin teléfono'],
        ['Sexo', postulante.sexo],
        ['Nacimiento', postulante.fecha_nacimiento],
        ['Ciudad', postulante.ciudad ?? 'Sin ciudad'],
        ['Dirección', postulante.direccion ?? 'Sin dirección'],
        [
            'Colegio',
            postulante.colegio_procedencia ?? 'Sin colegio registrado',
        ],
        [
            'Carrera opción 1',
            postulante.postulacion?.carrera_opcion1?.nombre ?? 'Sin carrera',
        ],
        [
            'Carrera opción 2',
            postulante.postulacion?.carrera_opcion2?.nombre ?? 'Sin carrera',
        ],
        [
            'Grupo',
            postulante.postulacion?.grupo?.nombre ?? 'Sin grupo asignado',
        ],
        [
            'Estado admisión',
            postulante.postulacion?.estado_admision ?? 'Sin postulación',
        ],
    ];
    return (<div className="space-y-5">
            <div>
                <h3 className="text-lg font-semibold text-foreground">
                    {postulante.nombre_completo}
                </h3>
                <div className="mt-2 flex flex-wrap gap-2">
                    <Badge variant={postulante.documentacion_completa
            ? 'default'
            : 'secondary'}>
                        {postulante.documentacion_completa
            ? 'Documentación completa'
            : 'Documentación pendiente'}
                    </Badge>
                    <Badge variant={postulante.activo ? 'default' : 'secondary'}>
                        {postulante.activo ? 'Usuario activo' : 'Usuario inactivo'}
                    </Badge>
                </div>
            </div>

            <div className="grid gap-3 sm:grid-cols-2">
                {detailRows.map(([label, value]) => (<div key={label} className="rounded-md border p-3">
                        <div className="text-xs text-muted-foreground">
                            {label}
                        </div>
                        <div className="mt-1 font-medium">{value}</div>
                    </div>))}
            </div>
        </div>);
}
