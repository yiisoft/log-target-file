<?php
namespace Yiisoft\Log\Tests;

use Psr\Log\LogLevel;
use yii\helpers\FileHelper;
use Yiisoft\Log\FileRotator;
use Yiisoft\Log\FileTarget;
use Yiisoft\Log\Logger;

/**
 * @group log
 */
class FileTargetTest extends \PHPUnit\Framework\TestCase
{

    public function booleanDataProvider()
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
    public function testInit()
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
    public function testRotate($rotateByCopy)
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
