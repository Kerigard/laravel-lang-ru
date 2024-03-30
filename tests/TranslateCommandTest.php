<?php

namespace Kerigard\LaravelLangRu\Tests;

use Illuminate\Support\Facades\File;

class TranslateCommandTest extends TestCase
{
    public function test_command(): void
    {
        $this->deleteLangDirectory();

        $this->artisan('lang:translate')->assertSuccessful();
        $this->assertFileExists(lang_path('ru/auth.php'));
        $this->assertFileExists(lang_path('ru/pagination.php'));
        $this->assertFileExists(lang_path('ru/passwords.php'));
        $this->assertFileExists(lang_path('ru/validation.php'));

        $content = <<<'EOT'
            <?php

            return [

                /*
                |--------------------------------------------------------------------------
                | Authentication Language Lines
                |--------------------------------------------------------------------------
                |
                | The following language lines are used during authentication for various
                | messages that we need to display to the user. You are free to modify
                | these language lines according to your application's requirements.
                |
                */

                'failed' => 'These credentials do not match our records. TR.',
                'password' => 'The provided password is incorrect. TR.',
                'throttle' => 'Too many login attempts. Please try again in :seconds seconds. TR.',

            ];

            EOT;

        $this->assertEquals($content, file_get_contents(lang_path('ru/auth.php')));
    }

    public function test_filter(): void
    {
        $this->deleteLangDirectory();

        $this->artisan('lang:translate', ['--filter' => ['en/auth.php', 'en/passwords.php']])->assertSuccessful();
        $this->assertFileExists(lang_path('ru/auth.php'));
        $this->assertFileDoesNotExist(lang_path('ru/pagination.php'));
        $this->assertFileExists(lang_path('ru/passwords.php'));
        $this->assertFileDoesNotExist(lang_path('ru/validation.php'));
    }

    public function test_translate_json()
    {
        File::delete([lang_path('en.json'), lang_path('ru.json')]);
        File::put(lang_path('en.json'), json_encode(require lang_path('en/auth.php')));

        $this->artisan('lang:translate', ['--filter' => ['en.json']])->assertSuccessful();
        $this->assertFileExists(lang_path('ru.json'));

        $content = <<<'EOT'
            {
                "failed": "These credentials do not match our records. TR.",
                "password": "The provided password is incorrect. TR.",
                "throttle": "Too many login attempts. Please try again in :seconds seconds. TR."
            }
            EOT;

        $this->assertEquals($content, file_get_contents(lang_path('ru.json')));
    }
}
