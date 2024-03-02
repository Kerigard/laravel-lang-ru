<?php

namespace Kerigard\LaravelLangRu\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Kerigard\LaravelLangRu\LangRuServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getPackageProviders($app): array
    {
        return [LangRuServiceProvider::class];
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->artisan('lang:publish');

        Http::preventStrayRequests();

        Http::fake(function (Request $request) {
            if (str_starts_with($request->url(), 'https://translate.googleapis.com/translate_a/single')) {
                return Http::response([[[($request->data()['q'] ?? '').' TR.']]]);
            }

            return Http::response(status: 404);
        });
    }

    protected function deleteLangDirectory(): void
    {
        $path = lang_path('ru');

        if (File::isDirectory($path)) {
            File::deleteDirectory($path);
        }
    }
}
