<?php

declare(strict_types=1);

namespace App\User\Transport\AutoMapper\Event;

use App\General\Transport\AutoMapper\RestRequestMapper;
use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

use function filter_var;
use function is_bool;
use function is_string;
use function sprintf;

use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOLEAN;

/**
 * @package App\Event
 */
class RequestMapper extends RestRequestMapper
{
    /**
     * @var array<int, non-empty-string>
     */
    protected static array $properties = [
        'title',
        'description',
        'start',
        'end',
        'allDay',
        'color',
        'location',
        'isPrivate',
    ];

    protected function transformStart(mixed $start): ?DateTimeInterface
    {
        return $this->transformDateTime($start, 'start');
    }

    protected function transformEnd(mixed $end): ?DateTimeInterface
    {
        return $this->transformDateTime($end, 'end');
    }

    protected function transformAllDay(mixed $allDay): ?bool
    {
        return $this->transformBoolean($allDay, 'allDay');
    }

    protected function transformIsPrivate(mixed $isPrivate): ?bool
    {
        return $this->transformBoolean($isPrivate, 'isPrivate');
    }

    private function transformDateTime(mixed $value, string $field): ?DateTimeInterface
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        if (!is_string($value)) {
            throw new BadRequestHttpException(
                sprintf('Field "%s" must be a valid datetime string.', $field),
            );
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Throwable $exception) {
            throw new BadRequestHttpException(
                sprintf('Field "%s" must be a valid datetime string.', $field),
                $exception,
            );
        }
    }

    private function transformBoolean(mixed $value, string $field): ?bool
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        $booleanValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($booleanValue === null) {
            throw new BadRequestHttpException(
                sprintf('Field "%s" must be boolean.', $field),
            );
        }

        return $booleanValue;
    }
}
