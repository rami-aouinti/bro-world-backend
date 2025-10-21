<?php

declare(strict_types=1);

namespace {
    if (extension_loaded('mongodb')) {
        return;
    }
}

namespace MongoDB\Driver\Monitoring {
    if (!interface_exists(CommandSubscriber::class)) {
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

    if (!class_exists(CommandStartedEvent::class)) {
        class CommandStartedEvent extends AbstractEventStub
        {
        }
    }

    if (!class_exists(CommandSucceededEvent::class)) {
        class CommandSucceededEvent extends AbstractEventStub
        {
        }
    }

    if (!class_exists(CommandFailedEvent::class)) {
        class CommandFailedEvent extends AbstractEventStub
        {
        }
    }
}
