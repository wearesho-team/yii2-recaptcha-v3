<?php

declare(strict_types=1);

namespace Wearesho\ReCaptcha\V3\Yii2;

/**
 * Interface ConfigInterface
 * @package Wearesho\ReCaptcha\V3\Yii2
 */
interface ConfigInterface
{
    public const DEFAULT_ENVIRONMENT = YII_ENV;

    public function getEnvironment(): string;
}
