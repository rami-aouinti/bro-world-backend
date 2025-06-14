<?php

declare(strict_types=1);

namespace App\Tool\Application\Service;

use App\Tool\Application\Service\Interfaces\ContactServiceInterface;
use App\Tool\Domain\Entity\Contact;
use App\Tool\Domain\Message\ContactMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @package App\Tool
 */
readonly class ContactService implements ContactServiceInterface
{
    /**
     * @param EntityManagerInterface $entityManager
     * @param MessageBusInterface    $bus
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $bus,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function send(string $name, string $email, string $subject, string $message): void
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
        $contact->setName($name);
        $contact->setEmail($email);
        $contact->setSubject($subject);
        $contact->setMessage($message);
        $this->entityManager->persist($contact);
        $this->entityManager->flush();
    }
}
