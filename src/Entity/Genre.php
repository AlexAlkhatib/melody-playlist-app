<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\GenreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Genre musical (Rock, Jazz, Électro…).
 */
#[ORM\Entity(repositoryClass: GenreRepository::class)]
#[UniqueEntity(fields: ['nomGenre'], message: 'Ce genre existe déjà.')]
class Genre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du genre est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 60,
        minMessage: 'Le nom du genre doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom du genre ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $nomGenre = null;

    /** @var Collection<int, Morceaux> */
    #[ORM\OneToMany(mappedBy: 'genreid', targetEntity: Morceaux::class)]
    private Collection $morceauxes;

    public function __construct()
    {
        $this->morceauxes = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->nomGenre;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomGenre(): ?string
    {
        return $this->nomGenre;
    }

    public function setNomGenre(string $nomGenre): self
    {
        $this->nomGenre = trim($nomGenre);

        return $this;
    }

    /**
     * @return Collection<int, Morceaux>
     */
    public function getMorceauxes(): Collection
    {
        return $this->morceauxes;
    }

    public function addMorceaux(Morceaux $morceaux): self
    {
        if (!$this->morceauxes->contains($morceaux)) {
            $this->morceauxes->add($morceaux);
            $morceaux->setGenreid($this);
        }

        return $this;
    }

    public function removeMorceaux(Morceaux $morceaux): self
    {
        if ($this->morceauxes->removeElement($morceaux) && $morceaux->getGenreid() === $this) {
            $morceaux->setGenreid(null);
        }

        return $this;
    }
}
