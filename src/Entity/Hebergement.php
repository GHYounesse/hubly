<?php

namespace App\Entity;

use App\Entity\Traits\Timestamp;
use App\Repository\HebergementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HebergementRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_hebergement_date_expiration', columns: ['date_expiration'])]
class Hebergement
{
    use Timestamp;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fournisseur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateExpiration = null;

    #[ORM\Column(type: 'boolean')]
    private bool $estActif = false; // par défaut inactif


    /**
     * @var Collection<int, Projet>
     */
    #[ORM\ManyToMany(targetEntity: Projet::class, mappedBy: 'hebergement')]
    private Collection $projets;

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'hebergement')]
    private Collection $notifications;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $site = null;

    #[ORM\Column(type: 'encrypted_text', nullable: true)]
    private ?string $acces = null;

    /** Price of one billing period, in the agency currency */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $prix = null;

    /** "mensuel" or "annuel" */
    #[ORM\Column(length: 10, options: ['default' => 'annuel'])]
    private string $periodicite = 'annuel';

    public function __construct()
    {
        $this->projets = new ArrayCollection();
        $this->notifications = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFournisseur(): ?string
    {
        return $this->fournisseur;
    }

    public function setFournisseur(?string $fournisseur): static
    {
        $this->fournisseur = $fournisseur;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getDateExpiration(): ?\DateTimeInterface
    {
        return $this->dateExpiration;
    }

    public function setDateExpiration(?\DateTimeInterface $dateExpiration): static
    {
        $this->dateExpiration = $dateExpiration;

        return $this;
    }
    public function isEstActif(): bool
    {
        return $this->estActif;
    }

    public function setEstActif(bool $estActif): self
    {
        $this->estActif = $estActif;
        return $this;
    }

    /**
     * @return Collection<int, Projet>
     */
    public function getProjets(): Collection
    {
        return $this->projets;
    }

    public function addProjet(Projet $projet): static
    {
        if (!$this->projets->contains($projet)) {
            $this->projets->add($projet);
            $projet->addHebergement($this);
        }

        return $this;
    }

    public function removeProjet(Projet $projet): static
    {
        if ($this->projets->removeElement($projet)) {
            $projet->removeHebergement($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setHebergement($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getHebergement() === $this) {
                $notification->setHebergement(null);
            }
        }

        return $this;
    }

    public function getSite(): ?string
    {
        return $this->site;
    }

    public function setSite(?string $site): static
    {
        $this->site = $site;

        return $this;
    }

    public function getAcces(): ?string
    {
        return $this->acces;
    }

    public function setAcces(?string $acces): static
    {
        $this->acces = $acces;

        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(?string $prix): static
    {
        $this->prix = $prix === '' ? null : $prix;

        return $this;
    }

    public function getPeriodicite(): string
    {
        return $this->periodicite;
    }

    public function setPeriodicite(string $periodicite): static
    {
        $this->periodicite = $periodicite;

        return $this;
    }

    /** What this plan costs over twelve months, or null when no price is set. */
    public function getCoutAnnuel(): ?float
    {
        if ($this->prix === null) {
            return null;
        }

        return (float) $this->prix * ($this->periodicite === 'mensuel' ? 12 : 1);
    }
}
