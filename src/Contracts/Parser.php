<?php

namespace Kerigard\LaravelLangRu\Contracts;

interface Parser
{
    public function load(string $filename): self;

    public function exists(): bool;

    public function parse(): self;

    public function items(): array;

    public function has(string $key): bool;

    public function set(array $newToken, string $value): void;

    public function remove(string $key): void;

    public function raw(): string;
}
