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
                'Hello {name}', ['name' => 'World'], 'Hello World'
            ],
            'Function chaining (lowercase then ucfirst)' => [
                '{name.strtolower.ucfirst}', ['name' => 'NABEGHE'], 'Nabeghe'
            ],
            'Handling whitespace inside tags' => [
                'Result: {  version  .  trim  }', ['version' => ' 1.0 '], 'Result: 1.0'
            ],
            'Escaped tags using exclamation mark' => [
                'Display !{this} literally', [], 'Display {this} literally'
            ],
            'Native PHP function with custom placeholder' => [
                'Slug: {title.str_replace(" ", "-", "$")}', // '$' MUST be in quotes to be parsed as a string by eval
                ['title' => 'hello world'],
                'Slug: hello-world'
            ],
            'Direct function call using @ symbol' => [
                'Year: {@date("Y")}', [], 'Year: ' . date('Y')
            ],
            'Built-in "exists" function (truthy)' => [
                '{val.exists}', ['val' => 'something'], '1'
            ],
            'Built-in "exists" function (falsy)' => [
                '{val.exists}', ['val' => ''], '0'
            ],
        ];
    }

    /**
     * Test adding custom functions.
     */
    public function testCustomFunctionRegistration(): void
    {
        $this->risma->addFunc('prefix', function($value, $prefix) {
            return $prefix . $value;
        });

        $result = $this->risma->render('{name.prefix("Mr. ")}', ['name' => 'John']);
        $this->assertSame('Mr. John', $result);
    }

    /**
     * Test adding class methods.
     */
    public function testClassMethodIntegration(): void
    {
        $mockClass = new class {
            public static function bold($text) {
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
}
