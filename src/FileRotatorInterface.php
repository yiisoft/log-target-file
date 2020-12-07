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
     * @param string $file The log file for rotation.
     */
    public function rotateFile(string $file): void;

    /**
     * Checks whether the file needs to be rotated.
     *
     * @param string $file The log file for rotation.
     *
     * @return bool Whether you need to rotate the file.
     */
    public function isNeedRotateFile(string $file): bool;
}
