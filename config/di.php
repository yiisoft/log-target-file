<?php

declare(strict_types=1);

use Yiisoft\Aliases\Aliases;
use Yiisoft\Log\Target\File\FileRotator;
use Yiisoft\Log\Target\File\FileRotatorInterface;
use Yiisoft\Log\Target\File\FileTarget;

/* @var $params array */

return [
    FileRotatorInterface::class => [
        'class' => FileRotator::class,
        '__construct()' => [
            $params['yiisoft/log-target-file']['fileRotator']['maxFileSize'],
            $params['yiisoft/log-target-file']['fileRotator']['maxFiles'],
            $params['yiisoft/log-target-file']['fileRotator']['fileMode'],
            $params['yiisoft/log-target-file']['fileRotator']['compressRotatedFiles'],
        ],
    ],

    FileTarget::class => static function (Aliases $aliases, FileRotatorInterface $fileRotator) use ($params) {
        $fileTarget = new FileTarget(
            $aliases->get($params['yiisoft/log-target-file']['fileTarget']['file']),
            $fileRotator,
            $params['yiisoft/log-target-file']['fileTarget']['dirMode'],
            $params['yiisoft/log-target-file']['fileTarget']['fileMode'],
        );

        $fileTarget->setLevels($params['yiisoft/log-target-file']['fileTarget']['levels']);
        $fileTarget->setCategories($params['yiisoft/log-target-file']['fileTarget']['categories']);
        $fileTarget->setExcept($params['yiisoft/log-target-file']['fileTarget']['except']);
        $fileTarget->setExportInterval($params['yiisoft/log-target-file']['fileTarget']['exportInterval']);

        return $fileTarget;
    },
];
