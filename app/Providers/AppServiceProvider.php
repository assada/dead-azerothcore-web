<?php

namespace App\Providers;

use App\Auth\Srp6UserProvider;
use App\Support\GameData;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GameData::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('srp6', fn ($app, array $config) => new Srp6UserProvider($app['hash'], $config['model']));

        VerifyEmail::toMailUsing(fn ($account, string $url) => (new MailMessage)
            ->subject('Verify your '.config('app.name').' email')
            ->greeting('Welcome to '.config('app.name'))
            ->line('Verify your email to finish setting up your account.')
            ->action('Verify email', $url)
            ->line('This link expires in '.config('auth.verification.expire', 60).' minutes.')
            ->line('If you did not create this account, ignore this email.'));

        ResetPassword::toMailUsing(fn ($account, string $token) => (new MailMessage)
            ->subject('Reset your '.config('app.name').' password')
            ->greeting('Reset your password')
            ->line('Choose a new password for your '.config('app.name').' account. It will work on the website and in the game.')
            ->action('Reset password', route('password.reset', [
                'token' => $token, 'email' => $account->getEmailForPasswordReset(),
            ]))
            ->line('This link expires in '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' minutes.')
            ->line('If you did not request a reset, ignore this email. Your password will stay the same.'));
    }
}
