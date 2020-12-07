<?php

declare(strict_types=1);

namespace Yiisoft\Log\Target\File\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Files\FileHelper;
use Yiisoft\Log\Logger;
use Yiisoft\Log\Target\File\FileRotator;
use Yiisoft\Log\Target\File\FileTarget;

final class FileRotatorTest extends TestCase
{
    public function testRotateByCopy(): void
    {
        $rotator = new FileRotator(1, 2, 0777, true);
        $this->innerTestRotate($rotator);
    }

    public function testRotateByRename(): void
    {
        $rotator = new FileRotator(1, 2, 0777, false);
        $this->innerTestRotate($rotator);
    }

    public function testRotateMaxFiles(): void
    {
        $filesCount = 3;
        $logFile = $this->getLogFilePath();
        $rotator = new FileRotator(1, $filesCount, null, true);
        $fileTarget = new FileTarget($logFile, $rotator);

        $logger = new Logger([
            'file' => $fileTarget,
        ]);

        $i = 0;
        while ($i <= $filesCount + 2) {
            $logger->debug($this->generateKilobytesOfData(1));
            $logger->flush(true);
            $i++;
        }

        self::assertFileExists($logFile);

        foreach (range(1, $filesCount) as $counter) {
            $filesName = $counter !== 1 ? $logFile . '.' . ($counter - 1) : $logFile;
            self::assertFileExists($filesName);
        }

        // check that next file does not exist
        $filesCount++;
        self::assertFileDoesNotExist("{$logFile}.{$filesCount}");
    }

    public function testRotateMaxFileSize(): void
    {
        $maxFileSize = 10;
        $logFile = $this->getLogFilePath();
        $rotator = new FileRotator($maxFileSize, 2, null, true);
        $fileTarget = new FileTarget($logFile, $rotator);

        $logger = new Logger([
            'file' => $fileTarget,
        ]);

        $logger->debug($this->generateKilobytesOfData($maxFileSize));
        $logger->flush(true);

        clearstatcache();
        self::assertFileExists($logFile);
        self::assertGreaterThan($maxFileSize, filesize($logFile) / 1024);
    }

    public function testDefaultMaxFileSize(): void
    {
        $rotator = new FileRotator();
        $this->assertFalse($rotator->isNeedRotateFile('not-found-file'));
    }

    public function testMaxFileSizeLowerThanOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FileRotator(0);
    }

    public function testMaxFilesLowerThanOne(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new FileRotator(1, 0);
    }

    protected function setUp(): void
    {
        FileHelper::removeDirectory(dirname($this->getLogFilePath()));
        mkdir(dirname($this->getLogFilePath()), 0777, true);
    }

    protected function tearDown(): void
    {
        FileHelper::removeDirectory(dirname($this->getLogFilePath()));
    }

    private function getLogFilePath(): string
    {
        return __DIR__ . '/runtime/log/file-target-test.log';
    }

    private function generateKilobytesOfData(int $count): string
    {
        return str_repeat('x', $count * 1024);
    }

    private function innerTestRotate(FileRotator $rotator): void
    {
        $logFile = $this->getLogFilePath();
        $fileTarget = new FileTarget($logFile, $rotator);

        $logger = new Logger([
            'file' => $fileTarget,
        ]);

        $logger->debug($this->generateKilobytesOfData(1));
        $logger->flush(true);
        self::assertFileExists($logFile);
        self::assertFileDoesNotExist($logFile . '.1');

        $nonRotatedFileContent = file_get_contents($logFile);

        $logger->debug('x');
        $logger->flush(true);

        self::assertFileExists($logFile);
        self::assertFileExists($logFile . '.1');

        self::assertEquals($nonRotatedFileContent, file_get_contents($logFile . '.1'));
    }
}
