<?php

namespace App\Entity;

use App\Repository\BannedRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=BannedRepository::class)
 */
class Banned
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $nbBannis;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNbBannis(): ?int
    {
        return $this->nbBannis;
    }

    public function setNbBannis(?int $nbBannis): self
    {
        $this->nbBannis = $nbBannis;

        return $this;
    }
}
