<?php

namespace App\Core\Identity\Providers;

use App\Core\Audit\Contracts\AuditLoggerContract;
use App\Core\Identity\Actions\Fortify\CreateNewUser;
use App\Core\Identity\Actions\Fortify\ResetUserPassword;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
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
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configureAuthEventAuditing();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'user');
        Fortify::loginView(fn () => view('user::auth.login'));
        Fortify::verifyEmailView(fn () => view('user::auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('user::auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('user::auth.confirm-password'));
        Fortify::registerView(fn () => view('user::auth.register'));
        Fortify::resetPasswordView(fn () => view('user::auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn () => view('user::auth.forgot-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }

    /**
     * Configure auth success event auditing.
     */
    private function configureAuthEventAuditing(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            app(AuditLoggerContract::class)->record('auth.login', $event->user, [
                'guard' => $event->guard,
                'remember' => $event->remember,
            ], $event->user);
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user === null) {
                return;
            }

            app(AuditLoggerContract::class)->record('auth.logout', $event->user, [
                'guard' => $event->guard,
            ], $event->user);
        });
    }
}
