<?php

namespace Kerigard\LaravelLangRu;

use Illuminate\Translation\FileLoader;
use Illuminate\Translation\TranslationServiceProvider;

class LangRuServiceProvider extends TranslationServiceProvider
{
    /**
     * @return void
     */
    public function boot()
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../lang' => file_exists($this->app->langPath())
                ? $this->app->langPath()
                : $this->app->basePath('lang'),
        ], 'laravel-lang-ru');
    }

    /**
     * @return void
     */
    protected function registerLoader()
    {
        $this->app->singleton('translation.loader', function ($app) {
            $paths = [];

            if (file_exists($path = "{$app['path.base']}/vendor/laravel/framework/src/Illuminate/Translation/lang")) {
                $paths[] = $path;
            }

            $paths[] = __DIR__.'/../lang';
            $paths[] = $app['path.lang'];

            return new FileLoader($app['files'], $paths);
        });
    }
}
