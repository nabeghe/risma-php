# Risma for PHP ≥ 7.4

> A lightweight, flexible string processing and template engine for PHP with function chaining support.

**Risma** is a lightweight, high-performance string processing engine for PHP.
It allows you to transform raw text into dynamic content using a flexible, pipe-like syntax.
Whether you're building a template system, a dynamic notification builder, or a content sanitizer,
Risma provides a clean interface to chain functions and variables seamlessly.

---

## Key Features

* **Variable Injection:** Easily replace placeholders with dynamic data.
* **Function Chaining:** Pipe data through multiple functions using dot notation.
* **Global & Custom Functions:** Use native PHP functions or register your own logic.
* **Class Integration:** Directly map class methods to your processing pipeline.
* **Escaping Mechanism:** Built-in support for literal braces using `!{}`.
* **Clean Syntax:** Intuitive `{var.func1.func2}` syntax.
- Compatible with PHP 7.4+

---

## 🫡 Usage

### 🚀 Installation

You can install the package via composer:

```bash
composer require nabeghe/risma
```

Or manually include the Risma.php if you want to keep it old school.

```php
use Nabeghe\Risma\Risma;

$risma = new Risma();
````

## Basic Example

### 1. Simple Variables

Pass an associative array of variables to the render method.

```php
$text = "Hello {name}!";
echo $risma->render($text, ['name' => 'Hadi']); 
// Output: Hello Hadi!
```

### 2. Function Chaining

Transform variables on the fly by chaining functions. The output of one function becomes the first argument of the next.

```php
// Using native PHP functions (strtoupper)
$text = "Welcome, {user.strtoupper}!";
echo $risma->render($text, ['user' => 'alice']);
// Output: Welcome, ALICE!
```

### 3. Arguments & Native Logic

You can pass arguments to functions just like in PHP.

```php
$text = "Profile: {slug.str_replace('-', ' ')}";
echo $risma->render($text, ['slug' => 'hello-world']);
// Output: Profile: hello world
```

### 4. Direct Function Calls (@)

Starting an expression with @ triggers a direct function call without requiring a variable.

```php
$text = "Current Year: {@date('Y')}";
echo $risma->render($text, []);
// Output: Current Year: 2026
```

## Advanced Configuration

### Custom Functions

Register specific callbacks that are only available within Risma.

```php
$risma->addFunc('greet', function($name) {
    return "Hi, " . ucfirst($name);
});

echo $risma->render("{name.greet}", ['name' => 'Hadi']);
// Output: Hi, Hadi
```

### Registering Classes

Make an entire class's methods available to your text processor.

```php
class StringHelper {
    public static function bold($text) {
        return "<b>$text</b>";
    }
}

$risma->addClass(StringHelper::class);
echo $risma->render("{title.bold}", ['title' => 'Risma']);
// Output: <b>Risma</b>
```

### Escaping

If you need to display the braces literally, prefix them with an exclamation mark.

echo $risma->render("This is !{ignored}", []);
// Output: This is {ignored}

## API Reference

`render(string $text, array $vars, bool $default = true): string`
Processes the string.

* $text: The raw string containing `{}` tags.
* $vars: Key-value pair of data.
* $default: If `true`, returns empty string for missing variables. If `false`, throws an Exception.

`addFunc(string $name, callable $callback): void`
Registers a custom function to the internal engine.

`addClass(string $className): void`
Registers a class. Risma will look for methods within this class when processing chains.

### Syntax Rules at a Glance

| Syntax | Description |
| :--- | :--- |
| `{var}` | Simple variable replacement. |
| `{var.func}` | Pass variable to `func`. |
| `{var.f1.f2}` | Chain: `f2(f1(var))`. |
| `{@func()}` | Execute a function directly without a variable. |
| `!{var}` | Escape the tag (returns `{var}`). |
| `{var.func('arg')}` | Pass additional arguments. |

## 📖 License

Licensed under the MIT license, see [LICENSE.md](LICENSE.md) for details.
