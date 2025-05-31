<?php

declare(strict_types=1);

namespace App\General\Infrastructure\Service;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * Class MercureService
 *
 * @package App\General\Infrastructure\Service
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
readonly class MercureService
{
    public function __construct(private HubInterface $hub) {}

    /**
     * @param        $username
     * @param string $scope
     *
     * @return void
     */
    public function create($username, string $scope): void
    {
    $this->hub->publish(new Update(
        $scope,
        json_encode(['type' => 'new_story', 'user' => $username])
    ));
    }
}
