<?php

declare(strict_types=1);

namespace Micro\Article\Domain\ValueObject;

use MicroModule\Base\Domain\ValueObject\Uuid;
use MicroModule\ValueObject\Exception\InvalidNativeArgumentException;
use MicroModule\ValueObject\ValueObjectInterface;

/**
 * @class Id
 *
 * Article domain identifier ValueObject.
 * Wraps UUID with domain-specific validation and semantics.
 */
final class Id extends Uuid
{
    /**
     * Constructor with validation.
     *
     * @param string $value UUID v4 string
     */
    public function __construct(string $value)
    {
        $this->validate($value);
        parent::__construct($value);
    }

    /**
     * Validate the value according to business rules.
     */
    public function validate($value): void
    {
        if ($value instanceof ValueObjectInterface) {
            $value = $value->toNative();
        }

        if (empty($value)) {
            throw new InvalidNativeArgumentException($value, ['Article ID cannot be empty']);
        }

        if (! is_string($value)) {
            throw new InvalidNativeArgumentException($value, ['Article ID must be a valid UUID string']);
        }

        // UUID v4 format validation: 8-4-4-4-12 hexadecimal characters
        $uuidPattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

        if (! preg_match($uuidPattern, $value)) {
            throw new InvalidNativeArgumentException($value, ['Article ID must be a valid UUID v4 format']);
        }
    }

    /**
     * Generate a new random UUID v4 for Article ID.
     */
    public static function generate(): static
    {
        $data = random_bytes(16);

        // Set version (4) and variant (RFC 4122)
        $data[6] = chr(ord($data[6]) & 0x0F | 0x40); // Version 4
        $data[8] = chr(ord($data[8]) & 0x3F | 0x80); // Variant RFC 4122

        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

        return new self($uuid);
    }
}
