<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MorceauxRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

/**
 * Morceau (piste) du catalogue collaboratif.
 *
 * Les fichiers (image de pochette + audio) sont gérés par VichUploader.
 */
#[ORM\Entity(repositoryClass: MorceauxRepository::class)]
#[Vich\Uploadable]
class Morceaux
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le titre du morceau est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 50,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $nomMorceaux = null;

    #[Vich\UploadableField(mapping: 'album_image', fileNameProperty: 'imageName')]
    #[Assert\File(
        maxSize: '2M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
        mimeTypesMessage: 'Choisissez une image JPG, PNG ou WebP.',
        maxSizeMessage: 'L\'image ne doit pas dépasser {{ limit }} {{ suffix }}.'
    )]
    private ?File $imageFile = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $imageName = null;

    #[Vich\UploadableField(mapping: 'morceau_musique', fileNameProperty: 'musicName')]
    #[Assert\File(
        maxSize: '20M',
        mimeTypes: [
            'audio/mpeg', 'audio/mp3', 'audio/ogg', 'audio/wav', 'audio/x-wav',
            'audio/webm', 'audio/mp4', 'audio/aac', 'audio/flac', 'audio/x-flac', 'audio/midi',
        ],
        mimeTypesMessage: 'Choisissez un fichier audio valide (MP3, OGG, WAV, FLAC…).',
        maxSizeMessage: 'Le fichier audio ne doit pas dépasser {{ limit }} {{ suffix }}.'
    )]
    private ?File $musicFile = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $musicName = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\LessThanOrEqual('today', message: 'La date de sortie ne peut pas être dans le futur.')]
    private ?\DateTimeInterface $dateSortie = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(message: 'Le nom de l\'artiste est obligatoire.')]
    #[Assert\Length(max: 255, maxMessage: 'Le nom de l\'artiste ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $artiste = null;

    #[ORM\ManyToOne(inversedBy: 'morceauxes', targetEntity: Genre::class)]
    #[ORM\JoinColumn(name: 'genreid_id', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull(message: 'Choisissez un genre musical.')]
    private ?Genre $genreid = null;

    /** Utilisateur ayant ajouté le morceau (null pour les données importées). */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    /** @var Collection<int, Playlist> */
    #[ORM\ManyToMany(targetEntity: Playlist::class, mappedBy: 'morceauxid')]
    private Collection $playlists;

    public function __construct()
    {
        $this->playlists = new ArrayCollection();
    }

    public function __toString(): string
    {
        $artiste = $this->artiste ?? 'Artiste inconnu';
        $titre = $this->nomMorceaux ?? 'Sans titre';

        return sprintf('%s — %s', $artiste, $titre);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomMorceaux(): ?string
    {
        return $this->nomMorceaux;
    }

    public function setNomMorceaux(string $nomMorceaux): self
    {
        $this->nomMorceaux = trim($nomMorceaux);

        return $this;
    }

    public function setImageFile(?File $imageFile = null): self
    {
        $this->imageFile = $imageFile;

        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageName(?string $imageName): self
    {
        $this->imageName = $imageName;

        return $this;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setMusicFile(?File $musicFile = null): self
    {
        $this->musicFile = $musicFile;

        return $this;
    }

    public function getMusicFile(): ?File
    {
        return $this->musicFile;
    }

    public function setMusicName(?string $musicName): self
    {
        $this->musicName = $musicName;

        return $this;
    }

    public function getMusicName(): ?string
    {
        return $this->musicName;
    }

    public function getDateSortie(): ?\DateTimeInterface
    {
        return $this->dateSortie;
    }

    public function setDateSortie(?\DateTimeInterface $dateSortie): self
    {
        $this->dateSortie = $dateSortie;

        return $this;
    }

    public function getArtiste(): ?string
    {
        return $this->artiste;
    }

    public function setArtiste(?string $artiste): self
    {
        $this->artiste = null === $artiste ? null : trim($artiste);

        return $this;
    }

    public function getGenreid(): ?Genre
    {
        return $this->genreid;
    }

    public function setGenreid(?Genre $genreid): self
    {
        $this->genreid = $genreid;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * @return Collection<int, Playlist>
     */
    public function getPlaylists(): Collection
    {
        return $this->playlists;
    }

    public function addPlaylist(Playlist $playlist): self
    {
        if (!$this->playlists->contains($playlist)) {
            $this->playlists->add($playlist);
            $playlist->addMorceauxid($this);
        }

        return $this;
    }

    public function removePlaylist(Playlist $playlist): self
    {
        if ($this->playlists->removeElement($playlist)) {
            $playlist->removeMorceauxid($this);
        }

        return $this;
    }

    /**
     * L'utilisateur peut-il modifier ce morceau ? (propriétaire ou admin)
     */
    public function isEditableBy(?User $user): bool
    {
        if (null === $user) {
            return false;
        }

        return $user->isAdmin() || $this->createdBy?->getId() === $user->getId();
    }
}
