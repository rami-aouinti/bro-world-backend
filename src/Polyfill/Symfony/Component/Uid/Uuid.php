<?php

declare(strict_types=1);

namespace Symfony\Component\Uid;

use Ramsey\Uuid\Uuid as RamseyUuid;
use Ramsey\Uuid\UuidInterface;

if (!class_exists(\Symfony\Component\Uid\Uuid::class)) {
    final class Uuid
    {
        private UuidInterface $uuid;

        private function __construct(UuidInterface $uuid)
        {
            $this->uuid = $uuid;
        }

        public static function isValid(string $value): bool
        {
            if ($value === '') {
                return false;
            }

            if (str_contains($value, '-')) {
                return RamseyUuid::isValid($value);
            }

            if (strlen($value) === 32 && ctype_xdigit($value)) {
                return RamseyUuid::isValid(self::formatHex($value));
            }

            return RamseyUuid::isValid($value);
        }

        public static function fromString(string $value): self
        {
            if (str_contains($value, '-')) {
                return new self(RamseyUuid::fromString($value));
            }

            if (strlen($value) === 32 && ctype_xdigit($value)) {
                return self::fromHex($value);
            }

            return new self(RamseyUuid::fromString($value));
        }

        public static function fromRfc4122(string $value): self
        {
            return self::fromString($value);
        }

        public static function fromBinary(string $value): self
        {
            return new self(RamseyUuid::fromBytes($value));
        }

        public static function fromHex(string $value): self
        {
            if ($value === '' || !ctype_xdigit($value) || strlen($value) % 2 !== 0) {
                throw new \InvalidArgumentException(sprintf('Invalid hexadecimal UUID string "%s".', $value));
            }

            $binary = hex2bin($value);

            if ($binary === false) {
                throw new \InvalidArgumentException(sprintf('Unable to convert hexadecimal UUID string "%s" to binary.', $value));
            }

            return new self(RamseyUuid::fromBytes($binary));
        }

        public static function v4(): self
        {
            return new self(RamseyUuid::uuid4());
        }

        public function equals(self $other): bool
        {
            return $this->uuid->equals($other->uuid);
        }

        public function toBinary(): string
        {
            return $this->uuid->getBytes();
        }

        public function toHex(): string
        {
            return bin2hex($this->uuid->getBytes());
        }

        public function toRfc4122(): string
        {
            return $this->uuid->toString();
        }

        public function __toString(): string
        {
            return $this->uuid->toString();
        }

        private static function formatHex(string $value): string
        {
            return sprintf(
                '%s-%s-%s-%s-%s',
                substr($value, 0, 8),
                substr($value, 8, 4),
                substr($value, 12, 4),
                substr($value, 16, 4),
                substr($value, 20)
            );
        }
    }
}
