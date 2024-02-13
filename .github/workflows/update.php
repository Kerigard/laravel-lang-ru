<?php

$baseDirectory = __DIR__.'/../../lang/ru/';
$excludes = [
    'validation.php' => ['custom', 'attributes'],
];

$files = files();

foreach ($files as $file) {
    $content = content($file);
    $source = source($content);
    $path = "{$baseDirectory}{$file}";

    if (! file_exists($path)) {
        if (file_put_contents($path, $content) === false) {
            throw new Exception("Unable to write file {$path}");
        }

        echo "File created {$path}\n";

        continue;
    }

    $target = target($path);
    $result = merge($target, $source);

    save($path, $result, $excludes[$file] ?? []);

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

function source(string $content): array
{
    return eval(substr($content, 5));
}

function target(string $path): array
{
    return require $path;
}

function merge(array $target, array $source): array
{
    foreach ($target as $key => $value) {
        if (! array_key_exists($key, $source)) {
            continue;
        }

        if (is_array($value)) {
            foreach ($value as $key2 => $value2) {
                if (array_key_exists($key2, @$source[$key])) {
                    $source[$key][$key2] = $value2;
                }
            }
        } else {
            $source[$key] = $value;
        }
    }

    return $source;
}

function save(string $path, array $result, array $exclude = []): void
{
    if (empty($result)) {
        return;
    }

    $stubPath = __DIR__.'/stubs/'.pathinfo($path, PATHINFO_FILENAME).'.stub';

    if (file_exists($stubPath)) {
        $stub = file_get_contents($stubPath);
    } else {
        $stub = "<?php\n\nreturn [\n{{ \$result }}\n];\n";
    }

    $lines = [];

    foreach ($result as $key => $value) {
        if (in_array($key, $exclude)) {
            continue;
        }

        line($lines, $key, $value);
    }

    $content = str_replace('{{ $result }}', implode("\n", $lines), $stub);

    if (file_put_contents($path, $content) === false) {
        throw new Exception("Unable to write file {$path}");
    }
}

function line(array &$lines, string $key, string|array $value, int $indent = 4): void
{
    $prefix = str_pad('', $indent);

    if (is_array($value)) {
        $lines[] = "{$prefix}'$key' => [";

        foreach ($value as $key2 => $value2) {
            line($lines, $key2, $value2, $indent*2);
        }

        $lines[] = "{$prefix}],";
    } else {
        $value = str_replace("'", "\\'", $value);
        $lines[] = "{$prefix}'{$key}' => '{$value}',";
    }
}
