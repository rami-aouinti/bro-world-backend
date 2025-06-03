<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Profile;

use App\General\Domain\Utils\JSON;
use App\Role\Application\Security\Interfaces\RolesServiceInterface;
use App\User\Application\Resource\UserResource;
use App\User\Application\Service\UserService;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserProfile;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

/**
 * @package App\User
 */
#[AsController]
#[OA\Tag(name: 'Profile')]
readonly class UpdateController
{
    public function __construct(
        private SerializerInterface $serializer,
        private RolesServiceInterface $rolesService,
        private EntityManagerInterface $entityManager,
        private UserService $userService,
        private UserResource $userResource
    ) {
    }

    /**
     * Get current user profile data, accessible only for 'IS_AUTHENTICATED_FULLY' users.
     *
     * @param User    $loggedInUser
     * @param Request $request
     *
     * @throws JsonException
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws ExceptionInterface
     * @throws Throwable
     * @return JsonResponse
     */
    #[Route(
        path: '/v1/profile/update',
        methods: [Request::METHOD_POST],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(User $loggedInUser, Request $request): JsonResponse
    {
        $user = $request->request->all();
        $file = $request->files->get('file');
        $profile = $loggedInUser->getProfile();

        if(!$profile) {
            $profile = new UserProfile($loggedInUser);
        }

        if(isset($file)) {
            $mediaPath = $this->userService->uploadPhoto($file, $request);
            $profile->setPhoto($mediaPath);
        }

        if(isset($user['firstName'])) {
            $loggedInUser->setFirstName($user['firstName']);
        }
        if(isset($user['lastName'])) {
            $loggedInUser->setLastName($user['lastName']);
        }

        if(isset($user['title'])) {
            $profile->setTitle($user['title']);
        }

        if(isset($user['description'])) {
            $profile->setDescription($user['description']);
        }

        if(isset($user['gender'])) {
            $profile->setGender($user['gender']);
        }
        if(isset($user['phone'])) {
            $profile->setPhone($user['phone']);
        }

        if(isset($user['address'])) {
            $profile->setAddress($user['address']);
        }

        if(isset($user['birthday'])) {
            $profile->setBirthday($user['birthday']);
        }

        $this->entityManager->persist($profile);
        $this->entityManager->flush();

        $this->userResource->save($loggedInUser);

        /** @var array<string, string|array<string, string>> $output */
        $output = JSON::decode(
            $this->serializer->serialize(
                $loggedInUser,
                'json',
                [
                    'groups' => User::SET_USER_PROFILE,
                ]
            ),
            true,
        );
        /** @var array<int, string> $roles */
        $roles = $output['roles'];
        $output['roles'] = $this->rolesService->getInheritedRoles($roles);

        return new JsonResponse($output);
    }
}
