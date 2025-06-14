<?php

declare(strict_types=1);

namespace App\Tool\Domain\Message;

use App\General\Domain\Message\Interfaces\MessageHighInterface;

/**
 *
 * @package App\Tool
 */
class ContactMessage implements MessageHighInterface
{
    public function __construct(
        private readonly string $email,
        private readonly string $name,
        private readonly string $subject,
        private readonly string $message,
    ) {
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }
}
