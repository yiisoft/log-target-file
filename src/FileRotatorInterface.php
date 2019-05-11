<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Log;

/**
 * Interface for FileRotator
 */
interface FileRotatorInterface
{
    /**
     * Rotates log files.
     * @param string $file
     */
    public function rotateFiles(string $file): void;
}
