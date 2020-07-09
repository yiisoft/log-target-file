<?php

declare(strict_types=1);

namespace Yiisoft\Log\Target\File\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Files\FileHelper;
use Yiisoft\Log\Target\File\FileTarget;

/**
 * @group log
 */
final class FileTargetTest extends TestCase
{
    /**
     * Tests that log directory isn't created during init process
     *
     * @see https://github.com/yiisoft/yii2/issues/15662
     */
    public function testInit(): void
    {
        FileHelper::removeDirectory(dirname($this->getLogFilePath()));
        $logFile = $this->getLogFilePath();
        new FileTarget($logFile);
        self::assertFileDoesNotExist(
            dirname($logFile),
            'Log directory should not be created during init process'
        );
    }

    public function testExportEmptyFile(): void
    {
        FileHelper::removeDirectory(dirname($this->getLogFilePath()));

        $logFile = $this->getLogFilePath();
        $target = new FileTarget($logFile, null, 0777, 0777);

        $target->export();

        self::assertDirectoryExists(dirname($logFile));
        self::assertFileExists($logFile);
        self::assertEquals("\n", file_get_contents($logFile));
    }

    public function testExportMessages(): void
    {
        FileHelper::removeDirectory(dirname($this->getLogFilePath()));

        $logFile = $this->getLogFilePath();
        $target = new FileTarget($logFile, null, 0777, 0777);
        $target->setMessages([
            ['level', 'text', ['category' => 'alert', 'time' => 123]]
        ]);

        $target->export();

        self::assertDirectoryExists(dirname($logFile));
        self::assertFileExists($logFile);
        self::assertEquals("1970-01-01 00:02:03.000000 [level][alert] text\n", file_get_contents($logFile));
    }

    private function getLogFilePath(): string
    {
        return __DIR__ . '/runtime/log/file-target-test.log';
    }
}
