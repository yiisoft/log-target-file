<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Log;

/**
 * FileTarget records log messages in a file.
 *
 * The log file is specified via [[logFile]]. If the size of the log file exceeds
 * [[maxFileSize]] (in kilo-bytes), a rotation will be performed, which renames
 * the current log file by suffixing the file name with '.1'. All existing log
 * files are moved backwards by one place, i.e., '.2' to '.3', '.1' to '.2', and so on.
 * The property [[maxLogFiles]] specifies how many history files to keep.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 */
class FileTarget extends Target
{
    /**
     * @var string log file path. If not set, it will use the "/tmp/app.log" file.
     * The directory containing the log files will be automatically created if not existing.
     */
    private $logFile;
    /**
     * @var int the permission to be set for newly created log files.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * If not set, the permission will be determined by the current environment.
     */
    private $fileMode;
    /**
     * @var int the permission to be set for newly created directories.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * Defaults to 0775, meaning the directory is read-writable by owner and group,
     * but read-only for other users.
     */
    private $dirMode = 0775;

    /**
     * @var FileRotatorInterface
     */
    private $rotator;

    public function __construct(string $logFile = '/tmp/app.log', FileRotatorInterface $rotator = null)
    {
        $this->rotator = $rotator;

        $this->setLogFile($logFile);
    }

    public function getLogFile(): string
    {
        return $this->logFile;
    }

    public function setLogFile(string $path): self
    {
        $this->logFile = $path;

        return $this;
    }

    /**
     * Writes log messages to a file.
     * Starting from version 2.0.14, this method throws LogRuntimeException in case the log can not be exported.
     * @throws LogRuntimeException if unable to open or write complete log to file
     */
    public function export(): void
    {
        $logPath = dirname($this->logFile);
        self::createDirectory($logPath, $this->dirMode);

        $text = implode("\n", array_map([$this, 'formatMessage'], $this->getMessages())) . "\n";

        if (($fp = fopen($this->logFile, 'a')) === false) {
            throw new LogRuntimeException("Unable to append to log file: {$this->logFile}");
        }

        @flock($fp, LOCK_EX);
        if ($this->rotator !== null) {
            // clear stat cache to ensure getting the real current file size and not a cached one
            // this may result in rotating twice when cached file size is used on subsequent calls
            clearstatcache();
        }
        if ($this->rotator !== null && @filesize($this->logFile) > $this->rotator->getMaxFileSize() * 1024) {
            @flock($fp, LOCK_UN);
            @fclose($fp);
            $this->rotator->rotateFile($this->logFile);
            $writeResult = @file_put_contents($this->logFile, $text, FILE_APPEND | LOCK_EX);
            if ($writeResult === false) {
                $error = error_get_last();
                throw new LogRuntimeException("Unable to export log through file!: {$error['message']}");
            }
            $textSize = strlen($text);
            if ($writeResult < $textSize) {
                throw new LogRuntimeException("Unable to export whole log through file! Wrote $writeResult out of $textSize bytes.");
            }
        } else {
            $writeResult = @fwrite($fp, $text);
            if ($writeResult === false) {
                $error = error_get_last();
                throw new LogRuntimeException("Unable to export log through file!: {$error['message']}");
            }
            $textSize = strlen($text);
            if ($writeResult < $textSize) {
                throw new LogRuntimeException("Unable to export whole log through file! Wrote $writeResult out of $textSize bytes.");
            }
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }
        if ($this->fileMode !== null) {
            @chmod($this->logFile, $this->fileMode);
        }
    }

    /**
     * @return int
     */
    public function getFileMode(): int
    {
        return $this->fileMode;
    }

    /**
     * @param int $fileMode
     * @return FileTarget
     */
    public function setFileMode(int $fileMode): FileTarget
    {
        $this->fileMode = $fileMode;
        return $this;
    }

    /**
     * @return int
     */
    public function getDirMode(): int
    {
        return $this->dirMode;
    }

    /**
     * @param int $dirMode
     * @return FileTarget
     */
    public function setDirMode(int $dirMode): FileTarget
    {
        $this->dirMode = $dirMode;
        return $this;
    }

    /**
     * Creates a new directory.
     *
     * This method is similar to the PHP `mkdir()` function except that
     * it uses `chmod()` to set the permission of the created directory
     * in order to avoid the impact of the `umask` setting.
     *
     * @param string $path path of the directory to be created.
     * @param int $mode the permission to be set for the created directory.
     * @return bool whether the directory is created successfully
     * @throws LogRuntimeException if the directory could not be created (i.e. php error due to parallel changes)
     */
    public static function createDirectory($path, $mode = 0775): bool
    {
        if (is_dir($path)) {
            return true;
        }
        $parentDir = \dirname($path);
        // recurse if parent dir does not exist and we are not at the root of the file system.
        if ($parentDir !== $path && !is_dir($parentDir)) {
            static::createDirectory($parentDir, $mode);
        }
        try {
            if (!mkdir($path, $mode) && !is_dir($path)) {
                return false;
            }
        } catch (\Exception $e) {
            if (!is_dir($path)) {// https://github.com/yiisoft/yii2/issues/9288
                throw new LogRuntimeException("Failed to create directory \"$path\": " . $e->getMessage(), $e->getCode(), $e);
            }
        }
        try {
            return chmod($path, $mode);
        } catch (\Exception $e) {
            throw new LogRuntimeException("Failed to change permissions for directory \"$path\": " . $e->getMessage(), $e->getCode(), $e);
        }
    }

}
