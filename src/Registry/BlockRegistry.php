<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Registry;

// Check readme for use
class BlockRegistry
{
    private array $blocks = [];

    public function register(
        string $kind,
        string $label,
        string $formClass,
        string $template,
        string $icon = '📦',
        string $category = 'Général',
        array $mediaTypes = []
    ): void {
        $this->blocks[$kind] = [
            'label'      => $label,
            'form'       => $formClass,
            'template'   => $template,
            'icon'       => $icon,
            'category'   => $category,
            'mediaTypes' => $mediaTypes,
        ];
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

    public function groupedByCategory(): array
    {
        $grouped = [];
        foreach ($this->blocks as $kind => $config) {
            $grouped[$config['category']][$config['label']] = $kind;
        }

        return $grouped;
    }
}