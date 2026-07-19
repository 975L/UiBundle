<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Entity;

use c975L\UiBundle\Repository\EmailBlockRepository;
use Doctrine\ORM\Mapping as ORM;

// One row of an EmailTemplate's body. Unlike c975L\UiBundle\Entity\Block, kinds are a small closed vocabulary
// (see TYPES) resolved by a plain match() in EmailTemplateRenderer, not a DI-tagged registry - and unlike that
// same Block, every kind shares one flat set of columns (same principle as FormField, see FormFieldType's own
// docblock) instead of a per-kind dynamic sub-form: simpler to author/review for a surface this small, and each
// column only means something for the kind(s) noted below - EmailBlockType shows/hides them client-side accordingly
#[ORM\Entity(repositoryClass: EmailBlockRepository::class)]
#[ORM\Table(name: 'site_email_block')]
class EmailBlock
{
    public const TYPE_HEADING = 'heading';
    public const TYPE_TEXT = 'text';
    public const TYPE_BUTTON = 'button';
    public const TYPE_IMAGE = 'image';
    public const TYPE_DIVIDER = 'divider';
    public const TYPE_SPACER = 'spacer';
    public const TYPE_FIELDS_TABLE = 'fields_table';

    public const TYPES = [
        self::TYPE_HEADING,
        self::TYPE_TEXT,
        self::TYPE_BUTTON,
        self::TYPE_IMAGE,
        self::TYPE_DIVIDER,
        self::TYPE_SPACER,
        self::TYPE_FIELDS_TABLE,
    ];

    public const LEVEL_H1 = 'h1';
    public const LEVEL_H2 = 'h2';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: EmailTemplate::class, inversedBy: 'blocks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?EmailTemplate $emailTemplate = null;

    #[ORM\Column(length: 20)]
    private string $type = self::TYPE_TEXT;

    #[ORM\Column(nullable: true)]
    private ?int $position = null;

    // TYPE_HEADING's text
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $heading = null;

    // TYPE_HEADING's size - self::LEVEL_H1/LEVEL_H2
    #[ORM\Column(length: 2, nullable: true)]
    private ?string $level = null;

    // TYPE_TEXT - plain text, split into <p> paragraphs on blank lines by EmailTemplateRenderer (deliberately not
    // rich/Trix text for v1: keeps the email-safe HTML fully controlled server-side, no risk of an editor-authored
    // tag surviving into a client that mangles it, same class of bug as the Process/Steps "<p><p>" issue)
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $content = null;

    // TYPE_BUTTON's label
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $label = null;

    // TYPE_BUTTON's target / TYPE_IMAGE's src - TextType, not UrlType: may hold a "{{ variable }}" placeholder
    // resolved at render time (see EmailTemplateRenderer), same reasoning as Block's own url fields (see ChangeLog)
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $url = null;

    // TYPE_IMAGE's alt text
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $alt = null;

    // TYPE_SPACER's height, in pixels
    #[ORM\Column(nullable: true)]
    private ?int $height = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmailTemplate(): ?EmailTemplate
    {
        return $this->emailTemplate;
    }

    public function setEmailTemplate(?EmailTemplate $emailTemplate): self
    {
        $this->emailTemplate = $emailTemplate;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position ?? 0;

        return $this;
    }

    public function getHeading(): ?string
    {
        return $this->heading;
    }

    public function setHeading(?string $heading): self
    {
        $this->heading = $heading;

        return $this;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(?string $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
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

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getAlt(): ?string
    {
        return $this->alt;
    }

    public function setAlt(?string $alt): self
    {
        $this->alt = $alt;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): self
    {
        $this->height = $height;

        return $this;
    }
}
