<?php

use Yiisoft\Factory\Definitions\Reference;
use Yiisoft\Log\FileRotator;
use Yiisoft\Log\FileRotatorInterface;

return [
    FileRotatorInterface::class => Reference::to('file-rotator'),
    'file-rotator' => [
        '__class' => FileRotator::class,
    ],
];