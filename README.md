# Laravel Lang Ru

<p align="center">
  <a href="https://github.com/Kerigard/laravel-lang-ru/actions"><img src="https://github.com/Kerigard/laravel-lang-ru/workflows/tests/badge.svg" alt="Build Status"></a>
  <a href="https://packagist.org/packages/Kerigard/laravel-lang-ru"><img src="https://img.shields.io/packagist/dt/Kerigard/laravel-lang-ru" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/Kerigard/laravel-lang-ru"><img src="https://img.shields.io/packagist/v/Kerigard/laravel-lang-ru" alt="Latest Stable Version"></a>
  <a href="https://packagist.org/packages/Kerigard/laravel-lang-ru"><img src="https://img.shields.io/packagist/l/Kerigard/laravel-lang-ru" alt="License"></a>
</p>

Русские языковые ресурсы для Laravel.

Перевод основан на репозитории https://github.com/Laravel-Lang/lang.

## Установка через composer

```bash
composer require kerigard/laravel-lang-ru
```

Опубликуйте языковые ресурсы, используя artisan команду `vendor:publish`, чтобы изменить файлы локализации:

```bash
php artisan vendor:publish --provider="Kerigard\LaravelLangRu\LangRuServiceProvider"
```

### Автоматический перевод языковых ресурсов

По умолчанию переводит все файлы из папки lang с английского на русский язык.

```bash
php artisan lang:translate
```

Можно указать с какого и на какой язык выполнять перевод, а так же конкретные папки и файлы.

```bash
php artisan lang:translate --source=en --target=ru --filter=en/validation.php --filter=vendor/my-package
```

## Ручная установка

> [!NOTE]
> При данном варианте установки копируются только файлы с языковыми ресурсами

### Laravel 9 и выше (папка lang находится в корне проекта)

```bash
curl https://codeload.github.com/Kerigard/laravel-lang-ru/tar.gz/master -L -o lang.tgz && tar --strip=1 -xvzf lang.tgz laravel-lang-ru-master/lang && rm lang.tgz
```

### Laravel <= 8

```bash
curl https://codeload.github.com/Kerigard/laravel-lang-ru/tar.gz/master -L -o lang.tgz && tar --strip=1 -xvzf lang.tgz -C resources laravel-lang-ru-master/lang && rm lang.tgz
```
