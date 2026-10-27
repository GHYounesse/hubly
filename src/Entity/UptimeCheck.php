<?php

namespace App\Entity;

use App\Repository\UptimeCheckRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * One availability check of a project URL, kept to draw its uptime history.
 */
#[ORM\Entity(repositoryClass: UptimeCheckRepository::class)]
#[ORM\Index(name: 'idx_uptime_projet_checked', columns: ['projet_id', 'checked_at'])]
class UptimeCheck
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Projet::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Projet $projet = null;

    #[ORM\Column]
    private \DateTimeImmutable $checkedAt;

    #[ORM\Column(nullable: true)]
    private ?int $statusCode = null;

    #[ORM\Column(nullable: true)]
    private ?int $responseTimeMs = null;

    #[ORM\Column]
    private bool $success = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $error = null;

    public function __construct()
    {
        $this->checkedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProjet(): ?Projet
    {
        return $this->projet;
    }

    public function setProjet(Projet $projet): static
    {
        $this->projet = $projet;

        return $this;
    }

    public function getCheckedAt(): \DateTimeImmutable
    {
        return $this->checkedAt;
    }

    public function setCheckedAt(\DateTimeImmutable $checkedAt): static
    {
        $this->checkedAt = $checkedAt;

        return $this;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function setStatusCode(?int $statusCode): static
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function getResponseTimeMs(): ?int
    {
        return $this->responseTimeMs;
    }

    public function setResponseTimeMs(?int $responseTimeMs): static
    {
        $this->responseTimeMs = $responseTimeMs;

        return $this;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): static
    {
        $this->success = $success;

        return $this;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): static
    {
        $this->error = $error !== null ? mb_substr($error, 0, 255) : null;

        return $this;
    }
}
