<div align="center">
    <h1>⚡ Risma</h1>
    <p><b>The Ultimate String Processing & Template Pipeline for Modern PHP</b></p>
    <p>
        <img src="https://img.shields.io/badge/PHP-%E2%89%A5%207.4-777BB4.svg?style=flat-square&logo=php" alt="PHP Version">
        <img src="https://img.shields.io/badge/License-MIT-green.svg?style=flat-square" alt="License">
        <img src="https://img.shields.io/badge/Dependencies-Zero-brightgreen.svg?style=flat-square" alt="Zero Dependencies">
        <img src="https://img.shields.io/badge/tests-Pest-purple" alt="Tests Pest">
    </p>
</div>



---

**Risma** is not just another template engine. It's a lightweight, high-performance, **zero-dependency** string processing pipeline for PHP.

It completely transforms how you manipulate strings, handle dynamic templates, and sanitize data by introducing a flexible, deeply recursive, and bulletproof pipe-like syntax. No `eval()`, no spaghetti code; just pure, chained logic.

## 🤯 Why Risma? (The Problem vs. The Risma Way)

Ever tried chaining multiple string manipulations in native PHP? It gets ugly, fast.

**❌ Native PHP (Hard to read, inside-out execution):**
```php
$output = sprintf("Status: %s", ucfirst(strtolower(trim($user['status'] ?? 'pending'))));
```

**✅ The Risma Way (Clean, pipeline execution):**
```php
$output = $risma->render("Status: {status.or('pending').trim.strtolower.ucfirst}", $user);
```

---

## ✨ Enterprise-Grade Features

* 🔗 **Infinite Function Chaining:** Pipe data through endless functions using simple dot notation.
* 🎯 **Smart Argument Routing (`$`):** Pinpoint exactly where your piped value should land in the next function's arguments.
* 🛡️ **Bulletproof Zero-Eval Lexer:** Advanced lookahead parser flawlessly handles internal unescaped quotes (e.g., `"sal"am"`) and escaped quotes (`"sal\"am"`) without breaking.
* 🪆 **Deep Recursion (Inception):** Nest placeholders inside function arguments infinitely.
* 🔌 **Ultimate Extensibility:** Inject your own custom functions, static classes, or instantiated objects directly into the engine.
* 🛠️ **Rich Built-in Arsenal:** Comes loaded with logic, formatting, and conditional operators out of the box.

---

## 🚀 Installation

Install via Composer and you are ready to go:

```bash
composer require nabeghe/risma
```

```php
use Nabeghe\Risma\Risma;

// Initialize the engine
$risma = new Risma();
```

---

## 📖 The Master Guide (A to Z)

### 1. Simple Variable Injection
Pass an associative array of data. Risma finds the placeholders and replaces them.

```php
echo $risma->render("Hello {name}!", ['name' => 'Hadi']); 
// Output: Hello Hadi!
```

### 2. The Pipeline (Function Chaining)
Transform data on the fly. By default, the output of the current step becomes the **first argument** of the next function.

```php
$text = "Welcome back, {user.trim.strtoupper}!";
echo $risma->render($text, ['user' => '  alice  ']);
// Output: Welcome back, ALICE!
```

### 3. Smart Argument Parsing
Pass arguments exactly like you would in PHP. Risma's internal lexer is incredibly smart. it handles inner quotes and commas inside strings flawlessly.

```php
// Handles internal unescaped quotes effortlessly!
$text = "Message: {@sprintf(\"%s\", \"He said \"Hello\" to me\")}";
echo $risma->render($text, []);
// Output: Message: He said "Hello" to me
```

### 4. Advanced Argument Routing (`$`)
Not every PHP function accepts the target string as the first argument (we're looking at you, `str_replace`). Use `$` to tell Risma exactly where to inject the piped value!

```php
// str_replace(search, replace, subject) -> Subject is the 3rd argument.
$text = "Formatted: {slug.str_replace('-', ' ', '$')}";
echo $risma->render($text, ['slug' => 'open-source-is-awesome']);
// Output: Formatted: open source is awesome
```

### 5. Direct Execution (`@`)
Need to execute a function to generate a root value without relying on a variable? Start with `@`.

```php
echo $risma->render("Copyright {@date('Y')} - {@rand(1, 100)}", []);
// Output: Copyright 2026 - 42
```

### 6. Deep Nesting & Recursion
Risma recursively resolves placeholders from the inside out. You can embed placeholders inside function arguments of other placeholders!

```php
$template = '{@sprintf("%s %s", "{@ucfirst("{first}")}", "{@ucfirst("{last}")}")}';
echo $risma->render($template, ['first' => 'hadi', 'last' => 'akbarzadeh']);
// Output: Hadi Akbarzadeh
```

### 7. Escaping Variables (`!`)
If you need to render the literal `{braces}` without Risma parsing them, prefix with `!`.

```php
echo $risma->render("Use !{variable} to write a variable.", []);
// Output: Use {variable} to write a variable.
```

---

## 🧰 The Built-in Arsenal (`Functions.php`)

Risma includes a powerful suite of native helpers. You can chain them endlessly to achieve complex logical operations directly inside your string.

### Logic & Conditionals
* **`ok`**: Returns `'1'` if truthy, `'0'` if falsy. *(Great for boolean flags)*
* **`exists`**: Returns `'1'` if not null/empty, otherwise `'0'`.
* **`or('default')`**: Fallback value if the variable is empty.
* **`and('suffix')`**: Appends text *only* if the variable is not empty.
* **`if_empty('yes', 'no')`** / **`if_not_empty('yes', 'no')`**: Conditional text based on emptiness.
* **`if_blank('yes', 'no')`** / **`if_not_blank('yes', 'no')`**: Like empty, but also treats invisible unicode spaces as blank!
* **`if_equals('target', 'yes', 'no')`**: Strict comparison.
* **`if_numeric('yes', 'no')`**: Checks if the piped value is a number.

*(Note: You can use `%s` in the 'yes' or 'no' arguments to inject the original value!)*

### String Manipulation
* **`prepend('prefix1', 'prefix2', ...)`**: Adds strings to the beginning.
* **`append('suffix1', 'suffix2', ...)`**: Adds strings to the end.
* **`flatten_lines`**: Squashes multi-line text (`\n`, `\r\n`) into a single, clean space-separated line.
* **`remove_lines`**: Completely strips line breaks.
* **`maybe_plural_s`**: Returns `'s'` if the piped integer is > 1.
* **`line`**: Generates a raw `\n` line break.

---

## 🤯 Real-World Scenario: Complex Notification System

Let's combine everything into a single, highly complex template that formats an email notification, handles missing data, flattens line breaks, and evaluates conditionals—all in one string!

```php
$template = 'Subject: {@sprintf("New message from %s", "{sender.trim.or(\"Unknown User\")}")} ' .
            '| Body: {message.flatten_lines.if_empty("No content.", ">> %s <<")} ' .
            '| Attachments: {attach_count} file{attach_count.maybe_plural_s} ' .
            '| Status: {is_vip.ok.if_equals("1", "[VIP Member]", "[Standard]")}';

$data = [
    'sender'       => '  Hadi "Danger" Akbarzadeh  ', // Internal quotes & spaces
    'message'      => "Hello!\r\nThis is a test message\nwith \"quotes\".", // Messy multiline
    'attach_count' => 3, 
    'is_vip'       => true,
];

echo $risma->render($template, $data);
```
**Output:**
> `Subject: New message from Hadi "Danger" Akbarzadeh | Body: >> Hello! This is a test message with "quotes". << | Attachments: 3 files | Status: [VIP Member]`

---

## 🧩 Extending Risma (Bring Your Own Logic)

Risma is designed to be your engine. You can mount your own functions, classes, and objects to the pipeline.

### Register Custom Callbacks
```php
$risma->addFunc('mask_email', function($email) {
    return substr($email, 0, 3) . '***@***.com';
});
echo $risma->render("{email.mask_email}", ['email' => 'hadi@nabeghe.com']);
// Output: had***@***.com
```

### Register Static Classes
Map an entire class. Risma will check this class for methods during the chain.
```php
class TextUtils {
    public static function bold($text) { return "<b>$text</b>"; }
}
$risma->addClass(TextUtils::class);
echo $risma->render("{title.bold}", ['title' => 'Risma']);
// Output: <b>Risma</b>
```

### Register Objects
You can even inject an instantiated object to preserve state!
```php
$translator = new MyTranslator();
$risma->addObject($translator); // Now all public methods of $translator are available!
```

---

## ⚙️ Engine API Reference

| Method | Description |
| :--- | :--- |
| `render(string $text, array $vars, bool $default = true): string` | Core compiler. If `$default` is false, throws Exception on missing vars. |
| `addFunc(string $name, callable $callback): void` | Registers a custom pipeline function. |
| `addClass(string $className, bool $prepend = false): void` | Mounts a static class to the resolver. |
| `addObject(object $object, bool $prepend = false): void` | Mounts an instantiated object to the resolver. |

---

## 📜 License

Created with ❤️ by Nabeghe. Licensed under the [MIT License](LICENSE.md). Free to use, modify, and distribute!
