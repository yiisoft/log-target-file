<?php

declare(strict_types=1);

namespace Yiisoft\Log\Target\File;

use Yiisoft\Files\FileHelper;
use Yiisoft\Log\LogRuntimeException;
use Yiisoft\Log\Target;

/**
 * FileTarget records log messages in a file.
 *
 * The log file is specified via {@see logFile}. If the size of the log file exceeds
 * {@see maxFileSize} (in kilo-bytes), a rotation will be performed, which renames
 * the current log file by suffixing the file name with '.1'. All existing log
 * files are moved backwards by one place, i.e., '.2' to '.3', '.1' to '.2', and so on.
 * The property {@see maxLogFiles} specifies how many history files to keep.
 */
class FileTarget extends Target
{
    /**
     * @var string log file path. If not set, it will use the "/tmp/app.log" file.
     * The directory containing the log files will be automatically created if not existing.
     */
    private string $logFile;
    /**
     * @var int|null the permission to be set for newly created log files.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * If not set, the permission will be determined by the current environment.
     */
    private ?int $fileMode;
    /**
     * @var int the permission to be set for newly created directories.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * Defaults to 0775, meaning the directory is read-writable by owner and group,
     * but read-only for other users.
     */
    private int $dirMode;

    private ?FileRotatorInterface $rotator;

    public function __construct(
        string $logFile = '/tmp/app.log',
        FileRotatorInterface $rotator = null,
        int $dirMode = 0775,
        int $fileMode = null
    ) {
        $this->logFile = $logFile;
        $this->rotator = $rotator;
        $this->dirMode = $dirMode;
        $this->fileMode = $fileMode;
        parent::__construct();
    }

    /**
     * Writes log messages to a file.
     *
     * @throws LogRuntimeException if unable to open or write complete log to file
     */
    public function export(): void
    {
        $logPath = dirname($this->logFile);

        if (!file_exists($logPath)) {
            FileHelper::createDirectory($logPath, $this->dirMode);
        }

        $text = $this->formatMessages("\n");

        if (($fp = fopen($this->logFile, 'ab')) === false) {
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
            $this->checkWrittenResult($writeResult, $text);
        } else {
            $writeResult = @fwrite($fp, $text);
            $this->checkWrittenResult($writeResult, $text);
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }
        if ($this->fileMode !== null) {
            @chmod($this->logFile, $this->fileMode);
        }
    }

    private function checkWrittenResult($writeResult, string $text): void
    {
        if ($writeResult === false) {
            $error = error_get_last();
            throw new LogRuntimeException("Unable to export log through file: {$error['message']}");
        }
        $textSize = strlen($text);
        if ($writeResult < $textSize) {
            throw new LogRuntimeException("Unable to export whole log through file! Wrote $writeResult out of $textSize bytes.");
        }
    }
}
