<?php

namespace Wearesho\ReCaptcha\V3\Yii2;

use yii\di;
use yii\web;
use yii\validators;
use Wearesho\ReCaptcha;

/**
 * Class Validator
 * @package Wearesho\ReCaptcha\V3\Yii2
 */
class Validator extends validators\Validator
{
    /**
     * Will be used to check reCAPTCHA token
     * @var array|string|ReCaptcha\V3\Client
     */
    public $client = [
        'class' => ReCaptcha\V3\Client::class,
    ];

    /**
     * Will be used to fetch user ip to pass to
     * @see ReCaptcha\V3\Client::verify()
     *
     * Set to null to skip IP checking
     *
     * @var array|string|web\Request|null
     */
    public $request = 'request';

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
     * If passed action from reCAPTCHA response will be compared with all values
     * @var string[]|null
     */
    public $actions = null;

    /**
     * @see init()
     * @var string
     *
     * Params:
     *  - hostname - the hostname of the site where the reCAPTCHA was solve
     *  - at - timestamp of the challenge load (format Y-m-d H:i:s)
     *  - score - the score for this request (0.0 - 1.0)
     *  - action - the action name for this request
     */
    public $message;

    /**
     * This message will be added if score too low compared with
     * @see min
     * If null `$this->message` will be used
     * Params same with `$this->message`
     * @see message
     * @var string|null
     */
    public $tooLowMessage = null;

    /**
     * This message will be added if score too high compared with
     * @see max
     * If null `$this->message` will be used
     * Params same with `$this->message`
     * @see message
     * @var string|null
     */
    public $tooHighMessage = null;

    /**
     * This message will be added if challenge domain is not in list
     * @see hostNames
     * If null `$this->message` will be used
     * Params same with `$this->message`
     * @see message
     * @var string|null
     */
    public $invalidHostNameMessage = null;

    /**
     * This message will be added if challenge action is not in list
     * @see actions
     * If null `$this->message` will be used
     * Params same with `$this->message`
     * @see message
     * @var string|null
     */
    public $invalidActionMessage = null;

    /**
     * This message will be added if exception caught while checking reCAPTCHA token
     * @see init()
     * @var string
     *
     * Params:
     *  - error - first error returned by Google API
     */
    public $errorMessage;

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init(): void
    {
        parent::init();
        $this->client = di\Instance::ensure($this->client, ReCaptcha\V3\Client::class);
        $this->request = is_null($this->request)
            ? null
            : di\Instance::ensure($this->request, web\Request::class);

        $this->message = \Yii::t('recaptcha', 'reCAPTCHA challenge not passed');
        $this->errorMessage = \Yii::t('recaptcha', 'Error while passing reCAPTCHA challange');
    }

    protected function validateValue($value)
    {
        $ip = $this->request ? $this->request->userIP : null;
        try {
            $response = $this->client->verify($value, $ip);
        } catch (ReCaptcha\V3\Exception $e) {
            $errors = $e->getErrors();
            return [
                $this->errorMessage,
                [
                    'error' => array_shift($errors),
                ]
            ];
        }

        $params = [
            'score' => $response->getScore(),
            'hostname' => $response->getHostname(),
            'at' => $response->getDateTime()->format('Y-m-d H:i:s'),
            'action' => $response->getAction(),
        ];

        if (!is_null($this->min) && $this->min > $response->getScore()) {
            return [$this->tooLowMessage ?? $this->message, $params];
        }
        if (!is_null($this->max) && $response->getScore() > $this->max) {
            return [$this->tooHighMessage ?? $this->message, $params];
        }
        if (!is_null($this->hostNames) && !in_array($response->getHostname(), $this->hostNames)) {
            return [$this->invalidHostNameMessage ?? $this->message, $params];
        }
        if (!is_null($this->actions) && !in_array($response->getAction(), $this->actions)) {
            return [$this->invalidActionMessage ?? $this->message, $params];
        }

        return null;
    }
}
