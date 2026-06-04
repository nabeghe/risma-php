<?php namespace Nabeghe\Risma;

use Exception;
use Throwable;

/**
 * Risma
 * A lightweight and flexible string processing engine for PHP.
 * developed by [https://github.com/nabeghe](https://github.com/nabeghe).
 */
class Risma
{
    /**
     * @var array Custom functions registry.
     */
    protected array $funcs = [];

    /**
     * @var array Registered classes for method resolution.
     */
    protected array $classes = [Functions::class];

    /**
     * @var array Registered objects for method resolution.
     */
    protected array $objects = [];

    /**
     * @var int Maximum recursion depth to prevent infinite loops.
     */
    protected int $maxDepth = 10;

    /**
     * @var int Current recursion depth tracker.
     */
    protected int $currentDepth = 0;

    /**
     * Register a custom function.
     *
     * @param  string  $name  The name used in the template.
     * @param  callable  $callback  The logic to execute.
     * @param  bool  $prepend  Add to beginning instead of end.
     */
    public function addFunc(string $name, callable $callback, bool $prepend = false): void
    {
        if ($prepend) {
            $this->funcs = [$name => $callback] + $this->funcs;
        } else {
            $this->funcs[$name] = $callback;
        }
    }

    /**
     * Register a class to expose its methods to the engine.
     *
     * @param  string  $className  Full namespace of the class.
     * @param  bool  $prepend  Add to beginning instead of end.
     */
    public function addClass(string $className, bool $prepend = false): void
    {
        if (class_exists($className)) {
            $prepend
                ? array_unshift($this->classes, $className)
                : $this->classes[] = $className;
        }
    }

    /**
     * Register an object to expose its methods to the engine.
     *
     * @param  object  $object  The object.
     * @param  bool  $prepend  Add to beginning instead of end.
     */
    public function addObject(object $object, bool $prepend = false): void
    {
        $prepend
            ? array_unshift($this->objects, $object)
            : $this->objects[] = $object;
    }

    /**
     * Renders the template string by replacing placeholders.
     * Uses recursive regex to match nested braces correctly.
     *
     * @param  string  $text  The raw string with placeholders like {var.func}.
     * @param  array  $vars  Key-value pairs of data.
     * @param  bool  $default  If true, returns empty string for missing variables.
     * @return string The processed text.
     * @throws Exception
     */
    public function render(string $text, array $vars, bool $default = true): string
    {
        // Reset depth counter at the start of render
        $this->currentDepth = 0;

        // Use recursive regex pattern to match balanced curly braces
        // (?1) refers to the first capturing group recursively
        $pattern = '/(!?)\{\s*((?:[^{}]|(?R))*)\s*\}/';

        return preg_replace_callback($pattern, function ($matches) use ($vars, $default) {
            $isEscaped = !empty($matches[1]);
            $content = $matches[2];

            if ($isEscaped) {
                return '{'.$content.'}';
            }

            // Check recursion depth
            $this->currentDepth++;
            if ($this->currentDepth > $this->maxDepth) {
                $this->currentDepth--;
                throw new Exception("Maximum recursion depth exceeded.");
            }

            try {
                // First recursively render any nested placeholders
                $renderedContent = $this->render($content, $vars, $default);

                // Then process the expression itself
                $result = $this->processExpression($renderedContent, $vars, $default);

                $this->currentDepth--;
                return $result;
            } catch (Throwable $e) {
                $this->currentDepth--;

                // Modified logic: If default is false, re-throw the exception for PHPUnit
                if (!$default) {
                    throw $e;
                }
                return '';
            }
        }, $text);
    }

    /**
     * Processes the content inside a single {} block.
     *
     * @throws Exception
     */
    protected function processExpression(string $expression, array $vars, bool $default): string
    {
        // Split the chain while respecting parentheses and quotes
        $chain = $this->splitChain($expression);

        if (empty($chain)) {
            return '';
        }

        $head = array_shift($chain);
        $value = null;

        // Check for direct function call (starts with @)
        if (substr($head, 0, 1) === '@') {
            $funcName = substr($head, 1);
            $value = $this->executeFunction($funcName, null, true);
        } else {
            // Handle variable replacement
            if (array_key_exists($head, $vars)) {
                $value = $vars[$head];
            } else {
                if ($default) {
                    $value = '';
                } else {
                    throw new Exception("Variable '$head' is undefined.");
                }
            }
        }

        // Process the rest of the function chain
        foreach ($chain as $item) {
            $value = $this->executeFunction($item, $value, false);
        }

        return (string) $value;
    }

    /**
     * Executes a single function within the chain.
     *
     * @param  string  $token  The function part, e.g., "func1" or "func('arg')".
     * @param  mixed  $prevValue  The value from the previous step in the chain.
     * @param  bool  $isFirstCall  Whether this is the start of a direct @ call.
     * @throws Exception
     */
    protected function executeFunction(string $token, $prevValue, bool $isFirstCall)
    {
        if (preg_match('/^([a-zA-Z0-9_\\\:]+)(?:\((.*)\))?$/s', $token, $matches)) {
            $funcName = $matches[1];
            $argsString = $matches[2] ?? null;

            $callable = $this->resolveCallback($funcName);
            $args = [];

            if ($argsString !== null && trim($argsString) !== '') {
                try {
                    // Replaced eval() with a safe custom parser
                    $args = $this->parseArguments($argsString);
                } catch (Throwable $e) {
                    throw new Exception("Error parsing arguments for '$funcName'.");
                }
            }

            if (!$isFirstCall) {
                // Check if user specified where the piped value should go using '$'
                $placeholderIndex = array_search('$', $args, true);

                if ($placeholderIndex !== false) {
                    // Replace '$' with the actual previous value
                    $args[$placeholderIndex] = $prevValue;
                } else {
                    // Default behavior: prepend to arguments
                    array_unshift($args, $prevValue);
                }
            }

            return call_user_func_array($callable, $args);
        }

        throw new Exception("Invalid syntax: $token");
    }

    /**
     * Safely parses arguments without eval(), supporting internal unescaped quotes and escaped quotes.
     */
    protected function parseArguments(string $argsString): array
    {
        $args = [];
        $buffer = '';
        $inQuote = false;
        $quoteChar = '';
        $len = strlen($argsString);
        $isValueQuoted = false;

        for ($i = 0; $i < $len; $i++) {
            $char = $argsString[$i];

            if ($inQuote) {
                // Convert escaped quotes (e.g., \") to actual quotes and ignore the backslash
                if ($char === '\\' && $i + 1 < $len && ($argsString[$i + 1] === '"' || $argsString[$i + 1] === "'")) {
                    $buffer .= $argsString[$i + 1];
                    $i++;
                    continue;
                }

                if ($char === $quoteChar) {
                    $isEnd = true;
                    // Lookahead to detect if the quote is truly the end of the input
                    for ($j = $i + 1; $j < $len; $j++) {
                        $nextChar = $argsString[$j];
                        if (trim($nextChar) === '') {
                            continue; // Skip spaces
                        }
                        // If a comma or closing parenthesis is reached, the string is truly finished
                        if ($nextChar === ',' || $nextChar === ')') {
                            break;
                        }
                        $isEnd = false;
                        break;
                    }

                    if ($isEnd) {
                        $inQuote = false;
                        $quoteChar = '';
                    } else {
                        // It's an internal quote (e.g., "sal"am")
                        $buffer .= $char;
                    }
                } else {
                    $buffer .= $char;
                }
            } else {
                if ($char === '"' || $char === "'") {
                    // Start of a new quoted string
                    if (trim($buffer) === '') {
                        $buffer = ''; // Clear potential spaces before the quote starts
                        $inQuote = true;
                        $quoteChar = $char;
                        $isValueQuoted = true;
                    } else {
                        $buffer .= $char;
                    }
                } elseif ($char === ',') {
                    // Argument boundary
                    $args[] = $this->finalizeArgument($buffer, $isValueQuoted);
                    $buffer = '';
                    $isValueQuoted = false;
                } else {
                    // Ignore spaces after the quote ends and before the comma
                    if ($isValueQuoted && trim($char) === '') {
                        continue;
                    }
                    $buffer .= $char;
                }
            }
        }

        // Add the last remaining argument in the buffer
        if (trim($buffer) !== '' || $isValueQuoted) {
            $args[] = $this->finalizeArgument($buffer, $isValueQuoted);
        }

        return $args;
    }

    /**
     * Casts raw string values to proper PHP types.
     */
    protected function finalizeArgument(string $val, bool $isQuoted)
    {
        if ($isQuoted) {
            return $val;
        }

        $val = trim($val);
        if (strtolower($val) === 'true') {
            return true;
        }
        if (strtolower($val) === 'false') {
            return false;
        }
        if (strtolower($val) === 'null') {
            return null;
        }
        if (is_numeric($val)) {
            return strpos($val, '.') !== false ? (float) $val : (int) $val;
        }

        return $val;
    }

    /**
     * Resolves the function name to a callable (Custom -> Class -> Global).
     *
     * @throws Exception
     */
    protected function resolveCallback(string $name): callable
    {
        // 1. Check custom registered functions
        if (isset($this->funcs[$name])) {
            return $this->funcs[$name];
        }

        // 2. Check registered object methods
        foreach ($this->objects as $object) {
            if (method_exists($object, $name)) {
                return [$object, $name];
            }
        }

        // 3. Check registered class methods
        foreach ($this->classes as $class) {
            if (method_exists($class, $name)) {
                return [$class, $name];
            }
        }

        // 4. Check global PHP functions
        if (function_exists($name)) {
            return $name;
        }

        throw new Exception("Function or method '$name' not found.");
    }

    /**
     * Splits the chain by dot while ignoring dots inside quotes or parentheses.
     */
    protected function splitChain(string $str): array
    {
        $parts = [];
        $buffer = '';
        $stack = 0; // Parentheses stack
        $inQuote = false;
        $quoteChar = '';

        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $char = $str[$i];

            // 1. Ignore escaped quotes at the chain separator level
            if ($inQuote && $char === '\\' && $i + 1 < $len && ($str[$i + 1] === '"' || $str[$i + 1] === "'")) {
                $buffer .= $char.$str[$i + 1];
                $i++;
                continue;
            }

            // 2. Manage quotes using Lookahead technique
            if ($char === '"' || $char === "'") {
                if (!$inQuote) {
                    $inQuote = true;
                    $quoteChar = $char;
                    $buffer .= $char;
                    continue;
                } elseif ($char === $quoteChar) {
                    $isEnd = true;
                    for ($j = $i + 1; $j < $len; $j++) {
                        $nextChar = $str[$j];
                        if (trim($nextChar) === '') {
                            continue;
                        }

                        // At the function chain splitting level, the next character to terminate the string must be a comma, closing parenthesis, or dot
                        if ($nextChar === ',' || $nextChar === ')' || $nextChar === '.') {
                            break;
                        }
                        $isEnd = false;
                        break;
                    }

                    if ($isEnd) {
                        $inQuote = false;
                        $quoteChar = '';
                    }
                    $buffer .= $char;
                    continue;
                }
            }

            // 3. Manage nested parentheses
            if (!$inQuote) {
                if ($char === '(') {
                    $stack++;
                }
                if ($char === ')') {
                    $stack--;
                }
            }

            // 4. Split by dot (only at the root level and outside quotes)
            if ($char === '.' && $stack === 0 && !$inQuote) {
                $parts[] = trim($buffer);
                $buffer = '';
            } else {
                $buffer .= $char;
            }
        }

        if ($buffer !== '') {
            $parts[] = trim($buffer);
        }

        return $parts;
    }
}
