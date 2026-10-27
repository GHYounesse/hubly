<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait Timestamp
{
    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $created = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $updated = null;

    public function getCreated(): ?\DateTime
    {
        return $this->created;
    }

    public function setCreated(\DateTime $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    public function setUpdated(\DateTime $updated): self
    {
        $this->updated = $updated;
        return $this;
    }

    #[ORM\PrePersist]
    public function setTimestampsOnPrePersist(): void
    {
        $now = new \DateTime();
        $this->created = $now;
        $this->updated = $now;
    }

    #[ORM\PreUpdate]
    public function setUpdatedTimestamp(): void
    {
        $this->updated = new \DateTime();
    }
}
