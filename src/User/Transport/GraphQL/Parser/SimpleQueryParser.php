<?php

declare(strict_types=1);

namespace App\User\Transport\GraphQL\Parser;

use InvalidArgumentException;

use function ctype_alnum;
use function ctype_alpha;
use function is_numeric;
use function preg_match;
use function strlen;
use function substr;
use function stripcslashes;
use function sprintf;

/**
 * Minimal GraphQL query parser dedicated to the profile endpoint use cases.
 */
class SimpleQueryParser
{
    private string $source = '';

    private int $length = 0;

    private int $position = 0;

    /**
     * @return array<string, array<string, mixed>>
     */
    public function parse(string $query): array
    {
        $this->source = $query;
        $this->length = strlen($query);
        $this->position = 0;

        $this->skipWhitespace();

        if ($this->matchKeyword('query') || $this->matchKeyword('mutation')) {
            $this->skipWhitespace();
            if ($this->peekName()) {
                $this->parseName();
                $this->skipWhitespace();
            }

            if ($this->currentChar() === '(') {
                $this->skipBlock('(', ')');
                $this->skipWhitespace();
            }
        }

        return $this->parseSelectionSet();
    }

    private function parseSelectionSet(): array
    {
        $this->expect('{');
        $fields = [];

        while (true) {
            $this->skipWhitespace();

            if ($this->currentChar() === '}') {
                $this->position++;
                break;
            }

            if ($this->position >= $this->length) {
                throw new InvalidArgumentException('Unexpected end of query while parsing selection set.');
            }

            $aliasOrName = $this->parseName();
            $this->skipWhitespace();

            $fieldName = $aliasOrName;
            if ($this->currentChar() === ':') {
                $this->position++;
                $this->skipWhitespace();
                $fieldName = $this->parseName();
                $this->skipWhitespace();
            }

            $arguments = [];
            if ($this->currentChar() === '(') {
                $arguments = $this->parseArguments();
                $this->skipWhitespace();
            }

            $selection = true;
            if ($this->currentChar() === '{') {
                $selection = $this->parseSelectionSet();
            }

            $fields[$aliasOrName] = [
                '_field' => $fieldName,
                '_arguments' => $arguments,
                '_selection' => $selection,
            ];

            $this->skipWhitespace();
        }

        return $fields;
    }

    private function parseArguments(): array
    {
        $this->expect('(');
        $arguments = [];

        while (true) {
            $this->skipWhitespace();

            if ($this->currentChar() === ')') {
                $this->position++;
                break;
            }

            $name = $this->parseName();
            $this->skipWhitespace();
            $this->expect(':');
            $this->skipWhitespace();

            $arguments[$name] = $this->parseValue();
            $this->skipWhitespace();
        }

        return $arguments;
    }

    private function parseValue(): mixed
    {
        $char = $this->currentChar();

        if ($char === '"') {
            return $this->parseString();
        }

        if ($char === '[') {
            return $this->parseList();
        }

        if ($char === '{') {
            return $this->parseInputObject();
        }

        if ($char === '$') {
            $this->position++;

            return ['__variable' => $this->parseName()];
        }

        if ($char === '-' || is_numeric($char)) {
            return $this->parseNumber();
        }

        $name = $this->parseName();

        return match ($name) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => $name,
        };
    }

    private function parseList(): array
    {
        $this->expect('[');
        $values = [];

        while (true) {
            $this->skipWhitespace();

            if ($this->currentChar() === ']') {
                $this->position++;
                break;
            }

            $values[] = $this->parseValue();
            $this->skipWhitespace();
        }

        return $values;
    }

    private function parseInputObject(): array
    {
        $this->expect('{');
        $object = [];

        while (true) {
            $this->skipWhitespace();

            if ($this->currentChar() === '}') {
                $this->position++;
                break;
            }

            $key = $this->parseName();
            $this->skipWhitespace();
            $this->expect(':');
            $this->skipWhitespace();
            $object[$key] = $this->parseValue();
            $this->skipWhitespace();
        }

        return $object;
    }

    private function parseNumber(): float|int
    {
        $start = $this->position;
        $char = $this->currentChar();
        if ($char === '-') {
            $this->position++;
        }

        while (is_numeric($this->currentChar())) {
            $this->position++;
        }

        if ($this->currentChar() === '.') {
            $this->position++;
            while (is_numeric($this->currentChar())) {
                $this->position++;
            }
        }

        $number = substr($this->source, $start, $this->position - $start);

        return preg_match('/\./', $number) === 1 ? (float)$number : (int)$number;
    }

    private function parseString(): string
    {
        $this->expect('"');
        $start = $this->position;

        while ($this->currentChar() !== '"') {
            if ($this->position >= $this->length) {
                throw new InvalidArgumentException('Unterminated string literal in GraphQL query.');
            }

            if ($this->currentChar() === '\\') {
                $this->position++;
            }

            $this->position++;
        }

        $value = substr($this->source, $start, $this->position - $start);
        $this->position++;

        return stripcslashes($value);
    }

    private function parseName(): string
    {
        $this->skipWhitespace();

        $char = $this->currentChar();
        if (!ctype_alpha($char) && $char !== '_') {
            throw new InvalidArgumentException(sprintf('Unexpected character "%s" in GraphQL query.', $char));
        }

        $start = $this->position;
        $this->position++;

        while ($this->position < $this->length) {
            $char = $this->currentChar();
            if (!ctype_alnum($char) && $char !== '_') {
                break;
            }

            $this->position++;
        }

        return substr($this->source, $start, $this->position - $start);
    }

    private function matchKeyword(string $keyword): bool
    {
        $length = strlen($keyword);
        if (substr($this->source, $this->position, $length) === $keyword) {
            $next = $this->position + $length;
            if ($next >= $this->length || !ctype_alnum($this->source[$next])) {
                $this->position += $length;

                return true;
            }
        }

        return false;
    }

    private function skipWhitespace(): void
    {
        while ($this->position < $this->length) {
            $char = $this->source[$this->position];
            if ($char <= ' ') {
                $this->position++;
                continue;
            }

            if ($char === '#') {
                $this->skipLineComment();
                continue;
            }

        if ($char === '/' && isset($this->source[$this->position + 1]) && $this->source[$this->position + 1] === '/') {
                $this->skipLineComment();
                continue;
            }

            if ($char === ',') {
                $this->position++;
                continue;
            }

            break;
        }
    }

    private function skipLineComment(): void
    {
        while ($this->position < $this->length && $this->source[$this->position] !== "\n") {
            $this->position++;
        }
    }

    private function expect(string $expected): void
    {
        if ($this->currentChar() !== $expected) {
            throw new InvalidArgumentException(sprintf('Expected "%s" in GraphQL query.', $expected));
        }

        $this->position++;
    }

    private function skipBlock(string $open, string $close): void
    {
        $this->expect($open);
        $depth = 1;

        while ($depth > 0) {
            if ($this->position >= $this->length) {
                throw new InvalidArgumentException('Unexpected end of GraphQL query.');
            }

            $char = $this->currentChar();
            if ($char === $open) {
                $depth++;
            } elseif ($char === $close) {
                $depth--;
            }

            $this->position++;
        }
    }

    private function currentChar(): string
    {
        if ($this->position >= $this->length) {
            return "\0";
        }

        return $this->source[$this->position];
    }

    private function peekName(): bool
    {
        $this->skipWhitespace();

        $char = $this->currentChar();

        return $char === '_' || ctype_alpha($char);
    }
}
