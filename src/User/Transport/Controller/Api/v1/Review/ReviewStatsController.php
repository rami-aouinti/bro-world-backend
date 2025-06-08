<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Review;

use App\Tool\Domain\Entity\Review;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ReviewStatsController
 *
 * @package App\Tool\Transport\Controller\Api
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[Route('/reviews')]
class ReviewStatsController extends AbstractController
{
    #[Route('/stats', name: 'review_stats', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get review statistics',
        tags: ['Review'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Reviews'
            )
        ]
    )]
    public function __invoke(EntityManagerInterface $em): JsonResponse
    {
        $repository = $em->getRepository(Review::class);

        $ratings = $repository->createQueryBuilder('r')
            ->select('r.rating')
            ->getQuery()
            ->getArrayResult();

        $count = count($ratings);
        $sum = array_reduce($ratings, fn($carry, $item) => $carry + $item['rating'], 0);
        $average = $count > 0 ? round($sum / $count, 2) : 0;

        $buckets = [
            '0-1' => 0,
            '1-2' => 0,
            '2-3' => 0,
            '3-4' => 0,
            '4-5' => 0,
        ];

        foreach ($ratings as $item) {
            $rating = $item['rating'];
            if ($rating < 1) {
                $buckets['0-1']++;
            } elseif ($rating < 2) {
                $buckets['1-2']++;
            } elseif ($rating < 3) {
                $buckets['2-3']++;
            } elseif ($rating < 4) {
                $buckets['3-4']++;
            } else {
                $buckets['4-5']++;
            }
        }

        return new JsonResponse([
            'total_reviews' => $count,
            'average_rating' => $average,
            'distribution' => $buckets,
        ]);
    }
}
