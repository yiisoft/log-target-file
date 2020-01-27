<?php

namespace Yiisoft\Log\Target\File;

/**
 * FileRotator takes care of rotating files.
 *
 * If the size of the file exceeds [[maxFileSize]] (in kilo-bytes), a rotation will be performed, which renames
 * the current file by suffixing the file name with '.1'.
 *
 * All existing files are moved backwards by one place, i.e., '.2' to '.3', '.1' to '.2', and so on.
 * The property [[maxFiles]] specifies how many history files to keep.
 */
class FileRotator implements FileRotatorInterface
{
    /**
     * @var int maximum file size, in kilo-bytes. Defaults to 10240, meaning 10MB.
     */
    private $maxFileSize;
    /**
     * @var int number of files used for rotation. Defaults to 5.
     */
    private $maxFiles;
    /**
     * @var int the permission to be set for newly created files.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * If not set, the permission will be determined by the current environment.
     */
    private $fileMode;
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
    private $rotateByCopy;

    public function __construct(int $maxFileSize = 10240, int $maxFiles = 5, int $fileMode = null, bool $rotateByCopy = null)
    {
        $this->maxFileSize = $maxFileSize;
        $this->maxFiles = $maxFiles;
        $this->fileMode = $fileMode;

        $this->rotateByCopy = $rotateByCopy ?? $this->isRunningOnWindows();
    }

    /**
     * Sets the value of maxFileSize.
     * @param int $maxFileSize
     * @return FileRotator
     */
    public function setMaxFileSize(int $maxFileSize): self
    {
        $this->maxFileSize = $maxFileSize;
        if ($this->maxFileSize < 1) {
            $this->maxFileSize = 1;
        }

        return $this;
    }

    /**
     * Gets the value of maxFileSize.
     * @return int
     */
    public function getMaxFileSize(): int
    {
        return $this->maxFileSize;
    }


    /**
     * Sets the value of maxFiles.
     * @param int $maxFiles
     */
    public function setMaxFiles(int $maxFiles): self
    {
        $this->maxFiles = $maxFiles;
        if ($this->maxFiles < 1) {
            $this->maxFiles = 1;
        }

        return $this;
    }

    /**
     * Gets the value of maxFiles.
     * @return int
     */
    public function getMaxFiles(): int
    {
        return $this->maxFiles;
    }


    /**
     * @inheritDoc
     */
    public function rotateFile(string $file): void
    {
        for ($i = $this->maxFiles; $i >= 0; --$i) {
            // $i == 0 is the original file
            $rotateFile = $file . ($i === 0 ? '' : '.' . $i);
            if (is_file($rotateFile)) {
                // suppress errors because it's possible multiple processes enter into this section
                if ($i === $this->maxFiles) {
                    @unlink($rotateFile);
                    continue;
                }
                $newFile = $file . '.' . ($i + 1);
                $this->rotateByCopy ? $this->rotateByCopy($rotateFile, $newFile) : $this->rotateByRename($rotateFile, $newFile);
                if ($i === 0) {
                    $this->clearFile($rotateFile);
                }
            }
        }
    }

    /***
     * Clears the file without closing any other process open handles
     * @param string $rotateFile
     */
    private function clearFile(string $rotateFile): void
    {
        if ($filePointer = @fopen($rotateFile, 'a')) {
            @ftruncate($filePointer, 0);
            @fclose($filePointer);
        }
    }

    /***
     * Copy rotated file into new file
     * @param string $rotateFile
     * @param string $newFile
     * @param int|null $fileMode
     */
    private function rotateByCopy(string $rotateFile, string $newFile): void
    {
        @copy($rotateFile, $newFile);
        if ($this->fileMode !== null) {
            @chmod($newFile, $this->fileMode);
        }
    }

    /**
     * Renames rotated file into new file
     * @param string $rotateFile
     * @param string $newFile
     */
    private function rotateByRename(string $rotateFile, string $newFile): void
    {
        @rename($rotateFile, $newFile);
    }

    private function isRunningOnWindows(): bool
    {
        return DIRECTORY_SEPARATOR === '\\';
    }
}
