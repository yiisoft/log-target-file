<?php

declare(strict_types=1);

namespace Yiisoft\Log\Target\File;

use InvalidArgumentException;
use RuntimeException;

use function chmod;
use function copy;
use function fclose;
use function file_exists;
use function filesize;
use function flock;
use function fopen;
use function ftruncate;
use function is_file;
use function rename;
use function sprintf;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const LOCK_EX;
use const LOCK_UN;

/**
 * FileRotator takes care of rotating files.
 *
 * If the size of the file exceeds {@see FileRotator::$maxFileSize} (in kilo-bytes),
 * a rotation will be performed, which renames the current file by suffixing the file name with '.1'.
 *
 * All existing files are moved backwards by one place, i.e., '.2' to '.3', '.1' to '.2', and so on.
 * The property {@see FileRotator::$maxFiles} specifies how many history files to keep.
 */
final class FileRotator implements FileRotatorInterface
{
    /**
     * @var int The maximum file size, in kilo-bytes. Defaults to 10240, meaning 10MB.
     */
    private int $maxFileSize;

    /**
     * @var int The number of files used for rotation. Defaults to 5.
     */
    private int $maxFiles;

    /**
     * @var int|null The permission to be set for newly created files.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * If not set, the permission will be determined by the current environment.
     */
    private ?int $fileMode;

    /**
     * @var bool|null Whether to rotate files by copy and truncate in contrast to rotation by
     * renaming files. Defaults to `true` to be more compatible with log tailers and is windows
     * systems which do not play well with rename on open files. Rotation by renaming however is
     * a bit faster.
     *
     * The problem with windows systems where the [rename()](http://www.php.net/manual/en/function.rename.php)
     * function does not work with files that are opened by some process is described in a
     * [comment by Martin Pelletier](http://www.php.net/manual/en/function.rename.php#102274) in
     * the PHP documentation. By setting rotateByCopy to `true` you can work
     * around this problem.
     */
    private ?bool $rotateByCopy;

    /**
     * @param int $maxFileSize The maximum file size, in kilo-bytes. Defaults to 10240, meaning 10MB.
     * @param int $maxFiles The number of files used for rotation. Defaults to 5.
     * @param int|null $fileMode The permission to be set for newly created files.
     * @param bool|null $rotateByCopy Whether to rotate files by copying and truncating or renaming them.
     */
    public function __construct(
        int $maxFileSize = 10240,
        int $maxFiles = 5,
        int $fileMode = null,
        bool $rotateByCopy = null
    ) {
        $this->checkCannotBeLowerThanOne($maxFileSize, '$maxFileSize');
        $this->checkCannotBeLowerThanOne($maxFiles, '$maxFiles');

        $this->maxFileSize = $maxFileSize;
        $this->maxFiles = $maxFiles;
        $this->fileMode = $fileMode;
        $this->rotateByCopy = $rotateByCopy ?? $this->isRunningOnWindows();
    }

    public function rotateFile(string $file): void
    {
        for ($i = $this->maxFiles; $i >= 0; --$i) {
            // `$i === 0` is the original file
            $rotateFile = $file . ($i === 0 ? '' : '.' . $i);
            if (is_file($rotateFile)) {
                // suppress errors because it's possible multiple processes enter into this section
                if ($i === $this->maxFiles) {
                    @unlink($rotateFile);
                    continue;
                }

                $newFile = $file . '.' . ($i + 1);
                $this->rotate($rotateFile, $newFile);

                if ($i === 0) {
                    $this->clear($rotateFile);
                }
            }
        }
    }

    public function isNeedRotateFile(string $file): bool
    {
        return file_exists($file) && @filesize($file) > ($this->maxFileSize * 1024);
    }

    /***
     * Copies or renames rotated file into new file.
     *
     * @param string $rotateFile
     * @param string $newFile
     */
    private function rotate(string $rotateFile, string $newFile): void
    {
        if (!$this->rotateByCopy) {
            rename($rotateFile, $newFile);
            return;
        }

        copy($rotateFile, $newFile);

        if ($this->fileMode !== null) {
            chmod($newFile, $this->fileMode);
        }
    }

    /***
     * Clears the file without closing any other process open handles.
     *
     * @param string $rotateFile Rotated file.
     *
     * @throws RuntimeException For the log file could not be opened.
     */
    private function clear(string $rotateFile): void
    {
        $filePointer = @fopen($rotateFile, 'ab');

        if ($filePointer === false) {
            throw new RuntimeException(sprintf(
                'The log file "%s" could not be opened.',
                $rotateFile,
            ));
        }

        flock($filePointer, LOCK_EX);
        ftruncate($filePointer, 0);
        flock($filePointer, LOCK_UN);
        fclose($filePointer);
    }

    /**
     * Whether it works on Windows OS.
     *
     * @return bool
     */
    private function isRunningOnWindows(): bool
    {
        return DIRECTORY_SEPARATOR === '\\';
    }

    /**
     * Checks that the value cannot be lower than one.
     *
     * @param int $value The value to be checked.
     * @param string $argumentName The name of the argument to check.
     */
    private function checkCannotBeLowerThanOne(int $value, string $argumentName): void
    {
        if ($value < 1) {
            throw new InvalidArgumentException(sprintf(
                'The argument "%s" cannot be lower than 1.',
                $argumentName,
            ));
        }
    }
}
