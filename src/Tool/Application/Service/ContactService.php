<?php

declare(strict_types=1);

namespace App\Tool\Application\Service;

use App\Tool\Application\Service\Interfaces\ContactServiceInterface;
use App\Tool\Domain\Entity\Contact;
use App\Tool\Domain\Message\ContactMessage;
use App\Tool\Domain\Repository\Interfaces\ContactRepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @package App\Tool
 */
readonly class ContactService implements ContactServiceInterface
{
    /**
     * @param \App\Tool\Infrastructure\Repository\ContactRepository $repository
     */
    public function __construct(
        private ContactRepositoryInterface $repository,
        private MessageBusInterface $bus,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function send(string $name, string $email, string $subject, string $message): ?Contact
    {
        $this->bus->dispatch(
            new ContactMessage(
                $name,
                $email,
                $subject,
                $message
            )
        );
        $contact = new Contact();
        $contact->setName($email);
        $contact->setEmail($email);
        $contact->setSubject($subject);
        $contact->setMessage($message);
        $this->repository->save($contact);

        return $contact;
    }
}
