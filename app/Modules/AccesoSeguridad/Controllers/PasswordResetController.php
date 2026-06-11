<?php

namespace App\Modules\AccesoSeguridad\Controllers;

use App\Modules\AccesoSeguridad\Requests\ForgotPasswordRequest;
use App\Modules\AccesoSeguridad\Requests\ResetPasswordRequest;
use App\Modules\AccesoSeguridad\Services\PasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetController extends BaseController
{
    public function __construct(private readonly PasswordResetService $service) {}

    public function create(Request $request): Response
    {
        return Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        $identifier = $request->validated('username_or_email');

        $this->service->requestReset($identifier);

        $request->session()->put('password_reset.identifier', $identifier);
        $request->session()->forget('password_reset.verified');

        return redirect()->route('password.verify.form')
            ->with('status', 'Si el usuario existe, se envió un código de recuperación al correo institucional.');
    }

    public function verifyForm(Request $request): Response|RedirectResponse
    {
        $identifier = $request->session()->get('password_reset.identifier', '');

        if ($identifier === '') {
            return redirect()->route('password.request');
        }

        return Inertia::render('auth/verify-reset-code', [
            'username_or_email' => $identifier,
            'status' => $request->session()->get('status'),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username_or_email' => ['required', 'string', 'max:150'],
            'token' => ['required', 'string', 'digits:6'],
        ], [
            'token.required' => 'El código de recuperación es obligatorio.',
            'token.digits' => 'El código de recuperación debe tener 6 dígitos.',
        ]);

        $user = $this->service->verifyCode($data['username_or_email'], $data['token']);

        $request->session()->put('password_reset.identifier', $user->username);
        $request->session()->put('password_reset.verified', true);

        return redirect()->route('password.reset.form')
            ->with('status', 'Código verificado correctamente.');
    }

    public function edit(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->get('password_reset.verified')) {
            return redirect()->route('password.verify.form');
        }

        return Inertia::render('auth/reset-password', [
            'username_or_email' => $request->session()->get('password_reset.identifier', ''),
            'passwordRules' => Password::min(8)->mixedCase()->numbers()->symbols()->toPasswordRulesString(),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function update(ResetPasswordRequest $request): RedirectResponse
    {
        if (! $request->session()->get('password_reset.verified')) {
            return redirect()->route('password.verify.form');
        }

        $data = $request->validated();
        $identifier = $request->session()->get('password_reset.identifier');

        $this->service->resetVerifiedPassword($identifier, $data['password']);

        $request->session()->forget('password_reset');

        return redirect()->route('login')
            ->with('status', 'Contraseña actualizada correctamente. Inicia sesión con tu nueva contraseña.');
    }
}
