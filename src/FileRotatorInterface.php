<?php

declare(strict_types=1);

namespace Yiisoft\Log\Target\File;

/**
 * Interface for FileRotator
 */
interface FileRotatorInterface
{
    /**
     * Rotates files.
     *
     * @param string $file
     */
    public function rotateFile(string $file): void;

    /**
     * Get maximum file size, in kilo-bytes.
     *
     * @return int
     */
    public function getMaxFileSize(): int;
}
