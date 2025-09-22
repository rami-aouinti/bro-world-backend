<?php

declare(strict_types=1);

namespace App\Tests\Application\User\Transport\Controller\Api\v1\Event;

use App\General\Domain\Utils\JSON;
use App\Tests\TestCase\WebTestCase;
use App\User\Application\Resource\EventResource;
use App\User\Application\Resource\UserResource;
use App\User\Domain\Entity\Event;
use App\User\Domain\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * @package App\Tests
 */
class EventControllerTest extends WebTestCase
{
    private const string LOGGED_USER = 'john-logged';
    private const string LOGGED_USER_PASSWORD = 'password-logged';

    protected static string $baseUrl = self::API_URL_PREFIX . '/v1/event';

    private EventResource $eventResource;
    private UserResource $userResource;
    /**
     * @var array<int, string>
     */
    private array $createdEventIds = [];

    /**
     * @throws Throwable
     */
    protected function setUp(): void
    {
        parent::setUp();

        $container = static::getContainer();
        $this->eventResource = $container->get(EventResource::class);
        $this->userResource = $container->get(UserResource::class);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `PUT /api/v1/event/{id}` converts request payload through the AutoMapper.')]
    public function testThatUpdateActionUsesAutoMapper(): void
    {
        $event = $this->createEvent();
        $client = $this->getTestClient(self::LOGGED_USER, self::LOGGED_USER_PASSWORD);

        $start = new DateTimeImmutable('2025-03-15T08:30:00+00:00');
        $end = new DateTimeImmutable('2025-03-15T10:00:00+00:00');
        $payload = [
            'title' => 'Updated title',
            'description' => 'Updated description',
            'start' => $start->format(DateTimeImmutable::ATOM),
            'end' => $end->format(DateTimeImmutable::ATOM),
            'allDay' => true,
            'color' => '#112233',
            'location' => 'Conference room',
            'isPrivate' => false,
        ];

        $client->request(
            method: 'PUT',
            uri: static::$baseUrl . '/' . $event->getId(),
            content: JSON::encode($payload),
        );

        $response = $client->getResponse();
        $content = $response->getContent();
        self::assertNotFalse($content);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), "Response:\n" . $response);

        $responseData = JSON::decode($content, true);
        self::assertSame($payload['title'], $responseData['title']);
        self::assertSame($payload['description'], $responseData['description']);
        self::assertSame($payload['color'], $responseData['color']);
        self::assertSame($payload['location'], $responseData['location']);
        self::assertSame($payload['allDay'], $responseData['allDay']);
        self::assertSame($payload['isPrivate'], $responseData['isPrivate']);

        $updatedEvent = $this->eventResource->findOne($event->getId());
        self::assertInstanceOf(Event::class, $updatedEvent);
        self::assertSame($start->getTimestamp(), $updatedEvent->getStart()->getTimestamp());
        self::assertSame($end->getTimestamp(), $updatedEvent->getEnd()?->getTimestamp());
        self::assertSame($payload['allDay'], $updatedEvent->isAllDay());
        self::assertSame($payload['isPrivate'], $updatedEvent->isPrivate());
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `PATCH /api/v1/event/{id}` updates datetime fields using the AutoMapper.')]
    public function testThatPatchActionUpdatesDatesThroughAutoMapper(): void
    {
        $event = $this->createEvent();
        $client = $this->getTestClient(self::LOGGED_USER, self::LOGGED_USER_PASSWORD);

        $newStart = new DateTimeImmutable('2026-07-01T14:15:00+00:00');
        $payload = [
            'start' => $newStart->format(DateTimeImmutable::ATOM),
            'allDay' => false,
        ];

        $client->request(
            method: 'PATCH',
            uri: static::$baseUrl . '/' . $event->getId(),
            content: JSON::encode($payload),
        );

        $response = $client->getResponse();
        $content = $response->getContent();
        self::assertNotFalse($content);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), "Response:\n" . $response);

        $updatedEvent = $this->eventResource->findOne($event->getId());
        self::assertInstanceOf(Event::class, $updatedEvent);
        self::assertSame($newStart->getTimestamp(), $updatedEvent->getStart()->getTimestamp());
        self::assertFalse($updatedEvent->isAllDay());
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `PUT /api/v1/event/{id}` rejects invalid datetime payload via the AutoMapper.')]
    public function testThatInvalidDateTimePayloadReturnsBadRequest(): void
    {
        $event = $this->createEvent();
        $client = $this->getTestClient(self::LOGGED_USER, self::LOGGED_USER_PASSWORD);

        $payload = [
            'title' => 'Invalid payload',
            'start' => 'not-a-valid-date',
        ];

        $originalStartTimestamp = $event->getStart()->getTimestamp();

        $client->request(
            method: 'PUT',
            uri: static::$baseUrl . '/' . $event->getId(),
            content: JSON::encode($payload),
        );

        $response = $client->getResponse();
        $content = $response->getContent();
        self::assertNotFalse($content);
        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode(), "Response:\n" . $response);

        $responseData = JSON::decode($content, true);
        self::assertSame('Field "start" must be a valid datetime string.', $responseData['message']);

        $unchangedEvent = $this->eventResource->findOne($event->getId());
        self::assertInstanceOf(Event::class, $unchangedEvent);
        self::assertSame($originalStartTimestamp, $unchangedEvent->getStart()->getTimestamp());
    }

    /**
     * @throws Throwable
     */
    private function createEvent(): Event
    {
        $user = $this->userResource->findOneBy(['username' => self::LOGGED_USER]);
        self::assertInstanceOf(User::class, $user);

        $event = new Event(
            user: $user,
            title: 'Initial title',
            start: new DateTimeImmutable('2024-01-01T09:00:00+00:00'),
            end: new DateTimeImmutable('2024-01-01T10:00:00+00:00'),
            color: '#ffffff',
            description: 'Initial description',
            location: 'Initial location',
            allDay: false,
            isPrivate: true,
        );

        $this->eventResource->save($event);
        $this->createdEventIds[] = $event->getId();

        return $event;
    }

    protected function tearDown(): void
    {
        foreach ($this->createdEventIds as $eventId) {
            try {
                $this->eventResource->delete($eventId);
            } catch (Throwable) {
                // Ignore cleanup errors to avoid hiding test failures.
            }
        }

        $this->createdEventIds = [];

        parent::tearDown();
    }
}
