import { Checkbox } from '@/shared/components/ui/checkbox';
import { Label } from '@/shared/components/ui/label';
export default function PermissionsMatrix({ permisosPorModulo, selectedIds, onToggle, }) {
    return (<div className="max-h-80 space-y-4 overflow-y-auto rounded-md border p-4">
            {Object.entries(permisosPorModulo).map(([modulo, permisos]) => (<section key={modulo} className="space-y-3">
                    <div className="flex items-center justify-between rounded-md bg-slate-50 px-3 py-2">
                        <h3 className="text-sm font-semibold text-[#001f3f]">
                            {modulo}
                        </h3>
                        <span className="text-xs text-muted-foreground">
                            {permisos.length} permisos
                        </span>
                    </div>

                    <div className="grid gap-2 sm:grid-cols-2">
                        {permisos.map((permiso) => (<Label key={permiso.id_permiso} className="flex cursor-pointer items-start gap-3 rounded-md border p-3 text-sm hover:bg-slate-50">
                                <Checkbox checked={selectedIds.includes(permiso.id_permiso)} onCheckedChange={() => onToggle(permiso.id_permiso)}/>
                                <span className="grid gap-1">
                                    <span className="font-medium">
                                        {permiso.nombre}
                                    </span>
                                    <span className="text-xs text-muted-foreground">
                                        {permiso.descripcion ??
                    permiso.accion}
                                    </span>
                                </span>
                            </Label>))}
                    </div>
                </section>))}
        </div>);
}
