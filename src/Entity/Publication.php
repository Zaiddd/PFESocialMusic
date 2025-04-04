<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PublicationRepository")
 */
class Publication implements \Serializable
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255,  nullable=true)
     */
    private $commentaire;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\File(mimeTypes={ "image/png", "image/jpeg" })
     */
    private $champPhoto;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="publications")
     */
    private $user;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $reponses = [];

    /**
     * @ORM\Column(type="date")
     */
    private $date;

    /**
     * @ORM\Column(type="boolean")
     */
    private $partage;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $userQuiCommente = [];

    /**
     * @ORM\Column(type="integer")
     */
    private $nbLike;

    /**
     * @ORM\Column(type="integer")
     */
    private $nbDislike;

    /**
     * @ORM\OneToMany(targetEntity=Signal::class, mappedBy="publication")
     */
    private $signals;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $userOriginal;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $idUserOriginal;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $publiSuivie;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $userAyantSuivi;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $tags;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $listeUserQuiLike = [];

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $listeUserQuiDislike = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $spotify;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $deezer;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $idPubliOriginale;

    /**
     * @ORM\Column(type="boolean")
     */
    private $publiSupprimee;

    public function __construct()
    {
        $this->signals = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): self
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getChampPhoto()
    {
        return $this->champPhoto;
    }

    public function setChampPhoto($champPhoto)
    {
        $this->champPhoto = $champPhoto;

        return $this;
    }



    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function __toString() {
        return $this->getCommentaire();
    }

    public function setIdPublication(self $idPublication): self
    {
        $this->idPublication = $idPublication;

        return $this;
    }

    public function getReponses(): ?array
    {
        return $this->reponses;
    }

    public function setReponses(?array $reponses): self
    {
        $this->reponses = $reponses;

        return $this;
    }

    /**
     * String representation of object
     * @link https://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        // TODO: Implement serialize() method.
        return serialize(array(
            $this->reponses,
        ));
    }

    /**
     * Constructs the object
     * @link https://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function unserialize($serialized)
    {
        // TODO: Implement unserialize() method.
        list (
            $this->reponses
            ) = unserialize($serialized, ['allowed_classes' => false]);
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getPartage(): ?bool
    {
        return $this->partage;
    }

    public function setPartage(bool $partage): self
    {
        $this->partage = $partage;

        return $this;
    }

    public function getUserQuiCommente(): ?array
    {
        return $this->userQuiCommente;
    }

    public function setUserQuiCommente(?array $userQuiCommente): self
    {
        $this->userQuiCommente = $userQuiCommente;

        return $this;
    }

    public function getNbLike(): ?int
    {
        return $this->nbLike;
    }

    public function setNbLike(int $nbLike): self
    {
        $this->nbLike = $nbLike;

        return $this;
    }

    public function getNbDislike(): ?int
    {
        return $this->nbDislike;
    }

    public function setNbDislike(int $nbDislike): self
    {
        $this->nbDislike = $nbDislike;

        return $this;
    }

    /**
     * @return Collection|Signal[]
     */
    public function getSignals(): Collection
    {
        return $this->signals;
    }

    public function addSignal(Signal $signal): self
    {
        if (!$this->signals->contains($signal)) {
            $this->signals[] = $signal;
            $signal->setPublication($this);
        }

        return $this;
    }

    public function removeSignal(Signal $signal): self
    {
        if ($this->signals->contains($signal)) {
            $this->signals->removeElement($signal);
            // set the owning side to null (unless already changed)
            if ($signal->getPublication() === $this) {
                $signal->setPublication(null);
            }
        }

        return $this;
    }

    public function getUserOriginal(): ?string
    {
        return $this->userOriginal;
    }

    public function setUserOriginal(string $userOriginal): self
    {
        $this->userOriginal = $userOriginal;

        return $this;
    }

    public function getIdUserOriginal(): ?int
    {
        return $this->idUserOriginal;
    }

    public function setIdUserOriginal(?int $idUserOriginal): self
    {
        $this->idUserOriginal = $idUserOriginal;

        return $this;
    }

    public function getPubliSuivie(): ?bool
    {
        return $this->publiSuivie;
    }

    public function setPubliSuivie(?bool $publiSuivie): self
    {
        $this->publiSuivie = $publiSuivie;

        return $this;
    }

    public function getUserAyantSuivi(): ?int
    {
        return $this->userAyantSuivi;
    }

    public function setUserAyantSuivi(?int $userAyantSuivi): self
    {
        $this->userAyantSuivi = $userAyantSuivi;

        return $this;
    }

    public function getTags(): ?string
    {
        return $this->tags;
    }

    public function setTags(?string $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    public function getListeUserQuiLike(): ?array
    {
        return $this->listeUserQuiLike;
    }

    public function setListeUserQuiLike(?array $listeUserQuiLike): self
    {
        $this->listeUserQuiLike = $listeUserQuiLike;

        return $this;
    }

    public function getListeUserQuiDislike(): ?array
    {
        return $this->listeUserQuiDislike;
    }

    public function setListeUserQuiDislike(?array $listeUserQuiDislike): self
    {
        $this->listeUserQuiDislike = $listeUserQuiDislike;

        return $this;
    }

    public function getSpotify(): ?string
    {
        return $this->spotify;
    }

    public function setSpotify(?string $spotify): self
    {
        $this->spotify = $spotify;

        return $this;
    }

    public function getDeezer(): ?string
    {
        return $this->deezer;
    }

    public function setDeezer(?string $deezer): self
    {
        $this->deezer = $deezer;

        return $this;
    }

    public function getIdPubliOriginale(): ?int
    {
        return $this->idPubliOriginale;
    }

    public function setIdPubliOriginale(?int $idPubliOriginale): self
    {
        $this->idPubliOriginale = $idPubliOriginale;

        return $this;
    }

    public function getPubliSupprimee(): ?bool
    {
        return $this->publiSupprimee;
    }

    public function setPubliSupprimee(bool $publiSupprimee): self
    {
        $this->publiSupprimee = $publiSupprimee;

        return $this;
    }
}
