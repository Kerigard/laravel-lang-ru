<?php

namespace Kerigard\LaravelLangRu\Tests;

use Illuminate\Support\Facades\Lang;

class LangTest extends TestCase
{
    public function test_autoload_lang(): void
    {
        $this->deleteLangDirectory();

        $this->assertEquals('The provided password is incorrect.', Lang::get('auth.password'));
        $this->assertEquals('Hello!', Lang::get('Hello!'));

        Lang::setLocale('ru');

        $this->assertEquals('Некорректный пароль.', Lang::get('auth.password'));
        $this->assertEquals('Здравствуйте!', Lang::get('Hello!'));
    }
}
