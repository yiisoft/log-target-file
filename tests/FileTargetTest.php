<?php

namespace Yiisoft\Log\Target\File\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Yiisoft\Files\FileHelper;
use Yiisoft\Log\Target\File\FileRotator;
use Yiisoft\Log\Target\File\FileTarget;
use Yiisoft\Log\Logger;

/**
 * @group log
 */
final class FileTargetTest extends TestCase
{
    public function booleanDataProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * Tests that log directory isn't created during init process
     * @see https://github.com/yiisoft/yii2/issues/15662
     */
    public function testInit(): void
    {
        $logFile = __DIR__ . '/runtime/log/filetargettest.log';
        FileHelper::removeDirectory(dirname($logFile));
        new FileTarget($logFile);
        $this->assertFileNotExists(
            dirname($logFile),
            'Log directory should not be created during init process'
        );
    }

    /**
     * @dataProvider booleanDataProvider
     * @param bool $rotateByCopy
     */
    public function testRotate(bool $rotateByCopy): void
    {
        $logFile = __DIR__ . '/runtime/log/filetargettest.log';
        FileHelper::removeDirectory(dirname($logFile));
        mkdir(dirname($logFile), 0777, true);

        $rotator = new FileRotator(1024, 1, null, $rotateByCopy);
        $fileTarget = new FileTarget($logFile, $rotator);

        $logger = new Logger([
            'file' => $fileTarget,
        ]);

        // one file

        $logger->log(LogLevel::WARNING, str_repeat('x', 1024));
        $logger->flush(true);

        clearstatcache();

        $this->assertFileExists($logFile);
        $this->assertFileNotExists($logFile . '.1');
        $this->assertFileNotExists($logFile . '.2');
        $this->assertFileNotExists($logFile . '.3');
        $this->assertFileNotExists($logFile . '.4');

        // exceed max size
        for ($i = 0; $i < 1024; $i++) {
            $logger->log(LogLevel::WARNING, str_repeat('x', 1024));
        }
        $logger->flush(true);

        // first rotate

        $logger->log(LogLevel::WARNING, str_repeat('x', 1024));
        $logger->flush(true);

        clearstatcache();

        $this->assertFileExists($logFile);
        $this->assertFileExists($logFile . '.1');
        $this->assertFileNotExists($logFile . '.2');
        $this->assertFileNotExists($logFile . '.3');
        $this->assertFileNotExists($logFile . '.4');

        // second rotate

        for ($i = 0; $i < 1024; $i++) {
            $logger->log(LogLevel::WARNING, str_repeat('x', 1024));
        }
        $logger->flush(true);

        clearstatcache();

        $this->assertFileExists($logFile);
        $this->assertFileExists($logFile . '.1');
        $this->assertFileNotExists($logFile . '.2');
        $this->assertFileNotExists($logFile . '.3');
        $this->assertFileNotExists($logFile . '.4');
    }
}
