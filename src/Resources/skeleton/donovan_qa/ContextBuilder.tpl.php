<?= "<?php\n" ?>
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace <?= $namespace ?>;

use c975L\UiBundle\Registry\BlockRegistry;

// Builds the LLM prompt context ("### kind\nLabel: ...\nDescription: ...", one section per registered
// block kind) so the model only ever cites real kinds instead of hallucinating one, and resolves the
// kinds an answer cited back into {label, url} source pairs for the API response
class <?= $class_name ?>
{
    public function __construct(
        private readonly BlockRegistry $blockRegistry,
    ) {
    }

    public function context(): string
    {
        $sections = [];
        foreach (array_keys($this->blockRegistry->all()) as $kind) {
            $label = $this->blockRegistry->getLabel($kind);
            $description = $this->blockRegistry->getDescription($kind);
            $sections[] = "### {$kind}\nLabel: {$label}\nDescription: {$description}";
        }

        return implode("\n\n", $sections);
    }

    // @param string[] $kinds
    // @return array{label: string, url: string}[]
    public function resolveSources(array $kinds): array
    {
        $sources = [];
        foreach ($kinds as $kind) {
            if (!$this->blockRegistry->has($kind)) {
                continue;
            }

            $sources[] = [
                'label' => $this->blockRegistry->getLabel($kind),
                // TODO: point this wherever your own block gallery/showcase lives, if you have one -
                // left empty on purpose rather than guessing at a URL scheme this bundle can't know
                'url' => '',
            ];
        }

        return $sources;
    }
}
