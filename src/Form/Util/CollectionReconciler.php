<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Form\Util;

// Symfony's by_reference:false adder/remover collection diffing is unreliable once nested dynamic sub-forms are involved (see BlockType/PageCrudController PRE_SUBMIT listeners): it can fail to detect that an item was removed when other items remain. This reconciles a Doctrine collection against a submitted collection-of-arrays form field by ID instead, removing whatever is no longer present.
final class CollectionReconciler
{
    public function __construct()
    {
    }

    public static function pruneRemoved(iterable $existing, array $submittedEntries, callable $remove): void
    {
        $submittedIds = self::extractIds($submittedEntries);

        foreach ($existing as $item) {
            if (!in_array((string) $item->getId(), $submittedIds, true)) {
                $remove($item);
            }
        }
    }

    // Drops submitted entries that neither match a surviving item by ID nor look like a genuine new entry (per $isNewEntry) - call after pruneRemoved(), passing the now-pruned collection. Guards against a malformed/partial submission for a just-deleted entry (see BlockType for the concrete case): left in the submitted array, CollectionType's own diffing (allow_add) would treat it as a new item and bind it to null data instead of dropping it.
    public static function dropOrphaned(array $submittedEntries, iterable $survivors, callable $isNewEntry): array
    {
        $survivingIds = [];
        foreach ($survivors as $item) {
            $survivingIds[] = (string) $item->getId();
        }

        return array_filter($submittedEntries, static function (mixed $entry) use ($survivingIds, $isNewEntry): bool {
            if (!is_array($entry)) {
                return false;
            }

            $id = $entry['id'] ?? null;
            if (null !== $id && '' !== $id) {
                return in_array((string) $id, $survivingIds, true);
            }

            return $isNewEntry($entry);
        });
    }

    // Pulls the "id" of each submitted entry, dropping entries that aren't arrays and IDs that are null/empty
    private static function extractIds(array $submittedEntries): array
    {
        return array_filter(
            array_map(
                static fn (mixed $entry): ?string => is_array($entry) ? ($entry['id'] ?? null) : null,
                $submittedEntries
            ),
            static fn (?string $id): bool => null !== $id && '' !== $id
        );
    }
}
