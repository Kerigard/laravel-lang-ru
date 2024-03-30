<?php

namespace Kerigard\LaravelLangRu\Services;

use Kerigard\LaravelLangRu\Contracts\Parser;

class JsonParser implements Parser
{
    protected ?string $code;

    protected bool $exists = false;

    protected array $items = [];

    public function __construct(?string $code = null)
    {
        $this->code = $code;
        $this->exists = ! is_null($code);
    }

    public function load(string $filename): self
    {
        if (file_exists($filename)) {
            $this->code = file_get_contents($filename);
            $this->exists = true;
        }

        return $this;
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    public function parse(): self
    {
        $items = json_decode($this->code, true) ?? [];

        foreach ($items as $key => $item) {
            if (is_string($item)) {
                $this->items[$key] = [
                    'type' => 'item',
                    'array_key' => $key,
                    'key' => $key,
                    'value' => $item,
                ];
            } else {
                $this->items[$key] = [
                    'type' => 'base',
                    'array_key' => $key,
                    'key' => $key,
                    'data' => $item,
                ];
            }
        }

        return $this;
    }

    public function items(): array
    {
        return $this->items;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    public function set(array $newToken, string $value): void
    {
        $newToken['type'] = 'item';
        $newToken['value'] = $value;
        $this->items[$newToken['key']] = $newToken;
    }

    public function remove(string $key): void
    {
        unset($this->items[$key]);
    }

    public function raw(): string
    {
        $array = [];

        ksort($this->items);

        foreach ($this->items as $item) {
            if ($item['type'] == 'base') {
                $array[$item['array_key']] = $item['data'];
            } else {
                $array[$item['array_key']] = $item['value'];
            }
        }

        return json_encode((object) $array, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}
