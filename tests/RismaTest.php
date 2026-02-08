<?php

namespace Nabeghe\Risma\Tests;

use Nabeghe\Risma\Risma;
use PHPUnit\Framework\TestCase;
use Exception;

class RismaTest extends TestCase
{
    protected Risma $risma;

    protected function setUp(): void
    {
        $this->risma = new Risma();
    }

    /**
     * Test a wide range of formatting scenarios.
     * @dataProvider templateProvider
     */
    public function testRenderingScenarios(string $template, array $vars, string $expected): void
    {
        $actual = $this->risma->render($template, $vars);
        $this->assertSame($expected, $actual);
    }

    public function templateProvider(): array
    {
        return [
            'Basic variable replacement' => [
                'Hello {name}', ['name' => 'World'], 'Hello World',
            ],
            'Function chaining (lowercase then ucfirst)' => [
                '{name.strtolower.ucfirst}', ['name' => 'NABEGHE'], 'Nabeghe',
            ],
            'Handling whitespace inside tags' => [
                'Result: {  version  .  trim  }', ['version' => ' 1.0 '], 'Result: 1.0',
            ],
            'Escaped tags using exclamation mark' => [
                'Display !{this} literally', [], 'Display {this} literally',
            ],
            'Native PHP function with custom placeholder' => [
                'Slug: {title.str_replace(" ", "-", "$")}',
                ['title' => 'hello world'],
                'Slug: hello-world',
            ],
            'Direct function call using @ symbol' => [
                'Year: {@date("Y")}', [], 'Year: '.date('Y'),
            ],
            'Built-in "exists" function (truthy)' => [
                '{val.exists}', ['val' => 'something'], '1',
            ],
            'Built-in "exists" function (falsy)' => [
                '{val.exists}', ['val' => ''], '0',
            ],
            'Simple nested placeholder in function argument' => [
                '{@strtoupper("{name}")}',
                ['name' => 'hadi'],
                'HADI',
            ],
            'Nested placeholder with function chain' => [
                '{@str_repeat("{char.strtoupper}", 3)}',
                ['char' => 'x'],
                'XXX',
            ],
            'Double nesting with variables' => [
                '{@trim("{@strtoupper("{name}")}")}',
                ['name' => 'alice'],
                'ALICE',
            ],
            'Nested placeholder with multiple arguments' => [
                '{@str_replace("{old}", "{new}", "{text}")}',
                ['old' => 'foo', 'new' => 'bar', 'text' => 'hello foo world'],
                'hello bar world',
            ],
            'Nested with custom function chain' => [
                '{prefix.append("{suffix}")}',
                ['prefix' => 'Start', 'suffix' => 'End'],
                'StartEnd',
            ],
            'Triple nested placeholders' => [
                '{@sprintf("%s %s", "{@ucfirst("{first}")}", "{@ucfirst("{last}")}")}',
                ['first' => 'hadi', 'last' => 'akbarzadeh'],
                'Hadi Akbarzadeh',
            ],
            'Nested in middle of string' => [
                'User: {@strtoupper("{name}")} - Age: {age}',
                ['name' => 'bob', 'age' => '25'],
                'User: BOB - Age: 25',
            ],
            'Complex nested with number formatting' => [
                'Price: {@number_format({amount}, 2, ".", ",")} for {item}',
                ['amount' => 1234.567, 'item' => 'Book'],
                'Price: 1,234.57 for Book',
            ],
        ];
    }

    /**
     * Test adding custom functions.
     */
    public function testCustomFunctionRegistration(): void
    {
        $this->risma->addFunc('prefix', function ($value, $prefix) {
            return $prefix.$value;
        });

        $result = $this->risma->render('{name.prefix("Mr. ")}', ['name' => 'John']);
        $this->assertSame('Mr. John', $result);
    }

    /**
     * Test adding class methods.
     */
    public function testClassMethodIntegration(): void
    {
        $mockClass = new class
        {
            public static function bold($text)
            {
                return "**$text**";
            }
        };

        $this->risma->addClass(get_class($mockClass));
        $result = $this->risma->render('{word.bold}', ['word' => 'Risma']);
        $this->assertSame('**Risma**', $result);
    }

    /**
     * Test behavior when a variable is missing and default is false.
     */
    public function testThrowsExceptionWhenVariableMissingAndDefaultIsFalse(): void
    {
        $this->expectException(Exception::class);
        $this->risma->render('{missing_var}', [], false);
    }

    /**
     * Test nested/complex chain strings.
     */
    public function testComplexChaining(): void
    {
        $template = "Price: {amount.number_format(2, '.', ',')}";
        $result = $this->risma->render($template, ['amount' => 1500.5]);
        $this->assertSame('Price: 1,500.50', $result);
    }

    /**
     * Test deeply nested placeholders with custom functions.
     */
    public function testDeeplyNestedWithCustomFunctions(): void
    {
        $this->risma->addFunc('wrap', function ($value, $before, $after) {
            return $before.$value.$after;
        });

        $template = '{@wrap("{@strtoupper("{name}")}", "[", "]")}';
        $result = $this->risma->render($template, ['name' => 'test']);
        $this->assertSame('[TEST]', $result);
    }

    /**
     * Test nested placeholders with escaped braces.
     */
    public function testNestedWithEscapedBraces(): void
    {
        $template = '{@sprintf("!{%s}", "{name}")}';
        $result = $this->risma->render($template, ['name' => 'placeholder']);
        $this->assertSame('{placeholder}', $result);
    }
}
