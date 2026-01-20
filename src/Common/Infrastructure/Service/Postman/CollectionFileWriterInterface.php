<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Service\Postman;

/**
 * Collection File Writer Interface.
 *
 * Responsible for writing collection data to files.
 * Follows Single Responsibility Principle - only handles file I/O.
 */
interface CollectionFileWriterInterface
{
    /**
     * Writes collection data to file.
     *
     * @param string              $filePath File path to write to
     * @param array<string,mixed> $data     Collection data
     */
    public function write(string $filePath, array $data): void;
}
