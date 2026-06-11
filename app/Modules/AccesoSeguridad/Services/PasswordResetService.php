<?php

namespace App\Modules\AccesoSeguridad\Services;

use App\Modules\AccesoSeguridad\Mail\PasswordResetNotificationMail;
use App\Modules\AccesoSeguridad\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class PasswordResetService
{
    public function requestReset(string $identifier): void
    {
        $user = $this->findUser($identifier);

        if (! $user) {
            return;
        }

        $code = $this->generateResetCode();
        $table = config('auth.passwords.users.table', 'password_reset_tokens');
        $expiresIn = (int) config('auth.passwords.users.expire', 60);

        DB::table($table)->updateOrInsert(
            ['email' => $user->correo],
            [
                'token' => Hash::make($code),
                'created_at' => now(),
            ]
        );

        $to = $this->notificationEmail($user);

        Mail::to($to)->send(new PasswordResetNotificationMail(
            $this->buildMessage($user, $code, $expiresIn),
        ));
    }

    public function verifyCode(string $identifier, string $token): User
    {
        $user = $this->findUser($identifier);

        if (! $user) {
            throw ValidationException::withMessages([
                'username_or_email' => 'No se pudo validar la solicitud de recuperación.',
            ]);
        }

        $this->validateResetCode($user, $token);

        return $user;
    }

    public function resetPassword(string $identifier, string $token, string $password): void
    {
        $user = $this->verifyCode($identifier, $token);

        $this->updatePasswordAndClearToken($user, $password);
    }

    public function resetVerifiedPassword(string $identifier, string $password): void
    {
        $user = $this->findUser($identifier);

        if (! $user) {
            throw ValidationException::withMessages([
                'username_or_email' => 'No se pudo validar la solicitud de recuperación.',
            ]);
        }

        $this->ensureResetTokenStillExistsAndIsNotExpired($user);
        $this->updatePasswordAndClearToken($user, $password);
    }

    private function validateResetCode(User $user, string $token): void
    {
        $table = config('auth.passwords.users.table', 'password_reset_tokens');
        $record = DB::table($table)->where('email', $user->correo)->first();

        if (! $record || ! Hash::check($token, $record->token)) {
            throw ValidationException::withMessages([
                'token' => 'El código de recuperación no es válido.',
            ]);
        }

        $this->ensureRecordIsNotExpired($record, $user);
    }

    private function ensureResetTokenStillExistsAndIsNotExpired(User $user): void
    {
        $table = config('auth.passwords.users.table', 'password_reset_tokens');
        $record = DB::table($table)->where('email', $user->correo)->first();

        if (! $record) {
            throw ValidationException::withMessages([
                'password' => 'La solicitud de recuperación ya no es válida. Solicita un nuevo código.',
            ]);
        }

        $this->ensureRecordIsNotExpired($record, $user);
    }

    private function ensureRecordIsNotExpired(object $record, User $user): void
    {
        $table = config('auth.passwords.users.table', 'password_reset_tokens');
        $expiresAt = Carbon::parse($record->created_at)
            ->addMinutes((int) config('auth.passwords.users.expire', 60));

        if (now()->greaterThan($expiresAt)) {
            DB::table($table)->where('email', $user->correo)->delete();

            throw ValidationException::withMessages([
                'token' => 'El código de recuperación expiró. Solicita uno nuevo.',
            ]);
        }
    }

    private function updatePasswordAndClearToken(User $user, string $password): void
    {
        $user->forceFill([
            'password_hash' => Hash::make($password),
        ])->save();

        $table = config('auth.passwords.users.table', 'password_reset_tokens');
        DB::table($table)->where('email', $user->correo)->delete();
    }

    private function findUser(string $identifier): ?User
    {
        return User::query()
            ->where('username', $identifier)
            ->orWhere('correo', $identifier)
            ->first();
    }

    protected function generateResetCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function notificationEmail(User $user): string
    {
        return config('services.postulantes.notification_email')
            ?: config('services.postulante_notification_email')
            ?: config('mail.from.address')
            ?: $user->correo;
    }

    private function buildMessage(User $user, string $code, int $expiresIn): string
    {
        return implode(PHP_EOL, [
            'Solicitud de recuperación de contraseña - CUP-FICCT',
            '',
            'Datos del usuario:',
            'Nombre completo: '.$user->nombre.' '.$user->apellido,
            'CI: '.$user->ci,
            'Username: '.$user->username,
            'Correo registrado: '.$user->correo,
            '',
            'Código de recuperación:',
            $code,
            '',
            'Este código vence en '.$expiresIn.' minutos.',
            'Ingresa el código en la pantalla de verificación de contraseña.',
            '',
            'Si no solicitaste este cambio, ignora este mensaje.',
        ]);
    }
}
