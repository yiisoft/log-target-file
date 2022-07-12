<?php

declare(strict_types=1);

namespace Yiisoft\Log\Target\File\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Files\FileHelper;
use Yiisoft\Log\Logger;
use Yiisoft\Log\Target\File\FileRotator;
use Yiisoft\Log\Target\File\FileTarget;

use function clearstatcache;
use function dirname;
use function file_get_contents;
use function filesize;
use function mkdir;
use function range;
use function str_repeat;

final class FileRotatorTest extends TestCase
{
    public function setUp(): void
    {
        FileHelper::removeDirectory(dirname($this->getLogFilePath()));
        mkdir(dirname($this->getLogFilePath()), 0777, true);
    }

    public function tearDown(): void
    {
        FileHelper::removeDirectory(dirname($this->getLogFilePath()));
    }

    public function rotateDataProvider(): array
    {
        return [
            'compress-true' => [true],
            'compress-false' => [false],
        ];
    }

    /**
     * @dataProvider rotateDataProvider
     *
     * @param bool $compress
     */
    public function testRotate(bool $compress): void
    {
        $rotator = new FileRotator(1, 2, 0644, $compress);
        $logFile = $this->getLogFilePath();
        $fileTarget = new FileTarget($logFile, $rotator);
        $logger = new Logger([$fileTarget]);
        $compressExtension = $compress ? '.gz' : '';

        $logger->debug($this->generateKilobytesOfData(1));
        $logger->flush(true);
        $this->assertFileExists($logFile);
        $this->assertFileDoesNotExist("{$logFile}.1{$compressExtension}");

        $nonRotatedFileContent = file_get_contents($logFile);
        $logger->debug('x');
        $logger->flush(true);

        $this->assertFileExists($logFile);
        $this->assertFileExists("{$logFile}.1{$compressExtension}");

        if ($compress) {
            $this->assertFileDoesNotExist($logFile . '.1');
        } else {
            $this->assertSame($nonRotatedFileContent, file_get_contents($logFile . '.1'));
            $this->assertFileDoesNotExist($logFile . '.1.gz');
        }
    }

    /**
     * @dataProvider rotateDataProvider
     *
     * @param bool $compress
     */
    public function testRotateMaxFiles(bool $compress): void
    {
        $filesCount = 3;
        $logFile = $this->getLogFilePath();
        $rotator = new FileRotator(1, $filesCount, null, $compress);
        $fileTarget = new FileTarget($logFile, $rotator);
        $logger = new Logger([$fileTarget]);
        $compressExtension = $compress ? '.gz' : '';

        $i = 0;
        while ($i <= $filesCount + 2) {
            $logger->debug($this->generateKilobytesOfData(1));
            $logger->flush(true);
            $i++;
        }

        $this->assertFileExists($logFile);

        foreach (range(1, $filesCount) as $counter) {
            $filesName = $counter !== 1 ? $logFile . '.' . ($counter - 1) . $compressExtension : $logFile;
            $this->assertFileExists($filesName);
        }

        // check that next file does not exist
        $filesCount++;
        $this->assertFileDoesNotExist("{$logFile}.{$filesCount}{$compressExtension}");
    }

    /**
     * @dataProvider rotateDataProvider
     *
     * @param bool $compress
     */
    public function testRotateMaxFileSize(bool $compress): void
    {
        $maxFileSize = 10;
        $logFile = $this->getLogFilePath();
        $rotator = new FileRotator($maxFileSize, 2, null, $compress);
        $fileTarget = new FileTarget($logFile, $rotator);
        $logger = new Logger([$fileTarget]);

        $logger->debug($this->generateKilobytesOfData($maxFileSize));
        $logger->flush(true);

        clearstatcache();
        $this->assertFileExists($logFile);
        $this->assertTrue($rotator->shouldRotateFile($logFile));
        $this->assertGreaterThan($maxFileSize, filesize($logFile) / 1024);
    }

    public function testIsShouldRotateFileWithDefaultMaxFileSize(): void
    {
        $rotator = new FileRotator();
        $this->assertFalse($rotator->shouldRotateFile('not-found-file'));
        $this->assertFalse($rotator->shouldRotateFile($this->getLogFilePath()));
    }

    public function testMaxFileSizeThrowExceptionForLowerThanOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FileRotator(0);
    }

    public function testMaxFilesThrowExceptionForLowerThanOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FileRotator(1, 0);
    }

    private function getLogFilePath(): string
    {
        return __DIR__ . '/runtime/log/file-target-test.log';
    }

    private function generateKilobytesOfData(int $count): string
    {
        return str_repeat('x', $count * 1024);
    }
}
