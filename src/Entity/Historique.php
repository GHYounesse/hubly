<?php

namespace App\Entity;

use App\Repository\HistoriqueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;

#[ORM\Entity(repositoryClass: HistoriqueRepository::class)]
#[ORM\Index(name: 'idx_historique_date_modification', columns: ['date_modification'])]
class Historique
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date_modification = null;

    #[ORM\Column(length: 255)]
    private ?string $entiteModifiee = null;

    #[ORM\Column(length: 50)]
    private string $action = 'modification'; // valeur par défaut pour éviter null

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(nullable: true)]
    private ?int $entiteModifieeId = null;

    /**
     * Before / after value of each changed field, e.g. ['statut' => ['old' => 'à faire', 'new' => 'en cours']].
     *
     * @var array<string, array{old: mixed, new: mixed}>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $changes = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->date_modification;
    }

    public function setDateModification(\DateTimeInterface $date_modification): static
    {
        $this->date_modification = $date_modification;
        return $this;
    }

    public function getEntiteModifiee(): ?string
    {
        return $this->entiteModifiee;
    }

    public function setEntiteModifiee(string $entiteModifiee): static
    {
        $this->entiteModifiee = $entiteModifiee;
        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getEntiteModifieeId(): ?int
    {
        return $this->entiteModifieeId;
    }

    public function setEntiteModifieeId(?int $entiteModifieeId): static
    {
        $this->entiteModifieeId = $entiteModifieeId;

        return $this;
    }

    /** @return array<string, array{old: mixed, new: mixed}> */
    public function getChanges(): array
    {
        return $this->changes ?? [];
    }

    /** @param array<string, array{old: mixed, new: mixed}>|null $changes */
    public function setChanges(?array $changes): static
    {
        $this->changes = $changes === [] ? null : $changes;

        return $this;
    }
}
