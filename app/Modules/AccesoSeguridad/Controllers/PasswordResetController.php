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
        $this->service->requestReset($request->validated('username_or_email'));

        return back()->with('status', 'Si el usuario existe, se enviaron instrucciones de recuperacion al correo institucional.');
    }

    public function edit(Request $request, string $token): Response
    {
        return Inertia::render('auth/reset-password', [
            'token' => $token,
            'username_or_email' => $request->query('identifier', ''),
            'passwordRules' => Password::min(8)->mixedCase()->numbers()->symbols()->toPasswordRulesString(),
        ]);
    }

    public function update(ResetPasswordRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->service->resetPassword(
            $data['username_or_email'],
            $data['token'],
            $data['password'],
        );

        return redirect()->route('login')
            ->with('status', 'Contrasena actualizada correctamente. Inicia sesion con tu nueva contrasena.');
    }
}
