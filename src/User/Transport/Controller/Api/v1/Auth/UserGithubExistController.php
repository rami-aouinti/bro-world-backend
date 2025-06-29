<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Auth;

use App\General\Domain\Enum\Language;
use App\General\Domain\Enum\Locale;
use App\User\Application\ApiProxy\UserProxy;
use App\User\Application\Resource\UserResource;
use App\User\Application\Security\SecurityUser;
use App\User\Domain\Entity\Socials\GithubUser;
use App\User\Domain\Entity\Socials\GoogleUser;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserProfile;
use App\User\Domain\Repository\Interfaces\UserRepositoryInterface;
use App\User\Infrastructure\Repository\GithubRepository;
use App\User\Infrastructure\Repository\UserGroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * @package App\User
 */
#[AsController]
#[OA\Tag(name: 'Profile')]
readonly class UserGithubExistController
{
    public function __construct(
        private UserResource $userResource,
        private UserRepositoryInterface $userRepository,
        private GithubRepository $githubRepository,
        private UserProxy $userProxy,
        private EntityManagerInterface $entityManager,
        private UserGroupRepository $groupRepository,
        private UserPasswordHasherInterface $userPasswordHasher,

    )
    {
    }


    /**
     * Get current user profile data, accessible only for 'IS_AUTHENTICATED_FULLY' users.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    #[Route(
        path: '/v1/user/github/verify',
        methods: [Request::METHOD_POST],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $userRequest = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            if (!isset($userRequest['id'], $userRequest['email'])) {
                return new JsonResponse(['error' => 'Invalid request data'], 400);
            }

            $githubId = (string)$userRequest['id'];

            $user = $this->githubRepository->findOneBy([
                'githubId' => $githubId
            ]);

            if ($user) {
                $user->setPlainPassword($githubId . $userRequest['email']);
                $user = $this->userResource->save($user, true, true);
            } else {
                $user = $this->userRepository->findOneBy([
                    'email' => $userRequest['email']
                ]);

                if ($user) {
                    $githubUserRepository = $this->entityManager->getRepository(GithubUser::class);
                    $githubRepo = $githubUserRepository->findOneBy(['email' => $user->getEmail()]);

                    if ($githubRepo) {
                        $githubRepo->setPlainPassword($githubId . $userRequest['email']);
                        $githubRepo->setGithubId($githubId);
                        if(isset($userRequest['html_url'])) {
                            $githubRepo->setHtmlUrl($userRequest['html_url']);
                        }
                        if(isset($userRequest['avatar_url'])) {
                            $githubRepo->setAvatarUrl($userRequest['avatar_url']);
                        }
                        $this->githubRepository->save($githubRepo);
                    } else {
                        $googleUserRepository = $this->entityManager->getRepository(GoogleUser::class);
                        $googleRepo = $googleUserRepository->findOneBy(['email' => $user->getEmail()]);
                        if($googleRepo) {
                            $githubId = $googleRepo->getGoogleId();
                        }
                    }
                } else {
                    $githubUser = new GithubUser();
                    $githubUser->setGithubId($githubId);
                    if(isset($userRequest['html_url'])) {
                        $githubUser->setHtmlUrl($userRequest['html_url']);
                    }
                    if(isset($userRequest['avatar_url'])) {
                        $githubUser->setAvatarUrl($userRequest['avatar_url']);
                    }

                    $acceptLanguage = $request->headers->get('Accept-Language', 'en');
                    $entity = $this->generateGithubUser(
                        $userRequest['email'],
                        $githubId . $userRequest['email'],
                        $acceptLanguage,
                        $githubUser
                    );

                    $group = $this->groupRepository->findOneBy(['role' => 'ROLE_USER']);
                    if ($group) {
                        $entity->addUserGroup($group);
                    }

                    $user = $this->userResource->save($entity, true, true);
                    $profile = new UserProfile($user);
                    $profile->setPhoto($userRequest['avatar_url']);
                    $this->entityManager->persist($profile);
                    $this->entityManager->flush();
                }
            }

            $token = $this->userProxy->login($user->getUsername(), $githubId . $userRequest['email']);
            $result['token'] = $token['token'];
            $result['profile'] = $this->userProxy->profile($token['token']);

            return new JsonResponse($result);
        } catch (Throwable $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * @throws NonUniqueResultException
     */
    private function generateGithubUser($email, $password, $acceptLanguage, $user): User
    {
        $parsedLocale = \Locale::acceptFromHttp($acceptLanguage);
        $language = Language::tryFrom(substr($parsedLocale, 0, 2)) ?? Language::EN;
        $locale = Locale::tryFrom($parsedLocale) ?? Locale::EN;
        $names = $this->generateNamesFromEmail($email);
        $callback = fn (string $plainPassword): string => $this->userPasswordHasher
            ->hashPassword(new SecurityUser($user, []), $plainPassword);
        // Set new password and encode it with user encoder
        $user->setPassword($callback, $password);
        $user
            ->setUsername($this->userRepository->generateUsername($email))
            ->setFirstName($names['firstname'])
            ->setLastName($names['lastname'])
            ->setEmail($email)
            ->setLanguage($language)
            ->setLocale($locale)
            ->setPlainPassword($password)
            ->setVerificationToken(null)
            ->setEnabled(true);
        return $user;
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
