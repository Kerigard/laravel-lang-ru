<?php

namespace Kerigard\LaravelLangRu\Contracts;

interface Translator
{
    public function setSource(string $lang): void;

    public function setTarget(string $lang): void;

    public function getSource(): string;

    public function getTarget(): string;

    public function translate(string $text): string;
}
