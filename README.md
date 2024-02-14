# Laravel Lang Ru

Русские языковые ресурсы для Laravel.

Перевод основан на репозитории https://github.com/Laravel-Lang/lang.

## Установка через composer

``` bash
composer require kerigard/laravel-lang-ru
```

Опубликуйте языковые ресурсы, используя artisan команду `vendor:publish`, чтобы изменить файлы локализации:

```bash
php artisan vendor:publish --provider="Kerigard\LaravelLangRu\LangRuServiceProvider"
```

## Ручная установка

### Laravel 9 и выше (папка lang находится в корне проекта)

```bash
curl https://codeload.github.com/Kerigard/laravel-lang-ru/tar.gz/master -L -o lang.tgz && tar --strip=1 -xvzf lang.tgz laravel-lang-ru-master/lang && rm lang.tgz
```

### Laravel <= 8

```bash
curl https://codeload.github.com/Kerigard/laravel-lang-ru/tar.gz/master -L -o lang.tgz && tar --strip=1 -xvzf lang.tgz -C resources laravel-lang-ru-master/lang && rm lang.tgz
```
