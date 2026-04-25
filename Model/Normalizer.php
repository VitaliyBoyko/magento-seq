<?php
declare(strict_types=1);

namespace VitaliiBoiko\Seq\Model;

use DateTimeInterface;
use JsonSerializable;
use Stringable;
use Throwable;

/**
 * Converts arbitrary PHP values into JSON-safe data structures for Seq payloads.
 */
class Normalizer
{
    private const MAX_DEPTH = 6;

    /**
     * Normalize a value recursively while guarding against deep or cyclic structures.
     */
    public function normalize(mixed $value, int $depth = 0): mixed
    {
        if ($depth >= self::MAX_DEPTH) {
            return '[depth-limit]';
        }

        if ($value === null || is_scalar($value)) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if ($value instanceof Throwable) {
            return [
                'class' => $value::class,
                'message' => $value->getMessage(),
                'code' => $value->getCode(),
                'file' => $value->getFile(),
                'line' => $value->getLine(),
                'trace' => $value->getTraceAsString(),
            ];
        }

        if ($value instanceof JsonSerializable) {
            return $this->normalize($value->jsonSerialize(), $depth + 1);
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[(string) $key] = $this->normalize($item, $depth + 1);
            }

            return $normalized;
        }

        if (is_object($value)) {
            return [
                'class' => $value::class,
                // Limit object serialization to visible properties to avoid side effects.
                'properties' => $this->normalize(get_object_vars($value), $depth + 1),
            ];
        }

        if (is_resource($value)) {
            return sprintf('resource(%s)', get_resource_type($value));
        }

        return (string) $value;
    }
}
