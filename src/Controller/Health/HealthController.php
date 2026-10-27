<?php

namespace App\Controller\Health;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Liveness and readiness probe for the hosting platform and for uptime monitors.
 * Public on purpose and free of any detail an attacker could use.
 */
final class HealthController extends AbstractController
{
    #[Route('/health', name: 'app_health', methods: ['GET'])]
    public function __invoke(Connection $connection): JsonResponse
    {
        try {
            $connection->executeQuery('SELECT 1');
            $database = 'ok';
        } catch (\Throwable) {
            $database = 'down';
        }

        $healthy = $database === 'ok';

        return new JsonResponse(
            ['status' => $healthy ? 'ok' : 'degraded', 'database' => $database],
            $healthy ? 200 : 503,
            ['Cache-Control' => 'no-store'],
        );
    }
}
