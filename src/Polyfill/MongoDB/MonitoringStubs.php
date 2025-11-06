<?php

declare(strict_types=1);

namespace MongoDB\BSON {
    use ArrayObject;
    use InvalidArgumentException;
    use JsonException;
    use JsonSerializable;

    use function is_array;

    if (!class_exists(Document::class)) {
        /**
         * Lightweight polyfill for {@see Document} when the MongoDB extension
         * is not installed or an older version is used that does not ship the class.
         *
         * The real implementation offers a BSON backed object. For the purposes of the
         * application code that relies on this class (e.g. Doctrine's profiler data
         * collector), representing the document data as an {@see ArrayObject} is
         * sufficient.
         */
        final class Document extends ArrayObject implements JsonSerializable
        {
            /**
             * @param iterable|object $document
             *
             * @return Document
             */
            public static function fromPHP(iterable|object $document): self
            {
                return new self(self::normalize($document));
            }

            public static function fromJSON(string $json): self
            {
                try {
                    $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
                } catch (JsonException $exception) {
                    throw new InvalidArgumentException($exception->getMessage(), $exception->getCode(), $exception);
                }

                if (!is_array($decoded)) {
                    throw new InvalidArgumentException('JSON document must decode to an array.');
                }

                return new self($decoded);
            }

            public function toPHP(): array
            {
                return $this->getArrayCopy();
            }

            public function toJSON(): string
            {
                try {
                    return json_encode($this->getArrayCopy(), JSON_THROW_ON_ERROR);
                } catch (JsonException $exception) {
                    throw new InvalidArgumentException($exception->getMessage(), $exception->getCode(), $exception);
                }
            }

            public function jsonSerialize(): array
            {
                return $this->toPHP();
            }

            public function __toString(): string
            {
                try {
                    return $this->toJSON();
                } catch (InvalidArgumentException) {
                    return '';
                }
            }

            /**
             * @param iterable|object $document
             *
             * @return array
             */
            private static function normalize(iterable|object $document): array
            {
                if ($document instanceof self) {
                    return $document->getArrayCopy();
                }

                if ($document instanceof ArrayObject) {
                    return $document->getArrayCopy();
                }

                if (is_array($document)) {
                    return $document;
                }

                if (is_object($document)) {
                    /** @var array<string, mixed> $normalized */
                    $normalized = get_object_vars($document);

                    return $normalized;
                }

                $normalized = [];

                foreach ($document as $key => $value) {
                    $normalized[$key] = $value;
                }

                return $normalized;
            }
        }
    }
}

namespace {
    if (extension_loaded('mongodb')) {
        return;
    }
}

namespace MongoDB\Driver\Monitoring {
    if (!interface_exists(CommandSubscriber::class)) {
        /**
         * @internal
         */
        interface CommandSubscriber
        {
            public function commandStarted(CommandStartedEvent $event): void;

            public function commandSucceeded(CommandSucceededEvent $event): void;

            public function commandFailed(CommandFailedEvent $event): void;
        }
    }

    /**
     * @internal
     */
    if (!class_exists(AbstractEventStub::class)) {
        /**
         * @internal
         */
        abstract class AbstractEventStub
        {
            public function __construct(...$arguments)
            {
                // No-op constructor to mirror the real extension signatures.
            }

            public function __call(string $name, array $arguments): mixed
            {
                return null;
            }

            public function __get(string $name): mixed
            {
                return null;
            }
        }
    }

    if (!class_exists(CommandStartedEvent::class)) {
        /**
         * @internal
         */
        class CommandStartedEvent extends AbstractEventStub
        {
        }
    }

    if (!class_exists(CommandSucceededEvent::class)) {
        /**
         * @internal
         */
        class CommandSucceededEvent extends AbstractEventStub
        {
        }
    }

    if (!class_exists(CommandFailedEvent::class)) {
        /**
         * @internal
         */
        class CommandFailedEvent extends AbstractEventStub
        {
        }
    }
}
