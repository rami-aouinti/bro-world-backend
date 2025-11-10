<?php

declare(strict_types=1);

namespace App\Workplace\Transport\Controller\Frontend;

use App\General\Transport\Rest\ResponseHandler;
use App\User\Application\Resource\PluginResource;
use App\User\Domain\Entity\Plugin;
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
readonly class RemoveWorkplacePluginController
{
    use WorkplaceOwnershipTrait;

    public function __construct(
        private ResponseHandler $responseHandler,
        private WorkplaceResource $workplaceResource,
        private PluginResource $pluginResource,
    ) {
    }

    /**
     * @throws Throwable
     */
    #[Route(
        path: '/v1/frontend/workplaces/{workplace}/plugins',
        methods: [Request::METHOD_DELETE],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(User $loggedInUser, Workplace $workplace, Request $request): Response
    {
        $this->assertOwnership($workplace, $loggedInUser);

        $plugins = $this->resolvePlugins($request->request->all('plugins'));

        foreach ($plugins as $plugin) {
            $workplace->removePlugin($plugin);
        }

        $workplace = $this->workplaceResource->save($workplace, true);

        return $this->responseHandler->createResponse(
            $request,
            $workplace,
            $this->workplaceResource,
        );
    }

    /**
     * @param array<int, mixed>|string|null $plugins
     *
     * @return array<int, Plugin>
     */
    private function resolvePlugins(array|string|null $plugins): array
    {
        $identifiers = $this->normalizeIdentifiers($plugins);

        return array_map(
            fn (string $pluginId): Plugin => $this->pluginResource->findOne($pluginId, throwExceptionIfNotFound: true),
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
