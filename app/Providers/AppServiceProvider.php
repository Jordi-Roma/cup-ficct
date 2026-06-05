<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use App\Modules\AccesoSeguridad\Models\LogAuditoria;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Event::listen(function (Login $event) {
            LogAuditoria::create([
                'tabla_afectada' => 'usuario',
                'operacion' => 'LOGIN',
                'id_registro' => $event->user->getAuthIdentifier(),
                'id_usuario' => $event->user->getAuthIdentifier(),
                'ip_origen' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        Event::listen(function (Logout $event) {
            if ($event->user) {
                LogAuditoria::create([
                    'tabla_afectada' => 'usuario',
                    'operacion' => 'LOGOUT',
                    'id_registro' => $event->user->getAuthIdentifier(),
                    'id_usuario' => $event->user->getAuthIdentifier(),
                    'ip_origen' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            }
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
