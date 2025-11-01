<?php

declare(strict_types=1);

namespace App\General\Infrastructure\DataCollector;

use Doctrine\Bundle\MongoDBBundle\Logger\CommandLogger;
use MongoDB\BSON\Document;
use MongoDB\BSON\Type;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\DataCollector\LateDataCollectorInterface;

use function array_filter;
use function array_map;
use function array_values;
use function class_exists;
use function count;
use function function_exists;
use function json_decode;
use function method_exists;

use const JSON_THROW_ON_ERROR;

/**
 * Doctrine ODM command data collector with fallback support for older MongoDB drivers.
 */
class MongoCommandDataCollector extends DataCollector implements LateDataCollectorInterface
{
    public function __construct(private readonly CommandLogger $commandLogger)
    {
        $this->reset();
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        // Intentionally left blank. Data is gathered in lateCollect().
    }

    public function lateCollect(): void
    {
        $commands = array_map(
            fn (array $command): array => $this->normaliseCommand($command),
            $this->commandLogger->getExecutedCommands(),
        );

        $failedCommands = array_values(array_filter(
            $commands,
            static fn (array $command): bool => $command['failure'] ?? false,
        ));

        $this->data = [
            'commands' => $commands,
            'commandCount' => count($commands),
            'failedCommands' => $failedCommands,
            'failedCommandCount' => count($failedCommands),
        ];
    }

    public function reset(): void
    {
        $this->data = [
            'commands' => [],
            'commandCount' => 0,
            'failedCommands' => [],
            'failedCommandCount' => 0,
        ];

        if (method_exists($this->commandLogger, 'clear')) {
            $this->commandLogger->clear();
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCommands(): array
    {
        return $this->data['commands'];
    }

    public function getCommandCount(): int
    {
        return $this->data['commandCount'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFailedCommands(): array
    {
        return $this->data['failedCommands'];
    }

    public function getFailedCommandCount(): int
    {
        return $this->data['failedCommandCount'];
    }

    public function getName(): string
    {
        return 'doctrine_mongodb';
    }

    /**
     * @param array<string, mixed> $command
     *
     * @return array<string, mixed>
     */
    private function normaliseCommand(array $command): array
    {
        if (isset($command['command'])) {
            $command['command'] = $this->normaliseValue($command['command']);
        }

        if (isset($command['reply'])) {
            $command['reply'] = $this->normaliseValue($command['reply']);
        }

        return $command;
    }

    private function normaliseValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->normaliseValue($item), $value);
        }

        $packedArrayClass = 'MongoDB\\BSON\\PackedArray';

        if ($value instanceof Document || (class_exists($packedArrayClass) && $value instanceof $packedArrayClass)) {
            return $this->normaliseDocument($value);
        }

        if ($value instanceof Type) {
            return $this->normaliseBsonType($value);
        }

        return $value;
    }

    private function normaliseDocument(object $document): mixed
    {
        if (method_exists($document, 'toCanonicalExtendedJSON')) {
            return $this->decodeJsonSafely($document->toCanonicalExtendedJSON());
        }

        if (method_exists($document, 'toJSON')) {
            return $this->decodeJsonSafely($document->toJSON());
        }

        if (function_exists('MongoDB\\BSON\\toJSON')) {
            try {
                return $this->decodeJsonSafely(\MongoDB\BSON\toJSON((string) $document));
            } catch (\Throwable) {
                // noop
            }
        }

        if (method_exists($document, '__toString')) {
            return (string) $document;
        }

        return $document;
    }

    private function normaliseBsonType(Type $type): mixed
    {
        if (method_exists($type, '__toString')) {
            return (string) $type;
        }

        return $type;
    }

    private function decodeJsonSafely(string $json): mixed
    {
        try {
            return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return $json;
        }
    }
}
