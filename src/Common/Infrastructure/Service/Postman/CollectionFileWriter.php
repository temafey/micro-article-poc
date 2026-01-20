<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Service\Postman;

/**
 * Collection File Writer - Writes collections to JSON files.
 */
final class CollectionFileWriter implements CollectionFileWriterInterface
{
    public function write(string $filePath, array $data): void
    {
        $directory = dirname($filePath);

        if (! is_dir($directory) && (! mkdir($directory, 0o755, true) && ! is_dir($directory))) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $directory));
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new \RuntimeException('Failed to encode collection to JSON');
        }

        if (file_put_contents($filePath, $json) === false) {
            throw new \RuntimeException(sprintf('Failed to write collection to %s', $filePath));
        }
    }
}
