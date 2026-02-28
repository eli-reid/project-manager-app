<?php

namespace App\Core\User\Providers;

use App\Core\User\Actions\Fortify\CreateNewUser;
use App\Core\User\Actions\Fortify\ResetUserPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
        $this->loadViewsFrom(__DIR__ . '/../Resources/Views', 'user');
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
}
