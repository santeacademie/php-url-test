<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\Yaml;

use Symfony\Component\Yaml\Parser as SymfonyYamlParser;

class Parser extends SymfonyYamlParser
{
    /** @var array<string, callable> */
    private static array $functions = [];

    public static function registerFileFunction(?string $path = null): void
    {
        self::$functions['file'] = static function (string $fileName) use ($path): string {
            $basePath = $path === null ? __DIR__ : rtrim($path, DIRECTORY_SEPARATOR);
            $filePath = str_starts_with($fileName, DIRECTORY_SEPARATOR)
                ? $fileName
                : $basePath . DIRECTORY_SEPARATOR . $fileName;

            if (!is_readable($filePath)) {
                throw new \RuntimeException(sprintf('File "%s" not found.', $filePath));
            }

            return (string) file_get_contents($filePath);
        };
    }

    public function parse(string $value, int $flags = 0): mixed
    {
        $parsed = parent::parse($value, $flags);

        if (is_array($parsed)) {
            $this->parseValues($parsed);
        }

        return $parsed;
    }

    private function parseValues(array &$values): void
    {
        foreach ($values as &$value) {
            if (is_array($value)) {
                $this->parseValues($value);
            } elseif (
                is_string($value)
                && str_starts_with($value, '<')
                && str_ends_with($value, '>')
            ) {
                $value = $this->evaluateFunction($value);
            }
        }
    }

    private function evaluateFunction(string $expression): mixed
    {
        $functionId = null;
        $parameters = [];

        foreach (token_get_all('<?php ' . $expression) as $token) {
            if (!is_array($token)) {
                continue;
            }

            if ($token[0] === T_STRING && $functionId === null) {
                $functionId = $token[1];
            } elseif ($token[0] === T_STRING) {
                $parameters[] = $token[1];
            } elseif ($token[0] === T_CONSTANT_ENCAPSED_STRING) {
                $parameters[] = substr($token[1], 1, -1);
            } elseif ($token[0] === T_LNUMBER || $token[0] === T_DNUMBER) {
                $parameters[] = $token[1];
            }
        }

        if ($functionId === null) {
            throw new \RuntimeException(sprintf('Function name cannot be found in %s.', $expression));
        }

        if (!isset(self::$functions[$functionId])) {
            throw new \RuntimeException(sprintf('Function "%s" not found.', $functionId));
        }

        return (self::$functions[$functionId])(...$parameters);
    }
}
