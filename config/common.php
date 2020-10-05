<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Log\Logger;
use Yiisoft\Log\Target\File\FileRotator;
use Yiisoft\Log\Target\File\FileRotatorInterface;
use Yiisoft\Log\Target\File\FileTarget;

/* @var $params array */

return [
    LoggerInterface::class => static fn(FileTarget $fileTarget) => new Logger(['file' => $fileTarget]),
    FileRotatorInterface::class => function () use ($params) {
        return new FileRotator(
            $params['yiisoft/log-target-file']['file-rotator']['maxFileSize'],
            $params['yiisoft/log-target-file']['file-rotator']['maxFiles'],
            $params['yiisoft/log-target-file']['file-rotator']['fileMode'],
            $params['yiisoft/log-target-file']['file-rotator']['rotateByCopy']
        );
    },
    FileTarget::class => function (Aliases $aliases, FileRotatorInterface $fileRotator) use ($params) {
        $fileTarget = new FileTarget(
            $aliases->get($params['yiisoft/log-target-file']['file-target']['file']),
            $fileRotator,
            $params['yiisoft/log-target-file']['file-target']['dirMode'],
            $params['yiisoft/log-target-file']['file-target']['fileMode']
        );

        $fileTarget->setLevels($params['yiisoft/log-target-file']['file-target']['levels']);

        return $fileTarget;
    },
];
