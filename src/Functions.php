<?php

namespace Nabeghe\Risma;

class Functions
{
    /**
     * @example {variable.and('anything')}
     */
    public static function and($value, $default)
    {
        $value = (string) $value;
        return $value != '' ? $value.$default : $value;
    }

    public static function append($value, ...$suffix)
    {
        return $value.implode('', array_map('strval', $suffix));
    }

    public static function exists($value)
    {
        return ($value !== null && $value !== '') ? '1' : '0';
    }

    public static function flatten_lines($value)
    {
        $value = (string) $value;
        $value = str_replace(["\r\n", "\n", "\r"], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        return trim($value);
    }

    public static function if_empty($value, $yes = null, $no = '')
    {
        $value = (string) $value;
        return empty($value) ? (string) $yes : sprintf(strval($no ?? $value), $value);
    }

    public static function if_equals($value, $target, $yes = null, $no = '')
    {
        $value = (string) $value;
        $target = (string) $target;
        return (string) ($value == $target ? sprintf(strval($yes ?? $value), $value) : $no);
    }

    public static function if_blank($value, $yes = null, $no = ''): string
    {
        $value = (string) $value;
        return strval(static::_ifBlank($value) ? sprintf(strval($yes ?? $value), $value) : $no);
    }

    public static function if_not_blank($value, $yes = null, $no = ''): string
    {
        $value = (string) $value;
        return strval(!static::_ifBlank($value) ? sprintf(strval($yes ?? $value), $value) : $no);
    }

    public static function if_not_empty($value, $yes = null, $no = ''): string
    {
        $value = (string) $value;
        return !empty($value) ? sprintf(strval($yes ?? $value), $value) : $no;
    }

    public static function if_numeric($value, $yes = null, $no = ''): string
    {
        return (string) (is_numeric($value) ? sprintf(strval($yes ?? $value), $value) : $no);
    }

    public static function line(): string
    {
        return "\n";
    }

    public static function maybe_plural_s($value): string
    {
        $value = (int) $value;
        return $value > 1 ? 's' : '';
    }

    public static function ok($value)
    {
        return $value ? '1' : '0';
    }

    /**
     * @example {variable.or('anything else')}
     */
    public static function or($value, $default): string
    {
        $value = (string) $value;
        return $value == '' ? (string) $default : $value;
    }

    public static function prepend($value, ...$prefix): string
    {
        return implode('', array_map('strval', $prefix)).$value;
    }

    public static function remove_lines($value): string
    {
        return str_replace(["\r\n", "\n", "\r"], '', (string) $value);
    }

    private static function _ifBlank(?string $value): bool
    {
        return $value == '' || preg_match(
                '/^[\x{206A}\x{200E}\x{200F}\x{200D}\x{200C}\x{200B}\x{202A}\x{202B}\x{202D}\x{202E}\x{202C}\x{2066}\x{2067}\x{2068}\x{2069}\s\x00]*$/u',
                $value
            ) === 1;
    }
}
