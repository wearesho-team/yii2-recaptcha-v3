<?php

declare(strict_types=1);

namespace Wearesho\ReCaptcha\V3\Yii2;

use Horat1us\Environment;

/**
 * Class EnvironmentConfig
 * @package Wearesho\ReCaptcha\V3\Yii2
 */
class EnvironmentConfig extends Environment\Yii2\Config implements ConfigInterface
{
    public $keyPrefix = 'RECAPTCHA_';

    public function getEnvironment(): string
    {
        return $this->getEnv('ENVIRONMENT', static::DEFAULT_ENVIRONMENT);
    }
}
