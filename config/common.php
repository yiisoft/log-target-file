<?php

return [
    \Yiisoft\Log\FileRotatorInterface::class => \yii\di\Reference::to('file-rotator'),
    'file-rotator' => [
        '__class' => \Yiisoft\Log\FileRotator::class,
    ],
];