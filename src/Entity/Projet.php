<?php

namespace App\Entity;

use App\Entity\User;
use App\Entity\Traits\Timestamp;
use App\Repository\ProjetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjetRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_projet_status', columns: ['status'])]
class Projet
{
    use Timestamp;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'encrypted_text', nullable: true)]
    private ?string $accesCodeSource = null;

    #[ORM\Column(type: 'encrypted_text', nullable: true)]
    private ?string $accesEnvironnement = null;

    /** Expiry date of the TLS certificate of the project URL, filled by app:check-ssl */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $sslExpiresAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $sslCheckedAt = null;

    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'à faire'])]
    #[Assert\Choice(choices: ['à faire', 'en cours', 'terminé'], message: 'Choix invalide pour le statut.')]
    private string $status = 'à faire';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url = null;

    #[ORM\ManyToMany(targetEntity: Technologie::class, inversedBy: 'projets')]
    private Collection $technologie;

    #[ORM\ManyToMany(targetEntity: Developpeur::class, inversedBy: 'projets')]
    private Collection $developpeur;

    #[ORM\ManyToMany(targetEntity: Hebergement::class, inversedBy: 'projets')]
    private Collection $hebergement;

    #[ORM\OneToMany(mappedBy: 'projet', targetEntity: Notification::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $notifications;

    #[ORM\ManyToOne(targetEntity: Societe::class, inversedBy: 'projets')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private $societe;

    /* Missing columns */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $suiviPar = null;

    // Développeur principal (user)
    #[ORM\ManyToOne(targetEntity: Developpeur::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Developpeur $developpeurPrincipal = null;

    // Remarques
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $remarques = null;

    // Priorité
    #[ORM\Column(type: 'string', length: 20, nullable: false, options: ['default' => 'normal'])]
    #[Assert\Choice(choices: ['normal', 'moyen', 'urgent', 'tres urgent'])]
    private string $priorite = 'normal';

    // Date livraison
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateLivraison = null;

    // Risque de livraison
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $risqueLivraison = null;

    // Date de commande / avance
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateCommande = null;

    // Commercial (user)
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $commercial = null;

    // Projet testé
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $projetTeste = false;

    // Projet validé
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $projetValide = false;

    // Client à consommer la gratuité
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $clientGratuite = false;

    // Client à demander des améliorations
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $clientAmeliorations = false;

    public function __construct()
    {
        $this->technologie = new ArrayCollection();
        $this->developpeur = new ArrayCollection();
        $this->hebergement = new ArrayCollection();
        $this->notifications = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getAccesCodeSource(): ?string
    {
        return $this->accesCodeSource;
    }

    public function setAccesCodeSource(?string $accesCodeSource): static
    {
        $this->accesCodeSource = $accesCodeSource;
        return $this;
    }

    public function getAccesEnvironnement(): ?string
    {
        return $this->accesEnvironnement;
    }

    public function setAccesEnvironnement(?string $accesEnvironnement): static
    {
        $this->accesEnvironnement = $accesEnvironnement;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
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

    public function getTechnologie(): Collection
    {
        return $this->technologie;
    }

    public function addTechnologie(Technologie $technologie): static
    {
        if (!$this->technologie->contains($technologie)) {
            $this->technologie->add($technologie);
        }
        return $this;
    }

    public function removeTechnologie(Technologie $technologie): static
    {
        $this->technologie->removeElement($technologie);
        return $this;
    }

    public function getDeveloppeur(): Collection
    {
        return $this->developpeur;
    }

    public function addDeveloppeur(Developpeur $developpeur): static
    {
        if (!$this->developpeur->contains($developpeur)) {
            $this->developpeur->add($developpeur);
        }
        return $this;
    }

    public function removeDeveloppeur(Developpeur $developpeur): static
    {
        $this->developpeur->removeElement($developpeur);
        return $this;
    }

    public function getHebergement(): Collection
    {
        return $this->hebergement;
    }

    public function addHebergement(Hebergement $hebergement): static
    {
        if (!$this->hebergement->contains($hebergement)) {
            $this->hebergement->add($hebergement);
        }
        return $this;
    }

    public function removeHebergement(Hebergement $hebergement): static
    {
        $this->hebergement->removeElement($hebergement);
        return $this;
    }

    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications[] = $notification;
            $notification->setProjet($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            if ($notification->getProjet() === $this) {
                $notification->setProjet(null);
            }
        }

        return $this;
    }

    public function getSociete(): ?Societe
    {
        return $this->societe;
    }

    public function setSociete(?Societe $societe): self
    {
        $this->societe = $societe;
        return $this;
    }

    // Suivi par (superviseur)
    public function getSuiviPar(): ?User
    {
        return $this->suiviPar;
    }

    public function setSuiviPar(?User $suiviPar): self
    {
        $this->suiviPar = $suiviPar;
        return $this;
    }

    // Développeur principal
    public function getDeveloppeurPrincipal(): ?Developpeur
    {
        return $this->developpeurPrincipal;
    }

    public function setDeveloppeurPrincipal(?Developpeur $developpeurPrincipal): self
    {
        $this->developpeurPrincipal = $developpeurPrincipal;
        return $this;
    }

    // Remarques
    public function getRemarques(): ?string
    {
        return $this->remarques;
    }

    public function setRemarques(?string $remarques): self
    {
        $this->remarques = $remarques;
        return $this;
    }

    // Priorité
    public function getPriorite(): string
    {
        return $this->priorite;
    }

    public function setPriorite(string $priorite): self
    {
        $this->priorite = $priorite;
        return $this;
    }

    // Date livraison
    public function getDateLivraison(): ?\DateTimeInterface
    {
        return $this->dateLivraison;
    }

    public function setDateLivraison(?\DateTimeInterface $dateLivraison): self
    {
        $this->dateLivraison = $dateLivraison;
        return $this;
    }

    // Risque de livraison
    public function getRisqueLivraison(): ?string
    {
        return $this->risqueLivraison;
    }

    public function setRisqueLivraison(?string $risqueLivraison): self
    {
        $this->risqueLivraison = $risqueLivraison;
        return $this;
    }

    // Date commande / avance
    public function getDateCommande(): ?\DateTimeInterface
    {
        return $this->dateCommande;
    }

    public function setDateCommande(?\DateTimeInterface $dateCommande): self
    {
        $this->dateCommande = $dateCommande;
        return $this;
    }

    // Commercial
    public function getCommercial(): ?User
    {
        return $this->commercial;
    }

    public function setCommercial(?User $commercial): self
    {
        $this->commercial = $commercial;
        return $this;
    }

    // Projet testé
    public function isProjetTeste(): bool
    {
        return $this->projetTeste;
    }

    public function setProjetTeste(bool $projetTeste): self
    {
        $this->projetTeste = $projetTeste;
        return $this;
    }

    // Projet validé
    public function isProjetValide(): bool
    {
        return $this->projetValide;
    }

    public function setProjetValide(bool $projetValide): self
    {
        $this->projetValide = $projetValide;
        return $this;
    }

    // Client à consommer la gratuité
    public function isClientGratuite(): bool
    {
        return $this->clientGratuite;
    }

    public function setClientGratuite(bool $clientGratuite): self
    {
        $this->clientGratuite = $clientGratuite;
        return $this;
    }

    // Client à demander des améliorations
    public function isClientAmeliorations(): bool
    {
        return $this->clientAmeliorations;
    }

    public function setClientAmeliorations(bool $clientAmeliorations): self
    {
        $this->clientAmeliorations = $clientAmeliorations;
        return $this;
    }

    public function getSslExpiresAt(): ?\DateTimeImmutable
    {
        return $this->sslExpiresAt;
    }

    public function setSslExpiresAt(?\DateTimeImmutable $sslExpiresAt): static
    {
        $this->sslExpiresAt = $sslExpiresAt;

        return $this;
    }

    public function getSslCheckedAt(): ?\DateTimeImmutable
    {
        return $this->sslCheckedAt;
    }

    public function setSslCheckedAt(?\DateTimeImmutable $sslCheckedAt): static
    {
        $this->sslCheckedAt = $sslCheckedAt;

        return $this;
    }
}
