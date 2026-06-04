<?php

namespace Tests;

use Exception;
use Nabeghe\Risma\Risma;

beforeEach(function () {
    $this->risma = new Risma();
});

dataset('templates', [
    'Basic variable replacement' => [
        'Hello {name}',
        ['name' => 'World'],
        'Hello World',
    ],

    'Function chaining (lowercase then ucfirst)' => [
        '{name.strtolower.ucfirst}',
        ['name' => 'NABEGHE'],
        'Nabeghe',
    ],

    'Handling whitespace inside tags' => [
        'Result: {  version  .  trim  }',
        ['version' => ' 1.0 '],
        'Result: 1.0',
    ],

    'Escaped tags using exclamation mark' => [
        'Display !{this} literally',
        [],
        'Display {this} literally',
    ],

    'Native PHP function with custom placeholder' => [
        'Slug: {title.str_replace(" ", "-", "$")}',
        ['title' => 'hello world'],
        'Slug: hello-world',
    ],

    'Direct function call using @ symbol (strval then md5)' => [
        'Hash: {@strval("nabeghe/risma-php").md5}',
        [],
        'Hash: ab49b39ee2325ba712f1cbb2472757da',
    ],
    'Direct function call using @ symbol then function chaining' => [
        'Year: {@date("Y")}',
        [],
        'Year: '.date('Y'),
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

    'Internal unescaped quotes (Double)' => [
        'Output: {@str_replace("l"o", "y", "hel"o world")}',
        [],
        'Output: hey world',
    ],
    'Internal unescaped quotes (Single)' => [
        "Output: {@str_replace('l'o', 'y', 'hel'o world')}",
        [],
        'Output: hey world',
    ],
    'Escaped double quotes' => [
        'Output: {@str_replace("l\"o", "y", "hel\"o world")}',
        [],
        'Output: hey world',
    ],
    'Mixed quotes with extra spaces around arguments' => [
        'Output: {@str_replace( "bad"word" , "good" , "this is a bad"word" )}',
        [],
        'Output: this is a good',
    ],
    'Nested brackets and quotes' => [
        'Output: {@sprintf("He said: %s", "sal"am")}',
        [],
        'Output: He said: sal"am',
    ],

    'Custom function: and (with value)' => [
        '{name.and(" is present")}',
        ['name' => 'Hadi'],
        'Hadi is present',
    ],
    'Custom function: and (empty value)' => [
        '{name.and(" is present")}',
        ['name' => ''],
        '',
    ],
    'Custom function: append multiple arguments' => [
        '{name.append(" - ", "Developer")}',
        ['name' => 'Risma'],
        'Risma - Developer',
    ],
    'Custom function: exists (truthy)' => [
        'Exists: {var.exists}',
        ['var' => 'test'],
        'Exists: 1',
    ],
    'Custom function: exists (falsy)' => [
        'Exists: {var.exists}',
        ['var' => ''],
        'Exists: 0',
    ],
    'Custom function: flatten_lines' => [
        "Text: {desc.flatten_lines}",
        ['desc' => "Line 1\n  Line 2 \r\n Line 3\r"],
        'Text: Line 1 Line 2 Line 3',
    ],
    'Custom function: if_empty (when empty)' => [
        '{val.if_empty("It is empty", "Not empty")}',
        ['val' => ''],
        'It is empty',
    ],
    'Custom function: if_empty (when not empty using %s)' => [
        '{val.if_empty("Empty", "Value is: %s")}',
        ['val' => 'Data'],
        'Value is: Data',
    ],
    'Custom function: if_equals (match)' => [
        '{status.if_equals("active", "It is active", "Inactive")}',
        ['status' => 'active'],
        'It is active',
    ],
    'Custom function: if_equals (mismatch)' => [
        '{status.if_equals("active", "It is active", "Inactive")}',
        ['status' => 'pending'],
        'Inactive',
    ],
    'Custom function: if_blank (when blank with spaces)' => [
        '{val.if_blank("Is Blank", "Not Blank")}',
        ['val' => '   '],
        'Is Blank',
    ],
    'Custom function: if_not_blank (when not blank)' => [
        '{val.if_not_blank("Has content: %s", "No content")}',
        ['val' => '   '],
        'No content',
    ],
    'Custom function: if_not_empty (when not empty)' => [
        '{val.if_not_empty("Content: %s", "Empty")}',
        ['val' => 'Risma'],
        'Content: Risma',
    ],
    'Custom function: if_not_empty (when empty)' => [
        '{val.if_not_empty("Content", "It is empty")}',
        ['val' => ''],
        'It is empty',
    ],
    'Custom function: if_numeric (is numeric)' => [
        '{price.if_numeric("Price is %s", "Invalid")}',
        ['price' => '1500'],
        'Price is 1500',
    ],
    'Custom function: if_numeric (not numeric)' => [
        '{price.if_numeric("Price is %s", "Invalid")}',
        ['price' => 'free'],
        'Invalid',
    ],
    'Custom function: line' => [
        '{@line}',
        [],
        "\n",
    ],
    'Custom function: maybe_plural_s (singular)' => [
        'item{count.maybe_plural_s}',
        ['count' => '1'],
        'item',
    ],
    'Custom function: maybe_plural_s (plural)' => [
        'item{count.maybe_plural_s}',
        ['count' => '5'],
        'items',
    ],
    'Custom function: ok (truthy)' => [
        'Status: {isActive.ok}',
        ['isActive' => true],
        'Status: 1',
    ],
    'Custom function: ok (falsy)' => [
        'Status: {isActive.ok}',
        ['isActive' => false],
        'Status: 0',
    ],
    'Custom function: or (when empty)' => [
        '{name.or("Anonymous")}',
        ['name' => ''],
        'Anonymous',
    ],
    'Custom function: or (when not empty)' => [
        '{name.or("Anonymous")}',
        ['name' => 'Alice'],
        'Alice',
    ],
    'Custom function: prepend multiple arguments' => [
        '{name.prepend("Hey. ", "Developer ")}',
        ['name' => 'Hadi'],
        'Hey. Developer Hadi',
    ],
    'Custom function: remove_lines' => [
        "Text: {desc.remove_lines}",
        ['desc' => "Line 1\nLine 2\r\nLine 3\r"],
        'Text: Line 1Line 2Line 3',
    ],

    'Complex real-world scenario: Email Notification System' => [
        // Template
        'Subject: {@sprintf("New message from %s", "{sender.trim.or(\"Unknown User\")}")} ' .
        '| Body: {message.flatten_lines.if_empty("No content.", ">> %s <<")} ' .
        '| Attachments: {attach_count} file{attach_count.maybe_plural_s} ' .
        '| Status: {is_vip.ok.if_equals("1", "[VIP Member]", "[Standard]")}',
        // Variables (Vars)
        [
            'sender' => '  Hadi "Danger" Akbarzadeh  ',
            'message' => "Hello!\r\nThis is a test message\nwith \"quotes\".",
            'attach_count' => '3',
            'is_vip' => true,
        ],
        // Expected Output
        'Subject: New message from Hadi "Danger" Akbarzadeh ' .
        '| Body: >> Hello! This is a test message with "quotes". << ' .
        '| Attachments: 3 files ' .
        '| Status: [VIP Member]'
    ],
]);

it('renders templates correctly', function ($template, $vars, $expected) {
    $actual = $this->risma->render($template, $vars);
    $actualNormalized = trim((string) $actual);
    $expectedNormalized = trim((string) $expected);
    expect((string) $actual)->toBe($expected);
})->with('templates');

it('registers custom functions', function () {
    $this->risma->addFunc('prefix2', function ($value, $prefix) {
        return $prefix . $value;
    });

    $result = $this->risma->render('{name.prefix2("Hey. ")}', [
        'name' => 'Hadi'
    ]);

    expect($result)->toBe('Hey. Hadi');
});

it('supports class methods', function () {
    $mockClass = new class {
        public static function bold($text)
        {
            return "**$text**";
        }
    };

    $this->risma->addClass(get_class($mockClass));

    $result = $this->risma->render('{word.bold}', [
        'word' => 'Risma'
    ]);

    expect($result)->toBe('**Risma**');
});

it('throws exception when variable missing and default is false', function () {
    expect(fn () => $this->risma->render('{missing_var}', [], false))
        ->toThrow(Exception::class);
});

it('supports complex chaining', function () {
    $template = "Price: {amount.number_format(2, '.', ',')}";

    $result = $this->risma->render($template, [
        'amount' => 1500.5
    ]);

    expect($result)->toBe('Price: 1,500.50');
});

it('supports deep nesting with custom functions', function () {
    $this->risma->addFunc('wrap', function ($value, $before, $after) {
        return $before . $value . $after;
    });

    $template = '{@wrap("{@strtoupper("{name}")}", "[", "]")}';

    $result = $this->risma->render($template, [
        'name' => 'test'
    ]);

    expect($result)->toBe('[TEST]');
});

it('handles escaped braces correctly', function () {
    $template = '{@sprintf("!{%s}", "{name}")}';

    $result = $this->risma->render($template, [
        'name' => 'placeholder'
    ]);

    expect($result)->toBe('{placeholder}');
});

it('handles internal and escaped quotes correctly inside arguments', function () {
    $template = '1: {@sprintf("%s", "sal\"am")} | 2: {@sprintf("%s", "sal"am")} | 3: {@sprintf("%s", \'sal\\\'am\')}';

    $result = $this->risma->render($template, []);

    expect($result)->toBe('1: sal"am | 2: sal"am | 3: sal\'am');
});
