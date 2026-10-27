<?php

namespace App\Service\Monitoring;

use App\Entity\Projet;
use App\Entity\UptimeCheck;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Turns the result of UrlCheckerService::checkUrl() into a stored UptimeCheck.
 * It does not flush: the caller decides when to write.
 */
class UptimeRecorder
{
    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    /**
     * @param array{success: bool, status_code: ?int, error: ?string, response_time_ms?: ?int} $result
     */
    public function record(Projet $projet, array $result): UptimeCheck
    {
        $check = (new UptimeCheck())
            ->setProjet($projet)
            ->setSuccess($result['success'])
            ->setStatusCode($result['status_code'])
            ->setResponseTimeMs($result['response_time_ms'] ?? null)
            ->setError($result['error']);

        $this->entityManager->persist($check);

        return $check;
    }
}
