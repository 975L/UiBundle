<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Registry;

use Symfony\Contracts\Translation\TranslatorInterface;

// Check readme for use
class BlockRegistry
{
    private array $blocks = [];
    private array $labelCache = [];
    private array $descriptionCache = [];
    private array $categoryCache = [];
    private ?array $groupedCache = null;

    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function register(
        string $kind,
        string $label,
        string $formClass,
        string $template,
        string $category = 'label.category_general',
        array $mediaTypes = [],
        string $translationDomain = 'ui',
        string $description = ''
    ): void {
        $this->blocks[$kind] = [
            'label'       => $label,
            'domain'      => $translationDomain,
            'form'        => $formClass,
            'template'    => $template,
            'category'    => $category,
            'mediaTypes'  => $mediaTypes,
            'description' => $description,
        ];
    }

    // Gets the translated label of a block kind (falls back to the raw label if untranslated)
    public function getLabel(string $kind): string
    {
        if (!isset($this->labelCache[$kind])) {
            $block = $this->get($kind);
            $this->labelCache[$kind] = $this->translator->trans($block['label'], [], $block['domain']);
        }

        return $this->labelCache[$kind];
    }

    // Gets the translated description of a block kind, empty if none was declared
    public function getDescription(string $kind): string
    {
        if (!isset($this->descriptionCache[$kind])) {
            $block = $this->get($kind);
            $this->descriptionCache[$kind] = '' === $block['description']
                ? ''
                : $this->translator->trans($block['description'], [], $block['domain']);
        }

        return $this->descriptionCache[$kind];
    }

    // Gets the translated category of a block kind, using the same translation domain as its label
    public function getCategory(string $kind): string
    {
        if (!isset($this->categoryCache[$kind])) {
            $block = $this->get($kind);
            $this->categoryCache[$kind] = $this->translator->trans($block['category'], [], $block['domain']);
        }

        return $this->categoryCache[$kind];
    }

    public function get(string $kind): array
    {
        if (!isset($this->blocks[$kind])) {
            throw new \InvalidArgumentException("Unknown block: {$kind}");
        }

        return $this->blocks[$kind];
    }

    public function has(string $type): bool
    {
        return isset($this->blocks[$type]);
    }

    public function all(): array
    {
        return $this->blocks;
    }

    public function getFormClass(string $kind): string
    {
        return $this->get($kind)['form'];
    }

    public function getTemplate(string $kind): string
    {
        return $this->get($kind)['template'];
    }

    public function getMediaTypes(string $kind): array
    {
        return $this->get($kind)['mediaTypes'];
    }

    public function hasMediaTypes(string $kind): bool
    {
        return !empty($this->get($kind)['mediaTypes']);
    }

    // Result only depends on the static block registrations, cached after the first call
    public function groupedByCategory(): array
    {
        if (null !== $this->groupedCache) {
            return $this->groupedCache;
        }

        $grouped = [];
        foreach ($this->blocks as $kind => $config) {
            $grouped[$this->getCategory($kind)][$this->getChoiceLabel($kind)] = $kind;
        }

        ksort($grouped, SORT_FLAG_CASE | SORT_STRING);
        foreach (array_keys($grouped) as $category) {
            ksort($grouped[$category], SORT_FLAG_CASE | SORT_STRING);
        }

        return $this->groupedCache = $grouped;
    }

    // Builds the "kind" choice label: name, plus a short description in parentheses when declared.
    // Kept as plain text (no markup) so the "kind" field's <optgroup> categories stay intact -
    // EasyAdmin's ea-autocomplete widget only preserves grouping on a plain native <select>.
    private function getChoiceLabel(string $kind): string
    {
        $label = $this->getLabel($kind);
        $description = $this->getDescription($kind);

        if ('' === $description) {
            return $label;
        }

        return sprintf('%s (%s)', $label, $description);
    }
}