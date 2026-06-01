import { Form } from '@inertiajs/react';
import { useRef } from 'react';
import ProfileController from '@/actions/App/Modules/Autenticacion/Controllers/ProfileController';
import Heading from '@/shared/components/heading';
import InputError from '@/shared/components/input-error';
import PasswordInput from '@/shared/components/password-input';
import { Button } from '@/shared/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger, } from '@/shared/components/ui/dialog';
import { Label } from '@/shared/components/ui/label';
export default function DeleteUser() {
    const passwordInput = useRef(null);
    return (<div className="space-y-6">
            <Heading variant="small" title="Eliminar cuenta" description="Elimina tu cuenta y todos sus datos asociados"/>
            <div className="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
                <div className="relative space-y-0.5 text-red-600 dark:text-red-100">
                    <p className="font-medium">Advertencia</p>
                    <p className="text-sm">
                        Procede con cuidado. Esta accion no se puede deshacer.
                    </p>
                </div>

                <Dialog>
                    <DialogTrigger asChild>
                        <Button variant="destructive" data-test="delete-user-button">
                            Eliminar cuenta
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogTitle>
                            Estas seguro de que deseas eliminar tu cuenta?
                        </DialogTitle>
                        <DialogDescription>
                            Una vez eliminada la cuenta, sus datos se eliminaran
                            de forma permanente. Ingresa tu contrasena para
                            confirmar esta accion.
                        </DialogDescription>

                        <Form {...ProfileController.destroy.form()} options={{
            preserveScroll: true,
        }} onError={() => passwordInput.current?.focus()} resetOnSuccess className="space-y-6">
                            {({ resetAndClearErrors, processing, errors }) => (<>
                                    <div className="grid gap-2">
                                        <Label htmlFor="password" className="sr-only">
                                            Contrasena
                                        </Label>

                                        <PasswordInput id="password" name="password" ref={passwordInput} placeholder="Contrasena" autoComplete="current-password"/>

                                        <InputError message={errors.password}/>
                                    </div>

                                    <DialogFooter className="gap-2">
                                        <DialogClose asChild>
                                            <Button variant="secondary" onClick={() => resetAndClearErrors()}>
                                                Cancelar
                                            </Button>
                                        </DialogClose>

                                        <Button variant="destructive" disabled={processing} asChild>
                                            <button type="submit" data-test="confirm-delete-user-button">
                                                Eliminar cuenta
                                            </button>
                                        </Button>
                                    </DialogFooter>
                                </>)}
                        </Form>
                    </DialogContent>
                </Dialog>
            </div>
        </div>);
}
