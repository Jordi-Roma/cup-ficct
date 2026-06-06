import { useForm } from '@inertiajs/react';
import { Button } from '@/shared/components/ui/button';
import { Checkbox } from '@/shared/components/ui/checkbox';
import { Label } from '@/shared/components/ui/label';
export default function UserRolesForm({ usuario, roles, canSubmit, onSuccess, }) {
    const { data, setData, put, processing } = useForm({
        roles: usuario.roles.map((role) => role.id_rol),
    });
    const toggleRole = (id) => {
        setData('roles', data.roles.includes(id)
            ? data.roles.filter((roleId) => roleId !== id)
            : [...data.roles, id]);
    };
    const submit = (event) => {
        event.preventDefault();
        put(`/admin/usuarios/${usuario.id_usuario}/roles`, {
            preserveScroll: true,
            onSuccess,
        });
    };
    return (<form onSubmit={submit} className="space-y-5">
            <div>
                <h3 className="font-semibold">{usuario.name}</h3>
                <p className="text-sm text-muted-foreground">
                    {usuario.username} · {usuario.correo}
                </p>
            </div>

            <div className="grid gap-2 sm:grid-cols-2">
                {roles.map((role) => (<Label key={role.id_rol} className="flex cursor-pointer items-start gap-3 rounded-md border p-3">
                        <Checkbox checked={data.roles.includes(role.id_rol)} onCheckedChange={() => toggleRole(role.id_rol)}/>
                        <span>
                            <span className="block font-medium">
                                {role.nombre}
                            </span>
                            <span className="text-xs text-muted-foreground">
                                {role.descripcion}
                            </span>
                        </span>
                    </Label>))}
            </div>

            <div className="flex justify-end gap-2">
                <Button type="button" variant="outline" onClick={onSuccess} disabled={processing}>
                    Cancelar
                </Button>
                <Button type="submit" disabled={processing || !canSubmit} className="bg-[#0D2B85] text-white hover:bg-[#0a2270]">
                    Guardar roles
                </Button>
            </div>
        </form>);
}
