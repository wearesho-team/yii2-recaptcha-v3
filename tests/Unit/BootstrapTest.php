<?php

namespace Wearesho\ReCaptcha\V3\Yii2\Tests\Unit;

use Wearesho\ReCaptcha\V3;
use yii\phpunit\TestCase;

/**
 * Class BootstrapTest
 * @package Wearesho\ReCaptcha\V3\Yii2\Tests\Unit
 */
class BootstrapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
}
