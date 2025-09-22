<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\General\Domain\Enum\Language;
use App\General\Domain\Enum\Locale;
use App\General\Infrastructure\Service\ApiProxyService;
use App\General\Infrastructure\Service\MercureService;
use App\User\Application\Resource\UserResource;
use App\User\Application\Service\Interfaces\UserCacheServiceInterface;
use App\User\Domain\Entity\Story;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\StoryUploadException;
use App\User\Domain\Message\UserCreatedMessage;
use App\User\Domain\Repository\Interfaces\UserRepositoryInterface;
use App\User\Infrastructure\Repository\StoryRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Throwable;

use function sprintf;

/**
 * @package App\User\User\Application\Service
 * @author Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
readonly class UserService
{
    private const string PATH = 'media';
    private const string CREATE_MEDIA_PATH = 'v1/platform/media';

    public function __construct(
        private UserResource $userResource,
        private UserRepositoryInterface $userRepository,
        private StoryRepository $storyRepository,
        private UserCacheServiceInterface $userCacheService,
        private SluggerInterface $slugger,
        private MessageBusInterface $bus,
        private ApiProxyService $proxyService,
        private string $storiesDirectory,
        private string $avatarDirectory
    ) {
    }

    /**
     *
     * @throws NonUniqueResultException
     * @throws Throwable
     */
    public function createUser(Request $request): string
    {
        if ($this->validateExistEmail($request) && $this->verifyPassword($request)) {
            $user = new User();
            //$this->generateUserIndex($user->getId(), $request->request->all(), $request->headers->get('Accept-Language', 'en'));
            $this->bus->dispatch(
                new UserCreatedMessage(
                    $user->getId(),
                    $request->request->all(),
                    $request->headers->get('Accept-Language', 'en')
                )
            );
        } else {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Failed to register');
        }

        return $user->getId();
    }

    /**
     * @param string $userId
     * @param array  $userData
     * @param string $language
     *
     * @throws NonUniqueResultException
     * @throws Throwable
     * @return User
     */
    public function generateUserIndex(string $userId, array $userData, string $language): User
    {
        $email = $userData['email'];
        $password = $userData['password'];
        $entity = $this->generateUser($userId, $email, $password, $language);

        return $this->userResource->save($entity, true, true);
    }

    /**
     * @param string $query
     *
     * @return array
     */
    public function searchUsers(string $query): array
    {
        return $this->userCacheService->search($query);
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

    /**
     * @throws NonUniqueResultException
     */
    private function generateUser(string $userId, $email, $password, $acceptLanguage): User
    {
        $parsedLocale = \Locale::acceptFromHttp($acceptLanguage);
        $language = Language::tryFrom(substr($parsedLocale, 0, 2)) ?? Language::EN;
        $locale = Locale::tryFrom($parsedLocale) ?? Locale::EN;
        $names = $this->generateNamesFromEmail($email);
        $verificationToken = Uuid::uuid1();
        $user = new User();
        $user->setId(Uuid::fromString($userId));
        $user->setUsername($this->userRepository->generateUsername($email));
        $user->setFirstName($names['firstname']);
        $user->setLastName($names['lastname']);
        $user->setEmail($email);
        $user->setLanguage($language);
        $user->setLocale($locale);
        $user->setPlainPassword($password);
        $user->setVerificationToken($verificationToken->toString());
        return $user;
    }

    private function verifyPassword(Request $request): bool
    {
        $password = $request->request->get('password');
        $repeatPassword = $request->request->get('repeatPassword');
        if ($password !== $repeatPassword) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Password and repeat password do not match');
        }

        return true;
    }

    /**
     * @throws NonUniqueResultException
     */
    private function validateExistEmail(Request $request): bool
    {
        $email = $request->request->get('email');
        $available = $this->userRepository->isEmailAvailable($email);

        if (!$available) {
            $message = sprintf(
                'Email "%s" is already registered',
                $email
            );

            throw new HttpException(Response::HTTP_BAD_REQUEST, $message);
        }

        return true;
    }

    /**
     * @param User    $user
     * @param Request $request
     *
     * @throws Throwable
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws StoryUploadException
     * @return Story
     */
    public function uploadStory(User $user, Request $request): Story
    {
        $file = $request->files->get('file');

        if (!$file instanceof UploadedFile) {
            throw StoryUploadException::missingFile();
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid('', true).'.'.$file->guessExtension();

        try {
            $file->move(
                $this->storiesDirectory,
                $newFilename
            );
        } catch (FileException $e) {
            throw StoryUploadException::moveFailed($e);
        }
        $baseUrl = $request->getSchemeAndHttpHost();
        $relativePath = '/uploads/stories/' . $newFilename;
        $mediaPath = $baseUrl . $relativePath;

        $story = new Story($user, $mediaPath);

        $this->storyRepository->save($story);
        return $story;
    }

    /**
     * @param User    $user
     * @param Request $request
     *
     * @return string|JsonResponse
     */
    public function uploadPhoto(User $user, Request $request): string|JsonResponse
    {
        $file = $request->files->get('file');

        if (!$file) {
            return new JsonResponse(['error' => 'No file uploaded.'], 400);
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid('', true).'.'.$file->guessExtension();

        try {
            $file->move(
                $this->avatarDirectory,
                $newFilename
            );
        } catch (FileException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
        $baseUrl = $request->getSchemeAndHttpHost();
        $relativePath = '/uploads/avatar/' . $newFilename;

        return $baseUrl . $relativePath;
    }

    /**
     * @param Request $request
     * @param string  $context
     *
     * @throws Throwable
     * @return array
     */
    public function createMedia(Request $request, string $context): array
    {
        $mediasArray = [];
        $medias = $this->proxyService->requestFile(
            Request::METHOD_POST,
            self::PATH,
            $request,
            [
                'context' => $context
            ],
            self::CREATE_MEDIA_PATH
        );

        foreach ($medias as $media) {
            if($media) {
                $mediasArray[] = $media['id'];
            }
        }

        return $mediasArray;
    }
}
