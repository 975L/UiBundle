<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace c975L\UiBundle\Doctrine;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

// Maps a PHP float[] to MariaDB's native VECTOR(n) column (11.7+) - not registered/used by anything in
// this bundle itself, a shared building block for any consuming app storing embeddings (see Readme "AI
// Assistant" > "Self-hosting your own backend" for the semantic-cache pattern this exists for, and
// c975l:ui:donovan-qa:create's generated AnswerRepository for a real usage). A consuming app registers it
// itself (doctrine.yaml: dbal.types.vector + dbal.mapping_types.vector, both to "vector") - this bundle
// never touches that file, same "print a snippet, don't own the app's config" reasoning as elsewhere here.
// Storage is the raw little-endian float32 bytes MariaDB's VECTOR type expects internally - the exact
// same bytes VEC_FromText()/VEC_ToText() convert to/from, confirmed empirically: pack('g*', ...) round-
// trips through a VECTOR column with no SQL-side wrapping needed
class VectorType extends Type
{
    public const NAME = 'vector';

    // Doctrine's own type registry instantiates custom types with "new $class()", no constructor
    // arguments possible - matches Qwen3-Embedding-8B's output size (confirmed empirically), the model
    // c975l:ui:donovan-qa:create's generated EmbeddingClient defaults to. A different embedding model
    // with a different output size needs its own subclass overriding this constant
    public const DIMENSIONS = 4096;

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'VECTOR(' . self::DIMENSIONS . ')';
    }

    /**
     * @return float[]|null
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?array
    {
        return null === $value ? null : self::unpack($value);
    }

    /**
     * @param float[]|null $value
     */
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return null === $value ? null : self::pack($value);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    // No comment hint needed: unlike most custom types, "vector" is registered as a real native SQL type
    // name (see the consuming app's own dbal.mapping_types), so the schema comparator already recognizes
    // a VECTOR(n) column as this type from introspection alone
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return false;
    }

    // The packed bytes are arbitrary binary, not valid UTF-8 text - binding as the default STRING type
    // lets the client charset conversion mangle them (confirmed: MariaDB then rejects the value as an
    // "Incorrect vector value"), BINARY sends the bytes through untouched
    public function getBindingType(): ParameterType
    {
        return ParameterType::BINARY;
    }

    // Exposed statically so a repository can pack a query vector the same way for a raw
    // VEC_DISTANCE_COSINE() SQL query (which Doctrine's ORM/DQL has no built-in knowledge of)
    /** @param float[] $floats */
    public static function pack(array $floats): string
    {
        return pack('g*', ...$floats);
    }

    /** @return float[] */
    public static function unpack(string $bytes): array
    {
        return array_values(unpack('g*', $bytes));
    }
}
