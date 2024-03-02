<?php

require_once __DIR__.'/../../src/Contracts/Parser.php';
require_once __DIR__.'/../../src/Services/PhpParser.php';

use Kerigard\LaravelLangRu\Services\PhpParser;

$baseDirectory = __DIR__.'/../../lang/ru/';
$excludes = [
    'validation.php' => ["'custom'", "'custom'.'attribute-name'", "'attributes'"],
];

$files = files();

foreach ($files as $file) {
    $content = content($file);
    $source = (new PhpParser($content))->parse();
    $path = "{$baseDirectory}{$file}";

    if (! file_exists($path)) {
        if (file_put_contents($path, $content) === false) {
            throw new Exception("Unable to write file {$path}");
        }

        echo "File created {$path}\n";

        continue;
    }

    $target = (new PhpParser())->load($path)->parse();
    $result = merge($target, $source, $excludes[$file] ?? []);

    if (file_put_contents($path, $result) === false) {
        throw new Exception("Unable to write file {$path}");
    }

    echo "File updated {$path}\n";
}

function files(): array
{
    $response = api();

    return array_map(fn (array $value) => $value['name'], $response);
}

function content(string $filename): string
{
    $response = api($filename);

    return base64_decode($response['content']);
}

function api(?string $file = null): array
{
    $url = 'https://api.github.com/repos/laravel/framework/contents/src/Illuminate/Translation/lang/en';

    if ($file) {
        $url .= "/{$file}";
    }

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => 'laravel-lang-ru',
    ]);

    $response = curl_exec($curl);
    $info = curl_getinfo($curl);
    $error = curl_error($curl);

    curl_close($curl);

    if ($info['http_code'] != 200) {
        $message = trim(empty($error) ? $response : $error);
        $error = "HTTP request returned status code {$info['http_code']}: {$message}";

        throw new Exception($error, $info['http_code']);
    }

    return json_decode($response, true);
}

function merge(PhpParser $target, PhpParser $source, array $excludes = []): string
{
    foreach ($target->items() as $key => $token) {
        if (array_key_exists('value', $token) && ! $source->has($key)) {
            foreach ($excludes as $exclude) {
                if ($token['key'] == $exclude || $token['key'] == "{$exclude}.{$token['array_key']}") {
                    continue 2;
                }
            }

            $target->remove($key);
        }
    }

    foreach ($source->items() as $key => $token) {
        if (array_key_exists('value', $token) && ! $target->has($key)) {
            $target->set($token, $token['value']);
        }
    }

    return $target->raw();
}
