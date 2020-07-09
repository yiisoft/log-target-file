<?php

declare(strict_types=1);

namespace Yiisoft\Log\Target\File\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Files\FileHelper;
use Yiisoft\Log\Logger;
use Yiisoft\Log\Target\File\FileRotator;
use Yiisoft\Log\Target\File\FileTarget;

final class FileRotatorTest extends TestCase
{
    /**
     * @dataProvider booleanDataProvider()
     * @param bool $rotateByCopy
     */
    public function testRotateByCopy(bool $rotateByCopy): void
    {
        $logFile = $this->getLogFilePath();
        $rotator = new FileRotator(1, 2, 777, $rotateByCopy);
        $fileTarget = new FileTarget($logFile, $rotator);

        $logger = new Logger([
            'file' => $fileTarget,
        ]);

        $logger->debug(str_repeat('x', 1024));
        $logger->flush(true);
        self::assertFileExists($logFile);
        self::assertFileDoesNotExist($logFile . '.1');

        $nonRotatedFileContent = file_get_contents($logFile);

        $logger->debug("\0");
        $logger->flush(true);

        self::assertFileExists($logFile);
        self::assertFileExists($logFile . '.1');

        self::assertEquals($nonRotatedFileContent, file_get_contents($logFile . '.1'));
    }

    /**
     * @dataProvider filesCountProvider()
     * @param int $filesCount
     */
    public function testRotateMaxFiles(int $filesCount): void
    {
        $logFile = $this->getLogFilePath();
        $rotator = new FileRotator(1, $filesCount, null, true);
        $fileTarget = new FileTarget($logFile, $rotator);

        $logger = new Logger([
            'file' => $fileTarget,
        ]);

        foreach (range(1, $filesCount+2) as $i) {
            /** @noinspection DisconnectedForeachInstructionInspection */
            $logger->debug(str_repeat('x', 1024));
            /** @noinspection DisconnectedForeachInstructionInspection */
            $logger->flush(true);
        }

        self::assertFileExists($logFile);

        foreach (range(1, $filesCount) as $counter) {
            $filesName = $counter !== 1 ? $logFile . '.' . ($counter - 1) : $logFile;
            self::assertFileExists($filesName);
        }

        // check that next file is not exist
        $filesCount++;
        self::assertFileDoesNotExist("{$logFile}.{$filesCount}");
    }

    /**
     * @dataProvider filesSizesProvider()
     * @param int $maxFileSize
     */
    public function testRotateMaxFileSize(int $maxFileSize): void
    {
        $logFile = $this->getLogFilePath();
        $rotator = new FileRotator($maxFileSize, 2, null, true);
        $fileTarget = new FileTarget($logFile, $rotator);

        $logger = new Logger([
            'file' => $fileTarget,
        ]);

        $logger->debug(str_repeat('x', $maxFileSize * 1024));
        $logger->flush(true);

        clearstatcache();
        self::assertFileExists($logFile);
        self::assertGreaterThan($maxFileSize, filesize($logFile) / 1024);
    }

    public function testDefaultMaxFileSize(): void
    {
        $rotator = new FileRotator();
        self::assertEquals(10240, $rotator->getMaxFileSize());
    }

    public function testMaxFileSizeLowerThanOne(): void
    {
        $rotator = new FileRotator(-1);
        self::assertEquals(1, $rotator->getMaxFileSize());
    }

    public function testSetMaxFileSizeLowerThanOne(): void
    {
        $rotator = new FileRotator();
        $rotator->setMaxFileSize(-1);
        self::assertEquals(1, $rotator->getMaxFileSize());
    }

    public function testDefaultMaxFiles(): void
    {
        $rotator = new FileRotator();
        self::assertEquals(5, $rotator->getMaxFiles());
    }

    public function testMaxFilesLowerThanOne(): void
    {
        $rotator = new FileRotator(0, -1);
        self::assertEquals(1, $rotator->getMaxFiles());
    }

    public function testSetMaxFilesLowerThanOne(): void
    {
        $rotator = new FileRotator();
        $rotator->setMaxFiles(-1);
        self::assertEquals(1, $rotator->getMaxFiles());
    }

    public function booleanDataProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }

    public function filesCountProvider(): array
    {
        return [
            [1],
            [2],
            [3],
            [4],
            [10],
        ];
    }

    public function filesSizesProvider(): array
    {
        return [
            [2],
            [5],
            [10],
            [20],
            [100],
        ];
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
}
