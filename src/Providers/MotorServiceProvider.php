<?php

namespace Motor\Admin\Providers;

use Clockwork\Support\Laravel\ClockworkServiceProvider;
use Clockwork\Support\Vanilla\Clockwork;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Motor\Admin\Console\Commands\MotorCreatePermissionsCommand;
use Motor\Admin\Console\Commands\MotorCreateScoutIndexCommand;
use Motor\Admin\Models\Category;
use Motor\Admin\Models\ConfigVariable;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class MotorServiceProvider
 */
class MotorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function boot()
    {
        if ($this->app->environment('local') && class_exists(ClockworkServiceProvider::class)) {
            $clockwork = Clockwork::init(['register_helpers' => true]);
        }

        Response::macro('attachment', static function ($content, $filename, $format = 'application/json') {
            $headers = [
                'Content-type'        => $format,
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ];

            return Response::make($content, 200, $headers);
        });

        $this->routes();
        $this->routeModelBindings();
        $this->permissions();
        $this->registerCommands();
        $this->migrations();
        $this->navigationItems();
        merge_local_config_with_db_configuration_variables('motor-admin');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'motor-admin');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/ide-helper.php', 'ide-helper');
        $this->mergeConfigFrom(__DIR__.'/../../config/media-library.php', 'medialibrary');
        // $this->mergeConfigFrom(__DIR__.'/../../config/ai.php', 'ai');
        $this->mergeConfigFrom(__DIR__.'/../../config/motor-admin.php', 'motor-admin');
        $this->mergeConfigFrom(__DIR__.'/../../config/motor-admin-project.php', 'motor-admin-project');
        $this->mergeConfigFrom(__DIR__.'/../../config/permission.php', 'permission');
        $this->mergeConfigFrom(__DIR__.'/../../config/blameable.php', 'blameable');
        $this->mergeConfigFrom(__DIR__.'/../../config/snowflake.php', 'snowflake');

        if (! app()->configurationIsCached()) {
            $config = $this->app['config']->get('scout', []);
            $this->app['config']->set('scout', array_merge_recursive(require __DIR__.'/../../config/scout.php', $config));

            $globalSearchConfig = $this->app['config']->get('global-search', []);
            $this->app['config']->set('global-search', array_merge_recursive(require __DIR__.'/../../config/global-search.php', $globalSearchConfig));
        }
    }

    /**
     * Set migration path
     */
    public function migrations()
    {
        $this->loadMigrationsFrom(realpath(__DIR__.'/../../database/migrations'));
    }

    /**
     * Merge permission config file
     */
    public function permissions()
    {
        $config = $this->app['config']->get('motor-admin-permissions', []);
        $this->app['config']->set('motor-admin-permissions', array_merge_recursive(require __DIR__.'/../../config/motor-admin-permissions.php', $config));
    }

    /**
     * Set routes
     */
    public function routes()
    {
        Route::middleware('api')->prefix('api')->group(function () {
            $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        });
        // if (! $this->app->routesAreCached()) {
        //    require __DIR__.'/../../routes/api.php';
        // }
    }

    /**
     * Add route model bindings
     */
    public function routeModelBindings()
    {
        Route::bind('user', static function ($id) {
            return config('motor-admin.models.user')::findOrFail($id);
        });

        Route::bind('role', static function ($id) {
            return config('motor-admin.models.role')::findOrFail($id);
        });

        Route::bind('language', static function ($id) {
            return config('motor-admin.models.language')::findOrFail($id);
        });

        Route::bind('client', static function ($id) {
            return config('motor-admin.models.client')::findOrFail($id);
        });

        Route::bind('email_template', static function ($id) {
            return config('motor-admin.models.email_template')::findOrFail($id);
        });

        Route::bind('category', static function ($id) {
            return Category::findOrFail($id);
        });

        Route::bind('config_variable', static function ($id) {
            return ConfigVariable::findOrFail($id);
        });
    }

    /**
     * Register artisan commands
     */
    public function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MotorCreatePermissionsCommand::class,
                MotorCreateScoutIndexCommand::class,
            ]);
        }
    }

    /**
     * Merge backend navigation items from configuration file
     */
    public function navigationItems()
    {
        $config = $this->app['config']->get('motor-admin-navigation', []);
        $this->app['config']->set('motor-admin-navigation', array_replace_recursive(require __DIR__.'/../../config/motor-admin-navigation.php', $config));
    }
}
