<?php

namespace Kerigard\LaravelLangRu\Services;

use Kerigard\LaravelLangRu\Contracts\Parser;
use PhpToken;

class PhpParser implements Parser
{
    protected ?string $code;

    protected bool $exists = false;

    protected array $tokens = [];

    protected array $items = [];

    protected int $groupIndex = 0;

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
        $tokens = PhpToken::tokenize($this->code ?? '');
        $newToken = null;
        $brackets = $arrays = $arrayKeys = [];
        $hasArrayValue = false;

        foreach ($tokens as $key => $token) {
            $type = 'base';
            $options = [];

            if (
                ($token->is(T_RETURN) && empty($arrays)) ||
                ($token->is(T_WHITESPACE) && @$newToken['type'] == 'return')
            ) {
                $type = 'return';
            } elseif ($token->is(T_ARRAY) || ($token->is(T_WHITESPACE) && @$newToken['old_array'])) {
                $type = 'array_open';
                $options['old_array'] = true;
            } elseif ($token->is(['[', '('])) {
                $brackets[] = $key;

                if (in_array(@$newToken['type'], ['return', 'array_open'])) {
                    $type = 'array_open';
                    $arrays[] = $key;
                } elseif ($newToken) {
                    $type = $newToken['type'];

                    if ($newToken['type'] == 'array_item' && $hasArrayValue) {
                        $newToken['closed'] = $newToken['open_nested'] = true;
                        $arrays[] = $key;
                        $arrayKeys[] = $newToken['array_key'];
                        $hasArrayValue = false;
                    }
                }
            } elseif ($token->is([']', ')'])) {
                $lastBracket = array_pop($brackets);

                if ($lastBracket == @$arrays[count($arrays) - 1]) {
                    if (@$newToken['type'] == 'array_item' && ! @$newToken['closed']) {
                        $newToken['text'] .= ',';
                        $newToken['closed'] = true;
                    }

                    if (empty($arrayKeys)) {
                        $type = 'array_close';
                    } else {
                        $type = 'array_item';
                        $options['key'] = implode('.', $arrayKeys);
                        $options['array_key'] = array_pop($arrayKeys);
                        $options['indent'] = (count($arrayKeys) + 1) * 4;
                        $options['close_nested'] = true;
                        $options['group'] = $this->groupIndex;

                        $this->tokens[] = $newToken;
                        $newToken = null;
                    }

                    array_pop($arrays);
                } elseif ($newToken) {
                    $type = $newToken['type'];
                }
            } elseif (
                $token->is([T_COMMENT, T_DOC_COMMENT]) &&
                str_starts_with($token->text, '/*') &&
                empty($arrayKeys) &&
                (@$newToken['type'] != 'array_item' || @$newToken['closed'])
            ) {
                $type = 'comment';
                $options['group'] = ++$this->groupIndex;
            } elseif (! empty($arrays)) {
                $type = 'array_item';

                if (@$newToken['closed']) {
                    if ($token->is(T_COMMENT)) {
                        if ($token->line == $tokens[$key - 1]?->line) {
                            $newToken['comment'] = $token->text;

                            continue;
                        }
                    } elseif ($token->isIgnorable()) {
                        continue;
                    }

                    $this->tokens[] = $newToken;
                    $newToken = null;
                } elseif (
                    $newToken &&
                    $token->is(T_COMMENT) &&
                    @$arrays[count($arrays) - 1] == @$brackets[count($brackets) - 1]
                ) {
                    $newToken['comment'] = $token->text;

                    continue;
                }
                if ($token->is(',') && @$arrays[count($arrays) - 1] == @$brackets[count($brackets) - 1]) {
                    $options['closed'] = true;
                } elseif ($token->is(T_DOUBLE_ARROW) && ! @$newToken['no_value']) {
                    $hasArrayValue = true;
                } elseif (
                    ! $hasArrayValue && ! array_key_exists('value', $newToken ?? []) && ! @$newToken['no_value']
                ) {
                    if (! $token->isIgnorable()) {
                        $text = $token->is(T_CONSTANT_ENCAPSED_STRING)
                            ? stripslashes($token->text)
                            : $token->text;

                        if (@$newToken['type'] == 'array_item' && array_key_exists('array_key', $newToken ?? [])) {
                            $newToken['array_key'] .= $text;
                            $newToken['key'] .= $text;
                        } else {
                            $options['array_key'] = $text;
                            $options['key'] = empty($arrayKeys) ? $text : (implode('.', $arrayKeys).".{$text}");
                            $options['indent'] = (count($arrayKeys) + 1) * 4;
                            $options['group'] = $this->groupIndex;
                        }
                    }

                    $hasArrayValue = false;
                } elseif ($hasArrayValue && ! $token->isIgnorable()) {
                    if ($token->is(T_CONSTANT_ENCAPSED_STRING)) {
                        $options['value'] = trim(stripslashes($token->text), '\'"');
                    } else {
                        $options['no_value'] = true;
                    }

                    $hasArrayValue = false;
                } elseif (
                    $token->is(T_WHITESPACE) &&
                    @$newToken['type'] == 'array_item' &&
                    array_key_exists('value', $newToken ?? [])
                ) {
                    continue;
                }
            }

            if ($newToken && $newToken['type'] != $type) {
                $this->tokens[] = $newToken;
                $newToken = null;
            }

            if (is_null($newToken)) {
                if ($type == 'array_item' && $token->is(T_WHITESPACE)) {
                    continue;
                }

                $newToken = [
                    'type' => $type,
                    'text' => $token->text,
                    ...$options,
                ];
            } elseif ($newToken['type'] == $type) {
                $newToken['text'] .= $token->text;
                $newToken += $options;
            }
        }

        if (! is_null($newToken)) {
            $this->tokens[] = $newToken;
        }

        foreach ($this->tokens as &$token) {
            if ($token['type'] == 'array_item' && ! @$token['close_nested']) {
                $this->items[$token['key']] = $token;
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
        $text = "{$newToken['array_key']} => '".str_replace("'", "\\'", $value)."',";
        $newToken['text'] = $text;
        $newToken['value'] = $value;

        if ($this->has($newToken['key'])) {
            foreach ($this->tokens as &$token) {
                if ($token['type'] == 'array_item' && $token['key'] == $newToken['key'] && ! @$token['closed_nested']) {
                    $token['text'] = $newToken['text'];
                    $token['value'] = $newToken['value'];
                    $this->items[$token['key']] = $token;

                    break;
                }
            }
        } else {
            $index = 0;
            $group = $newToken['group'] > $this->groupIndex ? ($this->groupIndex ? 1 : 0) : $newToken['group'];

            if ($this->groupIndex) {
                foreach ($this->tokens as $token) {
                    if ($token['type'] == 'comment' && $token['group'] == $group) {
                        break;
                    }

                    $index++;
                }

                if ($index > array_key_last($this->tokens)) {
                    $index = 0;
                }
            }

            if (! $index) {
                foreach ($this->tokens as $token) {
                    if ($token['type'] == 'array_open') {
                        break;
                    }

                    $index++;
                }

                if ($index > array_key_last($this->tokens)) {
                    $index = 0;
                }
            }

            if ($index) {
                $newToken['group'] = $group;
                array_splice($this->tokens, $index + 1, 0, [$newToken]);
                $this->items[$newToken['key']] = $newToken;
            }
        }
    }

    public function remove(string $key): void
    {
        $removedToken = @$this->items[$key];

        if (! $removedToken) {
            return;
        }

        foreach ($this->tokens as $i => $token) {
            if ($token['type'] == 'array_item' && $token['key'] == $removedToken['key']) {
                unset($this->tokens[$i]);

                if (@$token['open_nested']) {
                    foreach ($this->tokens as &$childToken) {
                        if (
                            $childToken['type'] == 'array_item' &&
                            $childToken['key'] == "{$removedToken['key']}.{$childToken['array_key']}"
                        ) {
                            $childToken['indent'] -= 4;
                        }
                    }
                } else {
                    break;
                }
            }
        }

        unset($this->items[$key]);
    }

    public function raw(): string
    {
        $code = '';

        usort($this->tokens, function (array $tokenA, array $tokenB) {
            if ($tokenA['type'] != 'array_item' || $tokenB['type'] != 'array_item') {
                return 0;
            }

            if ($tokenA['group'] != $tokenB['group']) {
                return $tokenA['group'] <=> $tokenB['group'];
            }

            if (
                $tokenA['key'] == "{$tokenB['key']}.{$tokenA['array_key']}" ||
                $tokenB['key'] == "{$tokenA['key']}.{$tokenB['array_key']}"
            ) {
                if (@$tokenA['close_nested'] && @$tokenB['close_nested']) {
                    return $tokenB['key'] <=> $tokenA['key'];
                }

                return @$tokenA['close_nested'] <=> @$tokenB['close_nested'] ?: $tokenA['key'] <=> $tokenB['key'];
            }

            return $tokenA['key'] <=> $tokenB['key'];
        });

        foreach ($this->tokens as $key => $token) {
            if ($token['type'] == 'array_open') {
                $code .= "{$token['text']}\n\n";
            } elseif ($token['type'] == 'comment') {
                $code .= "    {$token['text']}\n\n";
            } elseif ($token['type'] == 'array_item') {
                $next = @$this->tokens[$key + 1];

                if (
                    ! @$token['close_nested'] ||
                    ! @$this->tokens[$key - 1]['open_nested'] ||
                    $token['array_key'] != @$this->tokens[$key - 1]['array_key']
                ) {
                    $code .= str_pad('', $token['indent']);
                }

                $code .= $token['text'];

                if (array_key_exists('comment', $token)) {
                    $code .= " {$token['comment']}";
                }

                if (@$next['type'] != 'array_item') {
                    $code .= "\n\n";
                } elseif (! @$next['close_nested'] || $token['array_key'] != @$next['array_key']) {
                    $code .= "\n";
                }
            } else {
                $code .= $token['text'];
            }
        }

        return $code;
    }
}
