<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Entity;

use App\Entity\User;
use c975L\UiBundle\Repository\BlockRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BlockRepository::class)]
#[ORM\Table(name: 'site_block')]
class Block
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $kind = null;

    #[ORM\Column(nullable: true)]
    private int $position = 0;

    #[ORM\Column(type: Types::JSON)]
    private array $data = [];

    #[ORM\ManyToOne(inversedBy: 'blocks')]
    private ?User $user = null;

    #[ORM\OneToMany(mappedBy: 'block', targetEntity: Media::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $medias;

    // Translated label of the kind, resolved by BlockLabelListener on postLoad (not persisted)
    private ?string $label = null;

    public function __construct()
    {
        $this->medias = new ArrayCollection();
    }

    public function __toString(): string
    {
        $kindLabel = $this->label ?? ucfirst((string) $this->kind);

        $title = $this->data['title'] ?? null;
        if (is_string($title) && '' !== trim($title)) {
            return '(#' . $this->position . ') ' . $kindLabel . ' - ' . $title;
        }

        return '(#' . $this->position . ') ' . $kindLabel;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKind(): ?string
    {
        return $this->kind;
    }

    public function setKind(?string $kind): self
    {
        $this->kind = $kind;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position ?? 0;

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /** @return Collection<int, Media> */
    public function getMedias(): Collection
    {
        return $this->medias;
    }

    /** Twig alias: block.media still works */
    public function getMedia(): Collection
    {
        return $this->medias;
    }

    public function addMedia(Media $media): self
    {
        if (!$this->medias->contains($media)) {
            $this->medias->add($media);
            $media->setBlock($this);
        }

        return $this;
    }

    public function removeMedia(Media $media): self
    {
        if ($this->medias->removeElement($media)) {
            if ($media->getBlock() === $this) {
                $media->setBlock(null);
            }
        }

        return $this;
    }
}