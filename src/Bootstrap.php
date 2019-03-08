<?php

declare(strict_types=1);

namespace Wearesho\ReCaptcha\V3\Yii2;

use yii\base;
use Wearesho\ReCaptcha;

/**
 * Class Bootstrap
 * @package Wearesho\ReCaptcha\V3\Yii2
 */
class Bootstrap implements base\BootstrapInterface
{
    /** @var array|string|ReCaptcha\V3\ConfigInterface */
    public $config = [
        'class' => ReCaptcha\V3\EnvironmentConfig::class,
    ];

    /** @var array|string|ConfigInterface */
    public $yiiConfig = [
        'class' => EnvironmentConfig::class,
    ];

    /**
     * @inheritdoc
     */
    public function bootstrap($app): void
    {
        \Yii::$container->setDefinitions([
            ReCaptcha\V3\ConfigInterface::class => $this->config,
            ConfigInterface::class => $this->yiiConfig,
        ]);
    }
}
