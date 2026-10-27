<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Projet;
use App\Repository\SocieteRepository;

#[ORM\Entity(repositoryClass: SocieteRepository::class)]
#[ORM\Table(name: 'societe')]
class Societe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $denomination;

    #[ORM\Column(type: 'string', length: 50)]
    private $statut;

    #[ORM\Column(type: 'datetime')]
    private $createdAt;

    #[ORM\Column(type: 'datetime')]
    private $updatedAt;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $adresse;

    #[ORM\Column(type: 'string', length: 180, nullable: true)]
    private $email;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private $phone;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private $ice;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private $logo;

    #[ORM\Column(type: 'text', nullable: false)]
    private $description;

    #[ORM\OneToMany(mappedBy: 'societe', targetEntity: Projet::class, cascade: ['persist', 'remove'])]
    private $projets;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: "societes")]
    private Collection $users;

    public function __construct()
    {
        $this->projets = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDenomination(): ?string
    {
        return $this->denomination;
    }
    public function setDenomination(string $denomination): self
    {
        $this->denomination = $denomination;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }
    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }
    public function setAdresse(?string $adresse): self
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }
    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }
    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }
    public function getIce(): ?string
    {
        return $this->ice;
    }
    public function setIce(?string $ice): self
    {
        $this->ice = $ice;
        return $this;
    }
    
    public function getLogo(): ?string
    {
        return $this->logo;
    }
    public function setLogo(?string $logo): self
    {
        $this->logo = $logo;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function setUsers(Collection $users): self
    {   
        
        foreach ($this->getUsers() as $user) {
            $this->removeUser($user);
        }

        foreach ($users as $user) {
            $this->addUser($user);
        }

        return $this;
    }
    

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addSociete($this);
        }
        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            $user->removeSociete($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, Projet>
     */
    public function getProjets(): Collection
    {
        return $this->projets;
    }

    public function addProjet(Projet $projet): self
    {
        if (!$this->projets->contains($projet)) {
            $this->projets[] = $projet;
            $projet->setSociete($this);
        }
        return $this;
    }

    public function removeProjet(Projet $projet): self
    {
        if ($this->projets->removeElement($projet)) {
            if ($projet->getSociete() === $this) {
                $projet->setSociete(null);
            }
        }
        return $this;
    }
}
