<?php

declare(strict_types=1);

namespace Wearesho\ReCaptcha\V3\Yii2\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wearesho\ReCaptcha;

/**
 * Class ConfigTest
 * @package Wearesho\ReCaptcha\V3\Yii2\Tests\Unit
 */
class ConfigTest extends TestCase
{
    protected const ENVIRONMENT = 'test-case';

    /** @var ReCaptcha\V3\Yii2\Config */
    protected $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = new ReCaptcha\V3\Yii2\Config([
            'environment' => static::ENVIRONMENT
        ]);
    }

    public function testConfig(): void
    {
        $this->assertEquals(
            static::ENVIRONMENT,
            $this->config->getEnvironment()
        );
    }
}
