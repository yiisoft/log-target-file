<?php

declare(strict_types=1);

namespace Yiisoft\Log\Target\File\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Log\Target\File\FileRotator;
use Yiisoft\Log\Target\File\FileRotatorInterface;
use Yiisoft\Log\Target\File\FileTarget;

final class ConfigTest extends TestCase
{
    public function testBase(): void
    {
        $container = $this->createContainer();

        $fileRotator = $container->get(FileRotatorInterface::class);
        $fileTarget = $container->get(FileTarget::class);

        $this->assertInstanceOf(FileRotator::class, $fileRotator);
        $this->assertInstanceOf(FileTarget::class, $fileTarget);
    }

    private function createContainer(?array $params = null): Container
    {
        return new Container(
            ContainerConfig::create()->withDefinitions(
                array_merge(
                    $this->getDiConfig($params),
                    [
                        Aliases::class => [
                            '__construct()' => [
                                [
                                    '@runtime' => __DIR__ . '/runtime',
                                ],
                            ],
                        ],
                    ]
                )
            )
        );
    }

    private function getDiConfig(?array $params = null): array
    {
        if ($params === null) {
            $params = $this->getParams();
        }
        return require dirname(__DIR__) . '/config/di.php';
    }

    private function getParams(): array
    {
        return require dirname(__DIR__) . '/config/params.php';
    }
}
