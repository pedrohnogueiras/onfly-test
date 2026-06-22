<?php

declare(strict_types=1);

namespace App\Providers;

use App\Logging\RequestLogger;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Uma instância por request: o contexto definido em um ponto (ex.: controller)
        // fica disponível para qualquer componente que injete o RequestLogger no mesmo request.
        $this->app->scoped(RequestLogger::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
