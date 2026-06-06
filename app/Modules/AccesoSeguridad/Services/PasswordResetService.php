<?php

namespace App\Modules\AccesoSeguridad\Services;

use App\Modules\AccesoSeguridad\Mail\PasswordResetNotificationMail;
use App\Modules\AccesoSeguridad\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetService
{
    public function requestReset(string $identifier): void
    {
        $user = $this->findUser($identifier);

        if (! $user) {
            return;
        }

        $token = Str::random(64);
        $table = config('auth.passwords.users.table', 'password_reset_tokens');
        $expiresIn = (int) config('auth.passwords.users.expire', 60);
        $link = url('/reset-password/'.$token.'?identifier='.urlencode($identifier));

        DB::table($table)->updateOrInsert(
            ['email' => $user->correo],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        $to = config('services.postulante_notification_email', config('mail.from.address'));

        Mail::to($to)->send(new PasswordResetNotificationMail(
            $this->buildMessage($user, $link, $expiresIn),
        ));
    }

    public function resetPassword(string $identifier, string $token, string $password): void
    {
        $user = $this->findUser($identifier);

        if (! $user) {
            throw ValidationException::withMessages([
                'username_or_email' => 'No se pudo validar la solicitud de recuperacion.',
            ]);
        }

        $table = config('auth.passwords.users.table', 'password_reset_tokens');
        $record = DB::table($table)->where('email', $user->correo)->first();

        if (! $record || ! Hash::check($token, $record->token)) {
            throw ValidationException::withMessages([
                'token' => 'El enlace de recuperacion no es valido.',
            ]);
        }

        $expiresAt = Carbon::parse($record->created_at)
            ->addMinutes((int) config('auth.passwords.users.expire', 60));

        if (now()->greaterThan($expiresAt)) {
            DB::table($table)->where('email', $user->correo)->delete();

            throw ValidationException::withMessages([
                'token' => 'El enlace de recuperacion expiro. Solicita uno nuevo.',
            ]);
        }

        $user->forceFill([
            'password_hash' => Hash::make($password),
        ])->save();

        DB::table($table)->where('email', $user->correo)->delete();
    }

    private function findUser(string $identifier): ?User
    {
        return User::query()
            ->where('username', $identifier)
            ->orWhere('correo', $identifier)
            ->first();
    }

    private function buildMessage(User $user, string $link, int $expiresIn): string
    {
        return implode(PHP_EOL, [
            'Solicitud de recuperacion de contrasena - CUP-FICCT',
            '',
            'Usuario:',
            'Nombre completo: '.$user->nombre.' '.$user->apellido,
            'CI: '.$user->ci,
            'Username: '.$user->username,
            'Correo registrado: '.$user->correo,
            '',
            'Enlace para cambiar contrasena:',
            $link,
            '',
            'Este enlace vence en '.$expiresIn.' minutos.',
            '',
            'Si no solicitaste este cambio, ignora este mensaje.',
        ]);
    }
}
