import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { Download, Search, Eye } from 'lucide-react';
import { Badge } from '@/shared/components/ui/badge';
import { Button } from '@/shared/components/ui/button';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/shared/components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/shared/components/ui/dialog';

export default function BitacoraPage({ logs = { data: [], from: 0, to: 0, total: 0 }, filters = {} }) {
    const { auth } = usePage().props;
    const canExport = auth?.permissions?.includes('bitacora:read') || false;
    
    const [search, setSearch] = useState(filters.search || '');
    const [operacion, setOperacion] = useState(filters.operacion || '');
    const [fechaInicio, setFechaInicio] = useState(filters.fecha_inicio || '');
    const [fechaFin, setFechaFin] = useState(filters.fecha_fin || '');
    
    const [selectedLog, setSelectedLog] = useState(null);

    const applyFilters = () => {
        router.get('/reportes/bitacora', {
            search,
            operacion,
            fecha_inicio: fechaInicio,
            fecha_fin: fechaFin,
        }, { preserveState: true });
    };

    const handleKeyDown = (e) => {
        if (e.key === 'Enter') {
            applyFilters();
        }
    };

    const exportCsv = () => {
        const queryParams = new URLSearchParams({
            search,
            operacion,
            fecha_inicio: fechaInicio,
            fecha_fin: fechaFin,
        }).toString();
        window.location.href = `/reportes/bitacora/export?${queryParams}`;
    };

    return (
        <>
            <Head title="Bitácora del Sistema" />
            
            <div className="space-y-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-[#001f3f]">
                            Bitácora del Sistema
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Registro de auditoría de actividades y cambios realizados por los usuarios.
                        </p>
                    </div>

                    {canExport && (
                        <Button variant="outline" onClick={exportCsv}>
                            <Download className="mr-2 h-4 w-4" />
                            Exportar CSV
                        </Button>
                    )}
                </div>

                <div className="grid gap-4 md:grid-cols-4 items-end">
                    <div className="space-y-2">
                        <Label>Buscar</Label>
                        <Input 
                            placeholder="Usuario, tabla, ID..." 
                            value={search}
                            onChange={e => setSearch(e.target.value)}
                            onKeyDown={handleKeyDown}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Operación</Label>
                        <Select value={operacion} onValueChange={setOperacion}>
                            <SelectTrigger>
                                <SelectValue placeholder="Todas" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">Todas</SelectItem>
                                <SelectItem value="INSERT">INSERT</SelectItem>
                                <SelectItem value="UPDATE">UPDATE</SelectItem>
                                <SelectItem value="DELETE">DELETE</SelectItem>
                                <SelectItem value="LOGIN">LOGIN</SelectItem>
                                <SelectItem value="LOGOUT">LOGOUT</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label>Desde</Label>
                        <Input 
                            type="date" 
                            value={fechaInicio} 
                            onChange={e => setFechaInicio(e.target.value)} 
                        />
                    </div>
                    <div className="space-y-2 flex gap-2">
                        <div className="flex-1 space-y-2">
                            <Label>Hasta</Label>
                            <Input 
                                type="date" 
                                value={fechaFin} 
                                onChange={e => setFechaFin(e.target.value)} 
                            />
                        </div>
                        <Button className="mt-8" onClick={applyFilters}>
                            <Search className="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <div className="rounded-md border bg-card text-card-foreground overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/60 text-left">
                                <tr>
                                    <th className="px-4 py-3">Fecha</th>
                                    <th className="px-4 py-3">Usuario</th>
                                    <th className="px-4 py-3">Operación</th>
                                    <th className="px-4 py-3">Tabla</th>
                                    <th className="px-4 py-3">Registro ID</th>
                                    <th className="px-4 py-3">IP</th>
                                    <th className="px-4 py-3 text-right">Detalle</th>
                                </tr>
                            </thead>
                            <tbody>
                                {(!logs?.data || logs.data.length === 0) ? (
                                    <tr>
                                        <td colSpan={7} className="text-center py-6 text-muted-foreground">
                                            No se encontraron registros de auditoría.
                                        </td>
                                    </tr>
                                ) : (
                                    logs.data.map(log => (
                                        <tr key={log.id_log} className="border-t">
                                            <td className="px-4 py-3 whitespace-nowrap">
                                                {new Date(log.fecha_operacion).toLocaleString()}
                                            </td>
                                            <td className="px-4 py-3">
                                                {log.usuario ? log.usuario.name : 'Sistema'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge variant="outline" className={
                                                    log.operacion === 'DELETE' ? 'bg-red-50 text-red-700 border-red-200' :
                                                    log.operacion === 'INSERT' ? 'bg-green-50 text-green-700 border-green-200' :
                                                    log.operacion === 'UPDATE' ? 'bg-blue-50 text-blue-700 border-blue-200' :
                                                    'bg-gray-50 text-gray-700 border-gray-200'
                                                }>
                                                    {log.operacion}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">{log.tabla_afectada}</td>
                                            <td className="px-4 py-3">{log.id_registro || '-'}</td>
                                            <td className="px-4 py-3 text-xs">{log.ip_origen}</td>
                                            <td className="px-4 py-3 text-right">
                                                <Button variant="ghost" size="sm" onClick={() => setSelectedLog(log)}>
                                                    <Eye className="h-4 w-4" />
                                                </Button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                    
                    <div className="p-4 flex items-center justify-between border-t text-sm">
                        <span className="text-muted-foreground">
                            Mostrando {logs.from || 0} al {logs.to || 0} de {logs.total} registros
                        </span>
                        <div className="flex gap-2">
                            <Button 
                                variant="outline" 
                                size="sm" 
                                disabled={!logs.prev_page_url}
                                onClick={() => router.get(logs.prev_page_url)}
                            >
                                Anterior
                            </Button>
                            <Button 
                                variant="outline" 
                                size="sm" 
                                disabled={!logs.next_page_url}
                                onClick={() => router.get(logs.next_page_url)}
                            >
                                Siguiente
                            </Button>
                        </div>
                    </div>
                </div>

                <Dialog open={!!selectedLog} onOpenChange={(open) => !open && setSelectedLog(null)}>
                    <DialogContent className="max-w-3xl">
                        <DialogHeader>
                            <DialogTitle>Detalle del Log: {selectedLog?.operacion}</DialogTitle>
                            <DialogDescription>
                                Tabla: {selectedLog?.tabla_afectada} | ID Registro: {selectedLog?.id_registro} | Fecha: {selectedLog && new Date(selectedLog.fecha_operacion).toLocaleString()}
                            </DialogDescription>
                        </DialogHeader>
                        
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <h4 className="text-sm font-semibold mb-2">Datos Anteriores</h4>
                                <div className="h-[300px] w-full overflow-y-auto rounded-md border p-4 bg-muted/50">
                                    <pre className="text-xs">
                                        {selectedLog?.datos_anteriores 
                                            ? JSON.stringify(selectedLog.datos_anteriores, null, 2) 
                                            : 'No aplica o vacío'}
                                    </pre>
                                </div>
                            </div>
                            <div>
                                <h4 className="text-sm font-semibold mb-2">Datos Nuevos</h4>
                                <div className="h-[300px] w-full overflow-y-auto rounded-md border p-4 bg-muted/50">
                                    <pre className="text-xs">
                                        {selectedLog?.datos_nuevos 
                                            ? JSON.stringify(selectedLog.datos_nuevos, null, 2) 
                                            : 'No aplica o vacío'}
                                    </pre>
                                </div>
                            </div>
                        </div>
                        <div className="text-xs text-muted-foreground mt-2">
                            User Agent: {selectedLog?.user_agent}
                        </div>
                    </DialogContent>
                </Dialog>
            </div>
        </>
    );
}
