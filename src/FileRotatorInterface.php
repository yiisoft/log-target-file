<?php

namespace Yiisoft\Log\Target\File;

/**
 * Interface for FileRotator
 */
interface FileRotatorInterface
{
    /**
     * Rotates files.
     * @param string $file
     */
    public function rotateFile(string $file): void;
}
