<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

/**
 * Base class for all resource DTOs.
 *
 * Provides common functionality for API response objects.
 */
abstract readonly class Resource implements \JsonSerializable
{
    /**
     * Create a resource from an API response array.
     *
     * @param array<string, mixed> $data
     */
    abstract public static function fromArray(array $data): static;

    /**
     * Serialize for JSON encoding.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $properties = [];

        foreach ((new \ReflectionClass($this))->getProperties() as $property) {
            $name = $property->getName();
            $value = $property->getValue($this);

            if ($value instanceof \JsonSerializable) {
                $value = $value->jsonSerialize();
            } elseif ($value instanceof \DateTimeInterface) {
                $value = $value->format(\DateTimeInterface::ATOM);
            } elseif ($value instanceof \BackedEnum) {
                $value = $value->value;
            } elseif (\is_array($value)) {
                $value = array_map(static function ($item) {
                    if ($item instanceof \JsonSerializable) {
                        return $item->jsonSerialize();
                    }
                    if ($item instanceof \DateTimeInterface) {
                        return $item->format(\DateTimeInterface::ATOM);
                    }
                    if ($item instanceof \BackedEnum) {
                        return $item->value;
                    }

                    return $item;
                }, $value);
            }

            $properties[$name] = $value;
        }

        return $properties;
    }

    /**
     * Parse an optional datetime string.
     */
    protected static function parseDateTime(?string $value): ?\DateTimeImmutable
    {
        if (null === $value || '' === $value) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Parse an optional integer.
     */
    protected static function parseInt(mixed $value, int $default = 0): int
    {
        if (null === $value) {
            return $default;
        }

        return (int) $value;
    }

    /**
     * Parse an optional boolean.
     */
    protected static function parseBool(mixed $value, bool $default = false): bool
    {
        if (null === $value) {
            return $default;
        }

        return (bool) $value;
    }
}
