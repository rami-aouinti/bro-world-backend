<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Auth;

use App\General\Domain\Enum\Language;
use App\General\Domain\Enum\Locale;
use App\General\Domain\Utils\JSON;
use App\User\Application\Resource\UserResource;
use App\User\Domain\Entity\User;
use App\User\Domain\Message\UserCreatedMessage;
use App\User\Domain\Repository\Interfaces\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\NonUniqueResultException;
use JsonException;
use OpenApi\Attributes as OA;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

/**
 * @package App\User
 */
#[AsController]
#[OA\Tag(name: 'Profile')]
readonly class UserFacebookExistController
{
    public function __construct(
        private SerializerInterface $serializer,
        private UserResource $userResource,
        private UserRepositoryInterface $userRepository,
        private MessageBusInterface $bus,
        private EntityManagerInterface $entityManager
    )
    {
    }


    /**
     * Get current user profile data, accessible only for 'IS_AUTHENTICATED_FULLY' users.
     *
     * @param string  $email
     * @param string  $facebook
     * @param Request $request
     *
     * @throws JsonException
     * @throws NonUniqueResultException
     * @throws NotSupported
     * @throws Throwable
     * @throws ExceptionInterface
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     * @return JsonResponse
     */
    #[Route(
        path: '/v1/user/{email}/{facebook}/facebook/verify',
        methods: [Request::METHOD_GET],
    )]
    public function __invoke(string $email, string $facebook, Request $request): JsonResponse
    {
        $user = $this->userRepository->findOneBy([
            'email' => $email,
            'googleId' => $facebook
        ]);

        if(!$user) {
            $user = $this->userRepository->findOneBy([
                'email' => $email
            ]);
        }

        if(!$user) {
            $acceptLanguage = $request->headers->get('Accept-Language', 'en');
            $entity = $this->generateUser($email, $facebook . $this->userRepository->generateUsername($email), $acceptLanguage);
            $entity->setFacebookId($facebook);
            $user = $this->userResource->save($entity, true, true);

            $this->bus->dispatch(new UserCreatedMessage(
                $user->getId(),
                $request->request->all(),
                $request->headers->get('Accept-Language', 'en')));
        } else {
            $user->setFacebookId($facebook);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        /** @var array<string, string|array<string, string>> $output */
        $output = JSON::decode(
            $this->serializer->serialize(
                $user,
                'json',
                [
                    'groups' => User::SET_USER_PROFILE,
                ]
            ),
            true,
        );

        return new JsonResponse($output);
    }

    /**
     * @throws NonUniqueResultException
     */
    private function generateUser($email, $password, $acceptLanguage): User
    {
        $parsedLocale = \Locale::acceptFromHttp($acceptLanguage);
        $language = Language::tryFrom(substr($parsedLocale, 0, 2)) ?? Language::EN;
        $locale = Locale::tryFrom($parsedLocale) ?? Locale::EN;
        $names = $this->generateNamesFromEmail($email);
        $verificationToken = Uuid::uuid1();

        return (new User())
            ->setUsername($this->userRepository->generateUsername($email))
            ->setFirstName($names['firstname'])
            ->setLastName($names['lastname'])
            ->setEmail($email)
            ->setLanguage($language)
            ->setLocale($locale)
            ->setPlainPassword($password)
            ->setVerificationToken($verificationToken->toString());
    }

    private function generateNamesFromEmail(string $email): array
    {
        $base = strstr($email, '@', true);

        $base = preg_replace('/[^a-zA-Z0-9]+/', ' ', $base);

        $parts = explode(' ', trim($base));

        $firstname = ucfirst(strtolower($parts[0] ?? 'User'));
        $lastname = ucfirst(strtolower($parts[1] ?? 'Unknown'));

        return [
            'firstname' => $firstname,
            'lastname' => $lastname,
        ];
    }
}
