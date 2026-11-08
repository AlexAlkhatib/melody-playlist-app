<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PlaylistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Playlist appartenant à un utilisateur et contenant des morceaux.
 */
#[ORM\Entity(repositoryClass: PlaylistRepository::class)]
class Playlist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Donnez un nom à votre playlist.')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $nomPlaylist = null;

    /** Nombre de morceaux, maintenu automatiquement (voir syncNbMorceaux()). */
    #[ORM\Column(nullable: true)]
    private ?int $nbMorceaux = 0;

    /** @var Collection<int, Morceaux> */
    #[ORM\ManyToMany(targetEntity: Morceaux::class, inversedBy: 'playlists')]
    #[ORM\JoinTable(name: 'playlist_morceaux')]
    #[ORM\JoinColumn(name: 'playlist_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'morceaux_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $morceauxid;

    #[ORM\ManyToOne(inversedBy: 'playlists', targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'userid_id', referencedColumnName: 'id', nullable: true)]
    private ?User $userid = null;

    public function __construct()
    {
        $this->morceauxid = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->nomPlaylist;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomPlaylist(): ?string
    {
        return $this->nomPlaylist;
    }

    public function setNomPlaylist(string $nomPlaylist): self
    {
        $this->nomPlaylist = trim($nomPlaylist);

        return $this;
    }

    public function getNbMorceaux(): ?int
    {
        return $this->nbMorceaux;
    }

    public function setNbMorceaux(?int $nbMorceaux): self
    {
        $this->nbMorceaux = $nbMorceaux;

        return $this;
    }

    /**
     * Recale le compteur sur le nombre réel de morceaux.
     */
    public function syncNbMorceaux(): self
    {
        $this->nbMorceaux = $this->morceauxid->count();

        return $this;
    }

    /**
     * @return Collection<int, Morceaux>
     */
    public function getMorceauxid(): Collection
    {
        return $this->morceauxid;
    }

    public function addMorceauxid(Morceaux $morceaux): self
    {
        if (!$this->morceauxid->contains($morceaux)) {
            $this->morceauxid->add($morceaux);
            $morceaux->addPlaylist($this);
            $this->syncNbMorceaux();
        }

        return $this;
    }

    public function removeMorceauxid(Morceaux $morceaux): self
    {
        if ($this->morceauxid->removeElement($morceaux)) {
            $this->syncNbMorceaux();
        }

        return $this;
    }

    public function getUserid(): ?User
    {
        return $this->userid;
    }

    public function setUserid(?User $userid): self
    {
        $this->userid = $userid;

        return $this;
    }

    public function isOwnedBy(?User $user): bool
    {
        if (null === $user) {
            return false;
        }

        return $user->isAdmin() || $this->userid?->getId() === $user->getId();
    }
}
