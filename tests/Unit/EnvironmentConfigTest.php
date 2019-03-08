<?php

declare(strict_types=1);

namespace Wearesho\ReCaptcha\V3\Yii2\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wearesho\ReCaptcha;

/**
 * Class EnvironmentConfigTest
 * @package Wearesho\ReCaptcha\V3\Yii2\Tests\Unit
 */
class EnvironmentConfigTest extends TestCase
{
    protected const ENVIRONMENT = 'test-case';

    /** @var ReCaptcha\V3\Yii2\EnvironmentConfig */
    protected $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = new ReCaptcha\V3\Yii2\EnvironmentConfig();
    }

    public function testGetEnvironment(): void
    {
        putenv('RECAPTCHA_ENVIRONMENT=' . static::ENVIRONMENT);
        $this->assertEquals(
            static::ENVIRONMENT,
            $this->config->getEnvironment()
        );
    }

    public function testGetDefaultEnvironment(): void
    {
        putenv('RECAPTCHA_ENVIRONMENT');
        $this->assertEquals(
            ReCaptcha\V3\Yii2\ConfigInterface::DEFAULT_ENVIRONMENT,
            $this->config->getEnvironment()
        );
    }
}
