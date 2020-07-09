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
     * Gets the value of maxFileSize.
     *
     * @return int
     */
    public function getMaxFileSize(): int;
}
