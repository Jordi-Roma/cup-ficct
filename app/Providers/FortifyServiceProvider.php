<?php

namespace App\Providers;

use App\Modules\AccesoSeguridad\Actions\ResetUserPassword;
use App\Modules\AccesoSeguridad\Models\User;
use App\Modules\GestionAcademica\Models\Carrera;
use App\Modules\GestionAcademica\Models\GestionAcademica;
use App\Modules\RegistroPostulantes\Actions\CreateNewUser;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RegisterResponseContract::class, function () {
            return new class implements RegisterResponseContract
            {
                public function toResponse($request)
                {
                    Auth::guard()->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('login')
                        ->with('status', 'La solicitud fue enviada y queda pendiente de validacion administrativa.');
                }
            };
        });

        $this->app->singleton(LoginResponseContract::class, function () {
            return new class implements LoginResponseContract
            {
                public function toResponse($request)
                {
                    $user = $request->user();

                    if (! $user?->hasRole('POSTULANTE')) {
                        return redirect()->intended(route('dashboard', absolute: false));
                    }

                    $postulante = $user->postulante()
                        ->with(['postulaciones' => fn ($query) => $query->orderByDesc('fecha_postulacion')])
                        ->first();
                    $postulacion = $postulante?->postulaciones->first();

                    if ($postulacion?->estado_proceso === 'VALIDADO_PENDIENTE_PAGO') {
                        return redirect()->intended('/postulante/pago');
                    }

                    if ($postulacion?->estado_proceso === 'HABILITADO_CUP') {
                        return redirect()->intended(route('dashboard', absolute: false));
                    }

                    Auth::guard()->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('login')
                        ->with('status', 'Tu solicitud aun no esta habilitada para acceder al sistema.');
                }
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('username', $request->input(Fortify::username()))->first();

            if (
                $user !== null
                && $user->activo
                && $user->estado_acceso === 'HABILITADO'
                && Hash::check($request->input('password'), $user->password_hash)
            ) {
                return $user;
            }

            return null;
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/reset-password', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/verify-email', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/register', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'gestiones' => GestionAcademica::query()
                ->where('activo', true)
                ->orderBy('fecha_inicio', 'desc')
                ->get(['id_gestion as id', 'nombre']),
            'carreras' => Carrera::query()
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id_carrera as id', 'nombre']),
        ]));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/confirm-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
