<?php

namespace Wearesho\ReCaptcha\V3\Yii2\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wearesho\ReCaptcha\V3;
use yii\console\Application;

/**
 * Class BootstrapTest
 * @package Wearesho\ReCaptcha\V3\Yii2\Tests\Unit
 */
class BootstrapTest extends TestCase
{
    /** @var Application */
    protected $app;

    protected function setUp(): void
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->app = new Application([
            'id' => 'yii2-recaptcha-v3',
            'basePath' => dirname(dirname(__DIR__)),
        ]);

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
