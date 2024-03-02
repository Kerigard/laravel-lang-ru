<?php

namespace Kerigard\LaravelLangRu;

use Illuminate\Translation\FileLoader;
use Illuminate\Translation\TranslationServiceProvider;
use Kerigard\LaravelLangRu\Commands\Translate;
use Kerigard\LaravelLangRu\Contracts\Parser;
use Kerigard\LaravelLangRu\Contracts\Translator;
use Kerigard\LaravelLangRu\Services\GoogleTranslator;
use Kerigard\LaravelLangRu\Services\PhpParser;

class LangRuServiceProvider extends TranslationServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->defineBindings();
            $this->publishFiles();
            $this->registerCommands();
        }
    }

    protected function registerLoader(): void
    {
        $this->app->singleton('translation.loader', function ($app) {
            $paths = [];

            if (is_dir($path = "{$app['path.base']}/vendor/laravel/framework/src/Illuminate/Translation/lang")) {
                $paths[] = $path;
            }
            if (is_dir($path = "{$app['path.base']}/vendor/illuminate/translation/lang")) {
                $paths[] = $path;
            }

            $paths[] = __DIR__.'/../lang';
            $paths[] = $app['path.lang'];

            return new FileLoader($app['files'], $paths);
        });
    }

    protected function defineBindings(): void
    {
        $this->app->bind(Parser::class, PhpParser::class);
        $this->app->bind(Translator::class, GoogleTranslator::class);
    }

    protected function publishFiles(): void
    {
        $this->publishes([
            __DIR__.'/../lang' => is_dir($this->app->langPath())
                ? $this->app->langPath()
                : $this->app->basePath('lang'),
        ], 'laravel-lang-ru');
    }

    protected function registerCommands(): void
    {
        $this->commands([
            Translate::class,
        ]);
    }
}
