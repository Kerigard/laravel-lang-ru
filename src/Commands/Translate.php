<?php

namespace Kerigard\LaravelLangRu\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Kerigard\LaravelLangRu\Contracts\Parser;
use Kerigard\LaravelLangRu\Contracts\Translator;
use SplFileInfo;

class Translate extends Command
{
    protected $signature = 'lang:translate
                            {--source=en : Source language}
                            {--target=ru : Target language}
                            {--filter=* : Directories and files that need to be translated}';

    protected $description = 'Translate language files';

    protected Translator $translator;

    public function handle(): void
    {
        $source = $this->option('source');
        $target = $this->option('target');

        $this->components->info("Перевод языковых ресурсов с {$source} на {$target}");

        $this->translator = $this->laravel->make(Translator::class);
        $this->translator->setSource($source);
        $this->translator->setTarget($target);

        $files = File::allFiles($this->laravel->langPath($source));
        $vendorDirs = File::isDirectory($this->laravel->langPath('vendor'))
            ? File::directories($this->laravel->langPath('vendor'))
            : [];

        foreach ($vendorDirs as $dir) {
            array_push($files, ...File::allFiles($this->joinPaths($dir, $source)));
        }

        foreach ($files as $key => $file) {
            foreach ($this->option('filter') as $path) {
                $path = preg_replace('/[\/\\\]/', DIRECTORY_SEPARATOR, $path);

                if (Str::startsWith($file->getRealPath(), $this->laravel->langPath($path))) {
                    continue 2;
                }
            }

            if (! empty($this->option('filter')) || $file->getExtension() != 'php') {
                unset($files[$key]);
            }
        }

        foreach ($files as $file) {
            $path = Str::after($file->getRealPath(), $this->laravel->langPath(DIRECTORY_SEPARATOR));
            $this->components->task($path, fn () => $this->translateFile($file, $target));
        }

        $this->newLine();
    }

    private function translateFile(SplFileInfo $file, string $target): void
    {
        /** @var \Kerigard\LaravelLangRu\Contracts\Parser */
        $sourceParser = $this->laravel->make(Parser::class);
        $source = $sourceParser->load($file->getRealPath())->parse();

        $targetPath = $this->joinPaths(dirname($file->getPath()), $target, $file->getFilename());
        /** @var \Kerigard\LaravelLangRu\Contracts\Parser */
        $targetParser = $this->laravel->make(Parser::class);
        $targetParser = $targetParser->load($targetPath);
        $target = $targetParser->exists() ? $targetParser->parse() : $source;

        foreach ($source->items() as $key => $token) {
            if (array_key_exists('value', $token) && (! $targetParser->exists() || ! $target->has($key))) {
                $target->set($token, $this->translate($token['value']));
            }
        }

        File::ensureDirectoryExists(pathinfo($targetPath, PATHINFO_DIRNAME));
        File::replace($targetPath, $target->raw());
    }

    private function translate(string $content): string
    {
        $content = preg_replace('/(:[\w-]+)/iu', '<$1>', $content);
        $content = preg_replace('/(&#?\w+;)/iu', '<!$1>', $content);

        preg_match_all('/<(:[\w-]+)>/iu', $content, $attributes);
        preg_match_all('/<!(&#?\w+;)>/iu', $content, $symbols);

        $content = $this->translator->translate($content);

        $content = preg_replace_array('/<\s?:.+>/iu', $attributes[1], $content);
        $content = preg_replace_array('/<!.+>/iu', $symbols[1], $content);

        return $content;
    }

    private function joinPaths(string $basePath, string ...$paths): string
    {
        foreach ($paths as $index => $path) {
            if (empty($path)) {
                unset($paths[$index]);
            } else {
                $paths[$index] = DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
            }
        }

        return $basePath.implode('', $paths);
    }
}
