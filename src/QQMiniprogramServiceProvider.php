<?php

namespace Superzc\QQMiniprogram;

use Illuminate\Support\ServiceProvider;

class QQMiniprogramServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // 发布配置文件
        $this->publishes([
            __DIR__ . '/config/qqminiprogram.php' => config_path('qqminiprogram.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('qqminiprogram', function ($app) {
            return new QQMiniprogram($app['config']);
        });
    }
}
