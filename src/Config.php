<?php

declare(strict_types=1);

namespace Wearesho\ReCaptcha\V3\Yii2;

use yii\base;

/**
 * Class Config
 * @package Wearesho\ReCaptcha\V3\Yii2
 */
class Config extends base\BaseObject implements ConfigInterface
{
    /** @var string */
    public $environment = YII_ENV;

    public function getEnvironment(): string
    {
        return (string)$this->environment;
    }
}
