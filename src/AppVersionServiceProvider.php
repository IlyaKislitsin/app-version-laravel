<?php

namespace AvtoDev\AppVersion;

use Illuminate\Support\Facades\Blade;
use Illuminate\Contracts\Foundation\Application;
use AvtoDev\AppVersion\Contracts\AppVersionManagerContract;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

/**
 * Class AppVersionServiceProvider.
 */
class AppVersionServiceProvider extends IlluminateServiceProvider
{
    /**
     * Versions manager DI bind alias.
     */
    const VERSION_MANAGER_ALIAS = 'app.version.manager';

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Get config root key name.
     *
     * @return string
     */
    public static function getConfigRootKeyName()
    {
        return basename(static::getConfigPath(), '.php');
    }

    /**
     * Returns path to the configuration file.
     *
     * @return string
     */
    public static function getConfigPath()
    {
        return env('APP_VERSION_CONFIG_PATH', __DIR__ . '/config/version.php');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->initializeConfigs();

        $this->registerAppVersionManager();

        $this->registerBlade();

        $this->registerHelpers();

        if ($this->app->runningInConsole()) {
            $this->registerArtisanCommands();
        }
    }

    /**
     * Register version manager instance.
     *
     * @return void
     */
    protected function registerAppVersionManager()
    {
        $this->app->singleton(AppVersionManager::class, function (Application $app) {
            $config = (array) $app
                ->make('config')
                ->get(static::getConfigRootKeyName());

            return new AppVersionManager($config);
        });

        $this->app->bind(AppVersionManagerContract::class, AppVersionManager::class);
        $this->app->bind(static::VERSION_MANAGER_ALIAS, AppVersionManagerContract::class);
    }

    /**
     * Register Blade directives.
     */
    protected function registerBlade()
    {
        Blade::directive('app_version', function () {
            return "<?php echo resolve('app.version.manager')->formatted(); ?>";
        });

        Blade::directive('app_build', function () {
            return "<?php echo resolve('app.version.manager')->build(); ?>";
        });
    }

    /**
     * Register package helpers.
     *
     * @return void
     */
    protected function registerHelpers()
    {
        require __DIR__ . '/helpers.php';
    }

    /**
     * Initialize configs.
     *
     * @return void
     */
    protected function initializeConfigs()
    {
        $this->mergeConfigFrom(static::getConfigPath(), static::getConfigRootKeyName());

        $this->publishes([
            realpath(static::getConfigPath()) => config_path(basename(static::getConfigPath())),
        ], 'config');
    }

    /**
     * Register artisan commands.
     *
     * @return void
     */
    protected function registerArtisanCommands()
    {
        $this->commands([
            Commands\VersionCommand::class,
        ]);
    }
}