<?php

namespace Wearesho\ReCaptcha\V3\Yii2\Tests\Unit;

use GuzzleHttp;
use PHPUnit\Framework\TestCase;
use Wearesho\ReCaptcha\V3;
use yii\i18n\PhpMessageSource;
use yii\web;

/**
 * Class ValidatorTest
 * @package Wearesho\ReCaptcha\V3\Yii2\Tests\Unit
 */
class ValidatorTest extends TestCase
{
    protected const SECRET = 'secret';

    /** @var GuzzleHttp\Handler\MockHandler */
    protected $mock;

    protected function setUp(): void
    {
        putenv('RECAPTCHA_SECRET=' . static::SECRET);

        \Yii::$app = new web\Application([
            'id' => 'yii2-recaptcha-v3',
            'basePath' => dirname(dirname(__DIR__)),
            'sourceLanguage' => 'en',
            'language' => 'ru',
            'components' => [
                'request' => [
                    'class' => web\Request::class,
                    'enableCookieValidation' => false,
                ]
            ]
        ]);
        \Yii::$app->i18n->translations['recaptcha'] = [
            'class' => PhpMessageSource::class,
            'sourceLanguage' => 'en-US',
            'basePath' => \Yii::getAlias('@root/messages'),
            'fileMap' => [
                'recaptcha' => 'recaptcha.php'
            ]
        ];

        $container = [];
        $this->mock = new GuzzleHttp\Handler\MockHandler();
        $history = GuzzleHttp\Middleware::history($container);
        $stack = new GuzzleHttp\HandlerStack($this->mock);
        $stack->push($history);

        \Yii::$container->set(GuzzleHttp\ClientInterface::class, GuzzleHttp\Client::class, [
            ['handler' => $stack]
        ]);
    }

    public function testInit(): void
    {
        $validator = new V3\Yii2\Validator();

        $this->assertEquals($validator->client, \Yii::$container->get(V3\Client::class));
        $this->assertEquals($validator->request, \Yii::$app->request);
        $this->assertEquals($validator->message, 'Проверка reCAPTCHA не пройдена');
        $this->assertEquals($validator->errorMessage, 'Error while passing reCAPTCHA challange');
    }

    public function testInitWithEmptyRequest(): void
    {
        $validator = new V3\Yii2\Validator([
            'request' => null,
        ]);
        $this->assertEquals($validator->client, \Yii::$container->get(V3\Client::class));
        $this->assertNull($validator->request);
        $this->assertEquals($validator->message, 'Проверка reCAPTCHA не пройдена');
        $this->assertEquals($validator->errorMessage, 'Error while passing reCAPTCHA challange');
    }

    public function testFailedValidate(): void
    {
        putenv('RECAPTCHA_SECRET=' . static::SECRET);
        $validator = new V3\Yii2\Validator();

        $this->assertFalse($validator->validate('hello'));
    }

    public function testSuccessValidate(): void
    {
        $this->mock->append(
            new GuzzleHttp\Psr7\Response(200, [], json_encode([
                'success' => true,
                'score' => 1,
                'action' => 'test',
                'challenge_ts' => date('c'),
                'hostname' => 'wearesho.com',
            ]))
        );

        $validator = $this->createValidator();

        $this->assertTrue($validator->validate('test'));
    }

    public function testTooLowValidate(): void
    {
        $tooLowMessage = 'test low';

        $validator = $this->createValidator([
            'tooLowMessage' => $tooLowMessage,
            'min' => 0.6
        ]);

        $this->mock->append(
            new GuzzleHttp\Psr7\Response(200, [], json_encode([
                'success' => true,
                'score' => 0.5,
                'action' => 'test',
                'challenge_ts' => date('c'),
                'hostname' => 'wearesho.com',
            ]))
        );

        $error = null;
        $this->assertFalse($validator->validate('test', $error));
        $this->assertEquals($error, $tooLowMessage);
    }

    public function testTooHighValidate(): void
    {
        $tooHighMessage = 'test high';

        $validator = $this->createValidator([
            'tooHighMessage' => $tooHighMessage,
            'max' => 0.5
        ]);

        $this->mock->append(
            new GuzzleHttp\Psr7\Response(200, [], json_encode([
                'success' => true,
                'score' => 0.6,
                'action' => 'test',
                'challenge_ts' => date('c'),
                'hostname' => 'wearesho.com',
            ]))
        );

        $error = null;
        $this->assertFalse($validator->validate('test', $error));
        $this->assertEquals($error, $tooHighMessage);
    }

    public function testInvalidHostName(): void
    {
        $invalidHostNameMessage = 'test host';

        $validator = $this->createValidator([
            'invalidHostNameMessage' => $invalidHostNameMessage,
            'hostNames' => ['google.com'],
        ]);

        $this->mock->append(
            new GuzzleHttp\Psr7\Response(200, [], json_encode([
                'success' => true,
                'score' => 0.6,
                'action' => 'test',
                'challenge_ts' => date('c'),
                'hostname' => 'wearesho.com',
            ]))
        );

        $error = null;
        $this->assertFalse($validator->validate('test', $error));
        $this->assertEquals($error, $invalidHostNameMessage);
    }

    public function testInvalidAction(): void
    {
        $invalidActionMessage = 'test action';

        $validator = $this->createValidator([
            'invalidActionMessage' => $invalidActionMessage,
            'actions' => ['another']
        ]);

        $this->mock->append(
            new GuzzleHttp\Psr7\Response(200, [], json_encode([
                'success' => true,
                'score' => 0.6,
                'action' => 'test',
                'challenge_ts' => date('c'),
                'hostname' => 'wearesho.com',
            ]))
        );

        $error = null;
        $this->assertFalse($validator->validate('test', $error));
        $this->assertEquals($error, $invalidActionMessage);
    }

    protected function createValidator(array $params = []): V3\Yii2\Validator
    {
        /** @var V3\Yii2\Validator $validator */
        $validator = \Yii::$container->get(
            V3\Yii2\Validator::class,
            [],
            [
                'client' => \Yii::$container->get(
                    V3\Client::class,
                    [
                        \Yii::$container->get(V3\EnvironmentConfig::class),
                        \Yii::$container->get(GuzzleHttp\ClientInterface::class),
                    ]
                ),
            ] + $params
        );

        return $validator;
    }

    protected function tearDown(): void
    {
        putenv('RECAPTCHA_SECRET');
    }
}
