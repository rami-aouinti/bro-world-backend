<?php

declare(strict_types=1);

namespace App\User\Transport\GraphQL\Controller;

use App\General\Domain\Utils\JSON;
use App\User\Application\Service\UserProfileViewBuilder;
use App\User\Domain\Entity\User;
use App\User\Transport\GraphQL\Parser\SimpleQueryParser;
use InvalidArgumentException;
use JsonException;
use RuntimeException;
use Throwable;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

use function array_filter;
use function array_is_list;
use function array_key_exists;
use function array_map;
use function array_values;
use function is_array;
use function is_numeric;
use function is_string;
use function sprintf;

/**
 * @package App\User
 */
#[AsController]
#[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
class GraphQLController
{
    public function __construct(
        private readonly SimpleQueryParser $parser,
        private readonly UserProfileViewBuilder $profileViewBuilder,
    ) {
    }

    /**
     * @throws JsonException
     */
    #[Route(path: '/api/graphql', name: 'app_user_graphql', methods: [Request::METHOD_POST])]
    public function __invoke(Request $request, User $loggedInUser): JsonResponse
    {
        try {
            $payload = $this->parseRequestPayload($request);
        } catch (JsonException $exception) {
            return $this->errorResponse($exception->getMessage());
        }

        $query = $payload['query'] ?? '';
        if (!is_string($query) || $query === '') {
            return $this->errorResponse('Query cannot be empty.');
        }

        $variables = [];
        if (array_key_exists('variables', $payload) && is_array($payload['variables'])) {
            $variables = $payload['variables'];
        }

        try {
            $document = $this->parser->parse($query);
        } catch (InvalidArgumentException $exception) {
            return $this->errorResponse($exception->getMessage());
        }

        $data = [];
        $errors = [];

        foreach ($document as $alias => $node) {
            $field = is_array($node) && array_key_exists('_field', $node)
                ? (string)$node['_field']
                : (string)$alias;
            $arguments = is_array($node) && array_key_exists('_arguments', $node) ? (array)$node['_arguments'] : [];
            $selection = is_array($node) && array_key_exists('_selection', $node) ? $node['_selection'] : true;

            try {
                $resolved = $this->resolveField($field, $selection, $arguments, $variables, $loggedInUser);
                $data[$alias] = $resolved;
            } catch (Throwable $exception) {
                $errors[] = [
                    'message' => $exception->getMessage(),
                    'path' => [(string)$alias],
                ];
                $data[$alias] = null;
            }
        }

        $response = ['data' => $data];
        if ($errors !== []) {
            $response['errors'] = $errors;
        }

        return new JsonResponse($response);
    }

    /**
     * @param array<string, mixed>|true $selection
     * @param array<string, mixed>      $arguments
     *
     * @throws ExceptionInterface
     * @throws JsonException
     */
    private function resolveField(string $field, array|bool $selection, array $arguments, array $variables, User $loggedInUser): mixed
    {
        $arguments = $this->hydrateArguments($arguments, $variables);

        return match ($field) {
            'viewer' => $this->filterData($this->profileViewBuilder->buildProfile($loggedInUser), $selection),
            'profile' => $this->filterData(
                $this->profileViewBuilder->buildProfileByUsername($this->extractUsername($arguments)),
                $selection
            ),
            'stories' => $this->filterData(
                $this->profileViewBuilder->getStoryFeed($loggedInUser, $this->extractLimit($arguments)),
                $selection
            ),
            'groups' => $this->filterData(
                $this->profileViewBuilder->getGroups($loggedInUser),
                $selection
            ),
            'events' => $this->filterData(
                $this->profileViewBuilder->getEvents($loggedInUser, $this->extractLimit($arguments)),
                $selection
            ),
            'followStatuses' => $this->filterData(
                $this->profileViewBuilder->getFollowStatuses($loggedInUser, $this->extractUserIds($arguments)),
                $selection
            ),
            default => throw new RuntimeException(sprintf('Field "%s" is not supported.', $field)),
        };
    }

    /**
     * @param array<string, mixed>|true $selection
     */
    private function filterData(mixed $data, array|bool $selection): mixed
    {
        if ($selection === true) {
            return $data;
        }

        if (array_is_list($data)) {
            return array_map(fn (mixed $item): mixed => $this->filterData($item, $selection), $data);
        }

        if (!is_array($data)) {
            return $data;
        }

        $result = [];
        foreach ($selection as $alias => $node) {
            if (!is_array($node)) {
                continue;
            }

            $field = array_key_exists('_field', $node) ? (string)$node['_field'] : (string)$alias;
            $subSelection = $node['_selection'] ?? true;

            if (!array_key_exists($field, $data)) {
                continue;
            }

            $result[$alias] = $this->filterData($data[$field], $subSelection);
        }

        return $result;
    }

    private function hydrateArguments(array $arguments, array $variables): array
    {
        $resolved = [];

        foreach ($arguments as $name => $value) {
            $resolved[$name] = $this->resolveArgumentValue($value, $variables);
        }

        return $resolved;
    }

    private function resolveArgumentValue(mixed $value, array $variables): mixed
    {
        if (is_array($value) && array_key_exists('__variable', $value)) {
            $variableName = $value['__variable'];

            return is_string($variableName) && array_key_exists($variableName, $variables)
                ? $variables[$variableName]
                : null;
        }

        if (is_array($value)) {
            $resolved = [];
            foreach ($value as $key => $item) {
                $resolved[$key] = $this->resolveArgumentValue($item, $variables);
            }

            return $resolved;
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    private function parseRequestPayload(Request $request): array
    {
        $content = $request->getContent();
        if ($content === '') {
            return [];
        }

        $payload = JSON::decode($content, true);
        if (!is_array($payload)) {
            return [];
        }

        return $payload;
    }

    private function extractLimit(array $arguments): ?int
    {
        if (!array_key_exists('first', $arguments)) {
            return null;
        }

        $limit = $arguments['first'];
        if (!is_numeric($limit)) {
            return null;
        }

        $limit = (int)$limit;

        return $limit > 0 ? $limit : null;
    }

    private function extractUsername(array $arguments): string
    {
        $username = $arguments['username'] ?? null;
        if (!is_string($username) || $username === '') {
            throw new InvalidArgumentException('The "username" argument is required for the profile field.');
        }

        return $username;
    }

    private function extractUserIds(array $arguments): ?array
    {
        if (!array_key_exists('userIds', $arguments)) {
            return null;
        }

        $userIds = $arguments['userIds'];
        if (!is_array($userIds)) {
            return null;
        }

        $filtered = array_filter(
            $userIds,
            static fn (mixed $value): bool => is_string($value) && $value !== ''
        );

        return array_values($filtered);
    }

    private function errorResponse(string $message): JsonResponse
    {
        return new JsonResponse([
            'data' => null,
            'errors' => [
                ['message' => $message],
            ],
        ]);
    }
}
