<?php

declare(strict_types=1);

namespace Wearesho\ReCaptcha\V3\Yii2;

use yii\di;
use yii\web;
use yii\base;
use Wearesho\ReCaptcha;

/**
 * Class Behavior
 * @package Wearesho\ReCaptcha\V3\Yii2
 */
class Behavior extends base\Behavior
{
    public const HEADER = 'X-ReCaptcha-Token';

    public const ERROR_MISSING_HEADER = 21001;
    public const ERROR_NOT_SUCCESSFUL = 21002;
    public const ERROR_TOO_LOW = 21003;
    public const ERROR_TOO_HIGH = 21004;
    public const ERROR_INVALID_HOSTNAME = 21005;
    public const ERROR_INVALID_ACTION = 21006;

    /**
     * Will be used to check reCAPTCHA token
     * @var array|string|ReCaptcha\V3\Client
     */
    public $client = [
        'class' => ReCaptcha\V3\Client::class,
    ];

    /**
     * Request will be used to fetch reCAPTCHA token from headers
     * @var array|string|web\Request
     */
    public $request = 'request';

    /**
     * Header name will be used to fetch reCAPTCHA token
     * @var string
     */
    public $header = self::HEADER;

    /**
     * Value between 0 and 1 to compare with google score
     * Set to null to skip minimal value checking
     * `$this->min > $score` condition will be used
     *
     * @var int
     */
    public $min = null;

    /**
     * Value between 0 and 1 to compare with google score
     * Set to null to skip maximal value checking
     * `$score > $this->max` condition will be used
     *
     * @var int|null
     */
    public $max = null;

    /**
     * If passed hostname from reCAPTCHA response will be compared with all values
     * @var string[]|null
     */
    public $hostNames = null;

    /**
     * Controller actions to check. Where key is action id and value is array of method
     *
     * ```php
     * // @var \Wearesho\ReCaptcha\V3\Yii2\Behavior $behavior
     * $behavior->actions = [
     *  'login' => ['post'],
     *  'stats' => ['get', 'post'],
     * ]
     * ```
     * if set to null all actions will be checked
     *
     * @var string[][]|null
     */
    public $actions = null;

    /**
     * @throws base\InvalidConfigException
     */
    public function init(): void
    {
        parent::init();
        $this->client = di\Instance::ensure($this->client, ReCaptcha\V3\Client::class);
        $this->request = di\Instance::ensure($this->request, web\Request::class);
    }

    public function events(): array
    {
        return [
            base\Controller::EVENT_BEFORE_ACTION => 'beforeAction',
        ];
    }

    /**
     * @param base\ActionEvent $event
     * @throws web\HttpException
     *
     */
    public function beforeAction(base\ActionEvent $event): void
    {
        if (is_null($this->actions) || !in_array($event->action->id, $this->actions, true)) {
            return;
        }

        $token = $this->request->headers->get($this->header);
        if (is_null($token)) {
            $this->challengeFailed();
            return;
        }

        try {
            $response = $this->client->verify($token, $this->request->userIP);
        } catch (ReCaptcha\V3\Exception $e) {
            $this->notSuccessful($e);
            return;
        }

        if (!is_null($this->min) && $this->min > $response->getScore()) {
            $this->tooLow($response);
            return;
        }
        if (!is_null($this->max) && $response->getScore() > $this->max) {
            $this->tooHigh($response);
            return;
        }
        if (!is_null($this->hostNames) && !in_array($response->getHostname(), $this->hostNames)) {
            $this->invalidHostName($response);
            return;
        }

        $needleCaptchaAction = mb_strtolower(
            "{$event->action->id}-{$event->action->controller->id}-{$this->request->method}"
        );
        if ($needleCaptchaAction !== $response->getAction()) {
            $this->invalidAction($response);
            return;
        }
    }

    /**
     * @throws web\HttpException
     */
    protected function challengeFailed(): void
    {
        throw new web\BadRequestHttpException(
            "reCAPTCHA challenge failed",
            static::ERROR_MISSING_HEADER
        );
    }

    /**
     * @param ReCaptcha\V3\Exception $exception
     * @throws web\HttpException
     */
    protected function notSuccessful(ReCaptcha\V3\Exception $exception): void
    {
        throw new web\BadRequestHttpException(
            "reCAPTCHA challenge failed",
            static::ERROR_NOT_SUCCESSFUL,
            $exception
        );
    }

    /**
     * @param ReCaptcha\V3\Response $response @
     * @throws web\HttpException
     */
    protected function tooLow(ReCaptcha\V3\Response $response): void
    {
        $this->validationError($response, static::ERROR_TOO_LOW);
    }

    /**
     * @param ReCaptcha\V3\Response $response @
     * @throws web\HttpException
     */
    protected function tooHigh(ReCaptcha\V3\Response $response): void
    {
        $this->validationError($response, static::ERROR_TOO_HIGH);
    }

    /**
     * @param ReCaptcha\V3\Response $response @
     * @throws web\HttpException
     */
    protected function invalidHostName(ReCaptcha\V3\Response $response): void
    {
        $this->validationError($response, static::ERROR_INVALID_HOSTNAME);
    }

    /**
     * @param ReCaptcha\V3\Response $response @
     * @throws web\HttpException
     */
    protected function invalidAction(ReCaptcha\V3\Response $response): void
    {
        $this->validationError($response, static::ERROR_INVALID_ACTION);
    }

    /**
     * @param ReCaptcha\V3\Response $response
     * @param int $code
     * @throws web\HttpException
     *
     */
    protected function validationError(ReCaptcha\V3\Response $response, int $code): void
    {
        throw new web\BadRequestHttpException(
            "reCAPTCHA challenge failed",
            $code
        );
    }
}
