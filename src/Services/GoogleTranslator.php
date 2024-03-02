<?php

namespace Kerigard\LaravelLangRu\Services;

use Illuminate\Support\Facades\Http;
use Kerigard\LaravelLangRu\Contracts\Translator;

class GoogleTranslator implements Translator
{
    protected string $source = 'en';

    protected string $target = 'ru';

    public function setSource(string $lang): void
    {
        $this->source = $lang;
    }

    public function setTarget(string $lang): void
    {
        $this->target = $lang;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function translate(string $text): string
    {
        $result = Http::get('https://translate.googleapis.com/translate_a/single', [
            'client' => 'gtx',
            'sl' => $this->source,
            'tl' => $this->target,
            'dt' => 't',
            'q' => $text,
        ])
            ->throw()
            ->json();

        $translated = implode('', array_map(function (array $translation) {
            return $translation[0] ?? '';
        }, $result[0] ?? []));

        return $translated ?: $text;
    }
}
