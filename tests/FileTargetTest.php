<?php

declare(strict_types=1);

namespace Yiisoft\Log\Target\File\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use RuntimeException;
use Yiisoft\Files\FileHelper;
use Yiisoft\Log\Message;
use Yiisoft\Log\Target\File\FileTarget;

use function dirname;
use function chmod;
use function file_get_contents;
use function file_put_contents;

final class FileTargetTest extends TestCase
{
    public function setUp(): void
    {
        FileHelper::removeDirectory(dirname($this->getLogFilePath()));
    }

    public function tearDown(): void
    {
        FileHelper::removeDirectory(dirname($this->getLogFilePath()));
    }

    public function testExportWithEmptyMessages(): void
    {
        $logFile = $this->getLogFilePath();
        $target = new FileTarget($logFile, null, 0777, 0777);
        $target->collect([], true);

        $this->assertFileDoesNotExist($logFile);
        $this->assertDirectoryDoesNotExist(dirname($logFile));
    }

    public function testExportOneMessageWithDefaultFormat(): void
    {
        $logFile = $this->getLogFilePath();
        $target = new FileTarget($logFile, null, 0777, 0777);
        $target->collect([new Message(LogLevel::INFO, 'text', ['category' => 'alert', 'time' => 123])], true);

        $expected = '1970-01-01 00:02:03.000000 [info][alert] text'
            . "\n\nMessage context:\n\ncategory: 'alert'\ntime: 123\n\n";

        $this->assertDirectoryExists(dirname($logFile));
        $this->assertFileExists($logFile);
        $this->assertSame($expected, file_get_contents($logFile));
    }

    public function testExportMessagesWithSetFormat(): void
    {
        $logFile = $this->getLogFilePath();
        $target = new FileTarget($logFile, null, 0777, 0777);
        $target->setFormat(
            fn(Message $message) => "[{$message->level()}][{$message->context('category')}] {$message->message()}"
        );
        $target->collect(
            [
                new Message(LogLevel::INFO, 'text-1', ['category' => 'category-1']),
                new Message(LogLevel::INFO, 'text-2', ['category' => 'category-2']),
                new Message(LogLevel::INFO, 'text-3', ['category' => 'category-3', 'time' => 123]),
            ],
            true
        );
        $expected = "[info][category-1] text-1\n[info][category-2] text-2\n[info][category-3] text-3\n";

        $this->assertDirectoryExists(dirname($logFile));
        $this->assertFileExists($logFile);
        $this->assertSame($expected, file_get_contents($logFile));
    }

    public function testExportThrowExceptionForNotWritableFile(): void
    {
        $logFile = $this->getLogFilePath();
        FileHelper::ensureDirectory(dirname($logFile), 0777);
        file_put_contents($logFile, '');
        chmod($logFile, 0444);
        $target = new FileTarget($logFile, null, 0777, 0777);

        $this->expectException(RuntimeException::class);
        $target->collect([new Message(LogLevel::INFO, 'text')], true);
    }

    private function getLogFilePath(): string
    {
        return __DIR__ . '/runtime/log/file-target-test.log';
    }
}
