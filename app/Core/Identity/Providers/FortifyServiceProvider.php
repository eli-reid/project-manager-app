<?php

namespace App\Core\Identity\Providers;

use App\Core\Audit\Contracts\AuditLoggerContract;
use App\Core\Identity\Actions\Fortify\CreateNewUser;
use App\Core\Identity\Actions\Fortify\ResetUserPassword;
use App\Core\Identity\Http\Responses\MobileAwareLoginResponse;
use App\Core\Identity\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, MobileAwareLoginResponse::class);
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
        Fortify::authenticateUsing(function (Request $request): ?User {
            $login = trim((string) $request->input('login'));
            $password = (string) $request->input('password');

            if ($login === '' || $password === '') {
                return null;
            }

            $user = User::query()
                ->whereRaw('LOWER(email) = ?', [Str::lower($login)])
                ->orWhereRaw('LOWER(username) = ?', [Str::lower($login)])
                ->first();

            if (! $user instanceof User) {
                return null;
            }

            if (! Hash::check($password, (string) $user->password)) {
                return null;
            }

            return $user;
        });
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
            $throttleKey = Str::transliterate(Str::lower((string) $request->input(Fortify::username())).'|'.$request->ip());

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
