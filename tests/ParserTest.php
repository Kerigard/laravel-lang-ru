<?php

namespace Kerigard\LaravelLangRu\Tests;

use Kerigard\LaravelLangRu\Contracts\Parser;

class ParserTest extends TestCase
{
    public function test_parse_code(): void
    {
        /** @var \Kerigard\LaravelLangRu\Contracts\Parser */
        $parser = app(Parser::class, ['code' => file_get_contents(lang_path('en/validation.php'))]);

        $this->assertTrue($parser->exists());

        $parser->parse();

        foreach ($parser->items() as $key => $token) {
            if (! in_array($key, ["'required'", "'password'", "'password'.'numbers'"])) {
                $parser->remove($key);
            }
        }

        $items = $parser->items();

        $this->assertCount(3, $items);
        $this->assertArrayHasKey("'required'", $items);
        $this->assertTrue($parser->has("'required'"));
        $this->assertArrayHasKey("'password'.'numbers'", $items);
        $this->assertTrue($parser->has("'password'.'numbers'"));
        $this->assertArrayHasKey('key', $items["'password'.'numbers'"]);
        $this->assertArrayHasKey('array_key', $items["'password'.'numbers'"]);
        $this->assertArrayHasKey('value', $items["'password'.'numbers'"]);

        $parser->set([
            'type' => 'array_item',
            'array_key' => "'b-test'",
            'key' => "'b-test'",
            'value' => 'B test.',
            'indent' => 4,
            'group' => 2,
        ], 'Test B TR.');
        $parser->set([
            'type' => 'array_item',
            'array_key' => "'a-test'",
            'key' => "'a-test'",
            'value' => 'A test.',
            'indent' => 4,
            'group' => 2,
        ], 'Test A TR.');
        $parser->set([
            'type' => 'array_item',
            'array_key' => "'c-test'",
            'key' => "'c-test'",
            'value' => 'C test.',
            'indent' => 4,
            'group' => 2,
        ], 'Test C TR.');

        $content = <<<'EOT'
            <?php

            return [

                /*
                |--------------------------------------------------------------------------
                | Validation Language Lines
                |--------------------------------------------------------------------------
                |
                | The following language lines contain the default error messages used by
                | the validator class. Some of these rules have multiple versions such
                | as the size rules. Feel free to tweak each of these messages here.
                |
                */

                'password' => [
                    'numbers' => 'The :attribute field must contain at least one number.',
                ],
                'required' => 'The :attribute field is required.',

                /*
                |--------------------------------------------------------------------------
                | Custom Validation Language Lines
                |--------------------------------------------------------------------------
                |
                | Here you may specify custom validation messages for attributes using the
                | convention "attribute.rule" to name the lines. This makes it quick to
                | specify a specific custom language line for a given attribute rule.
                |
                */

                'a-test' => 'Test A TR.',
                'b-test' => 'Test B TR.',
                'c-test' => 'Test C TR.',

                /*
                |--------------------------------------------------------------------------
                | Custom Validation Attributes
                |--------------------------------------------------------------------------
                |
                | The following language lines are used to swap our attribute placeholder
                | with something more reader friendly such as "E-Mail Address" instead
                | of "email". This simply helps us make our message more expressive.
                |
                */

            ];

            EOT;

        $this->assertEquals($content, $parser->raw());
    }

    public function test_parse_json_code()
    {
        /** @var \Kerigard\LaravelLangRu\Contracts\Parser */
        $parser = app(Parser::class, [
            'extension' => 'json',
            'code' => json_encode(require lang_path('en/validation.php')),
        ]);

        $this->assertTrue($parser->exists());

        $parser->parse();

        foreach ($parser->items() as $key => $item) {
            if (! in_array($key, ['required', 'accepted_if'])) {
                $parser->remove($key);
            }
        }

        $items = $parser->items();

        $this->assertCount(2, $items);
        $this->assertArrayHasKey('required', $items);
        $this->assertTrue($parser->has('required'));
        $this->assertArrayHasKey('accepted_if', $items);
        $this->assertTrue($parser->has('accepted_if'));
        $this->assertArrayHasKey('key', $items['accepted_if']);
        $this->assertArrayHasKey('array_key', $items['accepted_if']);
        $this->assertArrayHasKey('value', $items['accepted_if']);

        $parser->set([
            'type' => 'item',
            'array_key' => 'b-test',
            'key' => 'b-test',
            'value' => 'B test.',
        ], 'Test B TR.');
        $parser->set([
            'type' => 'item',
            'array_key' => 'a-test',
            'key' => 'a-test',
            'value' => 'A test.',
        ], 'Test A TR.');

        $content = <<<'EOT'
            {
                "a-test": "Test A TR.",
                "accepted_if": "The :attribute field must be accepted when :other is :value.",
                "b-test": "Test B TR.",
                "required": "The :attribute field is required."
            }
            EOT;

        $this->assertEquals($content, $parser->raw());
    }
}
