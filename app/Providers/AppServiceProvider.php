<?php

namespace App\Providers;

use App\Contracts\MessagingProviderInterface;
use App\Services\Messaging\EvolutionMessagingProvider;
use App\Services\Messaging\FakeLocalMessagingProvider;
use App\Support\Tenancy\CurrentCompany;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(CurrentCompany::class);
        $this->app->bind(MessagingProviderInterface::class, function (Application $app): MessagingProviderInterface {
            return match (config('services.messaging.driver')) {
                'local' => new FakeLocalMessagingProvider,
                'evolution' => $app->make(EvolutionMessagingProvider::class),
                default => throw new \LogicException('Unsupported messaging driver.'),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
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
