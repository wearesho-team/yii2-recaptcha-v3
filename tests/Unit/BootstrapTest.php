<?php

namespace Wearesho\ReCaptcha\V3\Yii2\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wearesho\ReCaptcha\V3;
use yii\console;

/**
 * Class BootstrapTest
 * @package Wearesho\ReCaptcha\V3\Yii2\Tests\Unit
 */
class BootstrapTest extends TestCase
{
    /** @var console\Application */
    protected $app;

    protected function setUp(): void
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->app = $this->createMock(console\Application::class);

        (new V3\Yii2\Bootstrap())->bootstrap($this->app);
    }

    public function testContainerOnRecaptchaConfig(): void
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertEquals(
            \Yii::$container->get(V3\ConfigInterface::class),
            new V3\EnvironmentConfig()
        );
    }

    public function testContainerOnRecaptchaYiiConfig(): void
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertEquals(
            \Yii::$container->get(V3\Yii2\ConfigInterface::class),
            new V3\Yii2\EnvironmentConfig()
        );
    }
}
