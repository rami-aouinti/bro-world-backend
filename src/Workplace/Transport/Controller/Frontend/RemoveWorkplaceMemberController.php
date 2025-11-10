<?php

declare(strict_types=1);

namespace App\Workplace\Transport\Controller\Frontend;

use App\General\Transport\Rest\ResponseHandler;
use App\User\Application\Resource\UserResource;
use App\User\Domain\Entity\User;
use App\Workplace\Application\Resource\WorkplaceResource;
use App\Workplace\Domain\Entity\Workplace;
use App\Workplace\Transport\Controller\Frontend\Traits\WorkplaceOwnershipTrait;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

use function array_filter;
use function array_map;

#[AsController]
#[OA\Tag(name: 'Workplace Frontend')]
readonly class RemoveWorkplaceMemberController
{
    use WorkplaceOwnershipTrait;

    public function __construct(
        private ResponseHandler $responseHandler,
        private WorkplaceResource $workplaceResource,
        private UserResource $userResource,
    ) {
    }

    /**
     * @throws Throwable
     */
    #[Route(
        path: '/v1/frontend/workplaces/{workplace}/members',
        methods: [Request::METHOD_DELETE],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(User $loggedInUser, Workplace $workplace, Request $request): Response
    {
        $this->assertOwnership($workplace, $loggedInUser);

        $members = $this->resolveMembers($request->request->all('members'));

        foreach ($members as $member) {
            $workplace->removeMember($member);
        }

        $workplace = $this->workplaceResource->save($workplace, true);

        return $this->responseHandler->createResponse(
            $request,
            $workplace,
            $this->workplaceResource,
        );
    }

    /**
     * @param array<int, mixed>|string|null $members
     *
     * @return array<int, User>
     */
    private function resolveMembers(array|string|null $members): array
    {
        $identifiers = $this->normalizeIdentifiers($members);

        return array_map(
            fn (string $memberId): User => $this->userResource->findOne($memberId, throwExceptionIfNotFound: true),
            $identifiers,
        );
    }

    /**
     * @param array<int, mixed>|string|null $identifiers
     *
     * @return array<int, string>
     */
    private function normalizeIdentifiers(array|string|null $identifiers): array
    {
        if ($identifiers === null) {
            return [];
        }

        $ids = is_array($identifiers) ? $identifiers : [$identifiers];

        return array_filter(
            array_map(static fn ($value): ?string => $value !== '' ? (string)$value : null, $ids),
        );
    }
}
