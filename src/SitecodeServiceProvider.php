<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode;

use Alexeyplodenko\Sitecode\Commands\InstallCache\InstallOnApacheCommand;
use Alexeyplodenko\Sitecode\Commands\InstallCache\InstallOnNginxCommand;
use Alexeyplodenko\Sitecode\Commands\InstallCommand;
use Alexeyplodenko\Sitecode\Services\CachedDecorator;
use Alexeyplodenko\Sitecode\Services\CallSignatureBuilder;
use Alexeyplodenko\Sitecode\Services\DataSerializers\PrintRDataSerializer;
use Alexeyplodenko\Sitecode\Services\DataSerializers\VarDumperDataSerializer;
use Alexeyplodenko\Sitecode\Services\PagesCache;
use Alexeyplodenko\Sitecode\Services\PagesRepository;
use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;

class SitecodeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'sitecode');

        $this->app->singleton(PagesRepository::class, function ($app) {
            $signatureBuilder = new CallSignatureBuilder(
                new PrintRDataSerializer(),
                [
                    [\Illuminate\Http\Request::class, 'VarDumperSignatureBuilder']
                ]
            );
            $signatureBuilder->registerBuilder('VarDumperSignatureBuilder', new VarDumperDataSerializer());
            $signatureBuilder->registerBuilder('PrintRCallSignatureBuilder', new PrintRDataSerializer());

            return new CachedDecorator(new PagesRepository($app), $signatureBuilder);
        });

        $this->app->singleton(PagesCache::class, function () {
            return new PagesCache();
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'sitecode');
        
        $this->commands([
            InstallCommand::class,
            InstallOnApacheCommand::class,
            InstallOnNginxCommand::class,
        ]);
        
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('sitecode.php'),
            ], 'config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/sitecode'),
            ], 'sitecode-views');
        }

        Filament::serving(function () {
            // 
        });
    }
}
