<?php

namespace Wearesho\ReCaptcha\V3\Yii2\Tests\Unit;

use GuzzleHttp;
use PHPUnit\Framework\TestCase;
use Wearesho\ReCaptcha;
use yii\base;
use yii\web;

/**
 * Class BehaviorTest
 * @package Wearesho\ReCaptcha\V3\Yii2\Tests\Unit
 */
class BehaviorTest extends TestCase
{
    protected const SECRET = 'secret';

    /** @var GuzzleHttp\Handler\MockHandler */
    protected $mock;

    protected function setUp(): void
    {
        putenv('RECAPTCHA_SECRET=' . static::SECRET);

        $container = [];
        $this->mock = new GuzzleHttp\Handler\MockHandler();
        $history = GuzzleHttp\Middleware::history($container);
        $stack = new GuzzleHttp\HandlerStack($this->mock);
        $stack->push($history);

        \Yii::$container->set(GuzzleHttp\ClientInterface::class, GuzzleHttp\Client::class, [
            ['handler' => $stack]
        ]);

        /** @noinspection PhpUnhandledExceptionInspection */
        \Yii::$container->set(
            ReCaptcha\V3\Client::class,
            [],
            [
                \Yii::$container->get(ReCaptcha\V3\EnvironmentConfig::class),
                \Yii::$container->get(GuzzleHttp\ClientInterface::class),
            ]
        );
    }

    public function testSuccessBehavior(): void
    {
        $behavior = new ReCaptcha\V3\Yii2\Behavior([
            'request' => $request = new web\Request([
                'methodParam' => 'get_method'
            ]),
            'actions' => [
                'login' => ['post'],
            ],
            'min' => 0.5,
            'max' => 1,
            'hostNames' => ['wearesho.com',],
        ]);

        $request->headers->set('X-ReCaptcha-Token', static::SECRET);

        $this->mock->append(
            new GuzzleHttp\Psr7\Response(200, [], json_encode([
                'success' => true,
                'score' => 0.6,
                'action' => 'id-controller-login-get',
                'challenge_ts' => date('c'),
                'hostname' => 'wearesho.com',
            ]))
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        $behavior->beforeAction(
            new base\ActionEvent(new base\Action(
                'login',
                new base\Controller('id-controller', new base\Module('id-module'))
            ))
        );

        $this->assertEquals(
            [web\Application::EVENT_BEFORE_ACTION => 'beforeAction'],
            $behavior->events()
        );
    }

    public function testEmptyActions(): void
    {
        $behavior = new ReCaptcha\V3\Yii2\Behavior([
            'request' => $request = new web\Request([
                'methodParam' => 'get_method'
            ]),
            'min' => 0.5,
            'max' => 1,
            'hostNames' => ['wearesho.com',],
        ]);

        $request->headers->set('X-ReCaptcha-Token', static::SECRET);

        $this->mock->append(
            new GuzzleHttp\Psr7\Response(200, [], json_encode([
                'success' => true,
                'score' => 0.6,
                'action' => 'id-controller-login-get',
                'challenge_ts' => date('c'),
                'hostname' => 'wearesho.com',
            ]))
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        $behavior->beforeAction(
            new base\ActionEvent(new base\Action(
                'login',
                new base\Controller('id-controller', new base\Module('id-module'))
            ))
        );

        $this->assertEquals(
            [web\Application::EVENT_BEFORE_ACTION => 'beforeAction'],
            $behavior->events()
        );
    }

    public function testEmptyToken(): void
    {
        $behavior = new ReCaptcha\V3\Yii2\Behavior([
            'request' => $request = new web\Request([
                'methodParam' => 'get_method'
            ]),
            'actions' => [
                'login' => ['post'],
            ],
            'min' => 0.5,
            'max' => 1,
            'hostNames' => ['wearesho.com',],
        ]);

        $this->mock->append(
            new GuzzleHttp\Psr7\Response(200, [], json_encode([
                'success' => true,
                'score' => 0.6,
                'action' => 'id-controller-login-get',
                'challenge_ts' => date('c'),
                'hostname' => 'wearesho.com',
            ]))
        );

        $this->expectException(web\BadRequestHttpException::class);
        $this->expectExceptionMessage('reCAPTCHA challenge failed');
        $this->expectExceptionCode(ReCaptcha\V3\Yii2\Behavior::ERROR_MISSING_HEADER);

        /** @noinspection PhpUnhandledExceptionInspection */
        $behavior->beforeAction(
            new base\ActionEvent(new base\Action(
                'login',
                new base\Controller('id-controller', new base\Module('id-module'))
            ))
        );
    }

    public function testTooLow(): void
    {
        $behavior = new ReCaptcha\V3\Yii2\Behavior([
            'request' => $request = new web\Request([
                'methodParam' => 'get_method'
            ]),
            'actions' => [
                'login' => ['post'],
            ],
            'min' => 0.5,
            'max' => 1,
            'hostNames' => ['wearesho.com',],
        ]);

        $request->headers->set('X-ReCaptcha-Token', static::SECRET);

        $this->mock->append(
            new GuzzleHttp\Psr7\Response(200, [], json_encode([
                'success' => true,
                'score' => 0.2,
                'action' => 'id-controller-login-get',
                'challenge_ts' => date('c'),
                'hostname' => 'wearesho.com',
            ]))
        );

        $this->expectException(web\BadRequestHttpException::class);
        $this->expectExceptionMessage('reCAPTCHA challenge failed');
        $this->expectExceptionCode(ReCaptcha\V3\Yii2\Behavior::ERROR_TOO_LOW);

        /** @noinspection PhpUnhandledExceptionInspection */
        $behavior->beforeAction(
            new base\ActionEvent(new base\Action(
                'login',
                new base\Controller('id-controller', new base\Module('id-module'))
            ))
        );
    }

    public function testNotVerify(): void
    {
        $behavior = new ReCaptcha\V3\Yii2\Behavior([
            'request' => $request = new web\Request([
                'methodParam' => 'get_method'
            ]),
            'actions' => [
                'login' => ['post'],
            ],
            'min' => 0.5,
            'max' => 1,
            'hostNames' => ['wearesho.com',],
        ]);

        $request->headers->set('X-ReCaptcha-Token', static::SECRET);

        $this->mock->append(
            new GuzzleHttp\Psr7\Response(200, [], json_encode([
                'success' => false,
                'score' => 0.7,
                'action' => 'id-controller-login-get',
                'challenge_ts' => date('c'),
                'hostname' => 'wearesho.com',
            ]))
        );

        $this->expectException(web\BadRequestHttpException::class);
        $this->expectExceptionMessage('reCAPTCHA challenge failed');
        $this->expectExceptionCode(ReCaptcha\V3\Yii2\Behavior::ERROR_NOT_SUCCESSFUL);

        /** @noinspection PhpUnhandledExceptionInspection */
        $behavior->beforeAction(
            new base\ActionEvent(new base\Action(
                'login',
                new base\Controller('id-controller', new base\Module('id-module'))
            ))
        );
    }

    public function testInvalidHostName(): void
    {
        $behavior = new ReCaptcha\V3\Yii2\Behavior([
            'request' => $request = new web\Request([
                'methodParam' => 'get_method'
            ]),
            'actions' => [
                'login' => ['post'],
            ],
            'min' => 0.5,
            'max' => 1,
            'hostNames' => ['wearesho.com',],
        ]);

        $request->headers->set('X-ReCaptcha-Token', static::SECRET);

        $this->mock->append(
            new GuzzleHttp\Psr7\Response(200, [], json_encode([
                'success' => true,
                'score' => 0.7,
                'action' => 'id-controller-login-get',
                'challenge_ts' => date('c'),
                'hostname' => 'invalid.com',
            ]))
        );

        $this->expectException(web\BadRequestHttpException::class);
        $this->expectExceptionMessage('reCAPTCHA challenge failed');
        $this->expectExceptionCode(ReCaptcha\V3\Yii2\Behavior::ERROR_INVALID_HOSTNAME);

        /** @noinspection PhpUnhandledExceptionInspection */
        $behavior->beforeAction(
            new base\ActionEvent(new base\Action(
                'login',
                new base\Controller('id-controller', new base\Module('id-module'))
            ))
        );
    }

    public function testInvalidAction(): void
    {
        $behavior = new ReCaptcha\V3\Yii2\Behavior([
            'request' => $request = new web\Request([
                'methodParam' => 'get_method'
            ]),
            'actions' => [
                'login' => ['post'],
            ],
            'min' => 0.5,
            'max' => 1,
            'hostNames' => ['wearesho.com',],
        ]);

        $request->headers->set('X-ReCaptcha-Token', static::SECRET);

        $this->mock->append(
            new GuzzleHttp\Psr7\Response(200, [], json_encode([
                'success' => true,
                'score' => 0.7,
                'action' => 'invalid-action',
                'challenge_ts' => date('c'),
                'hostname' => 'wearesho.com',
            ]))
        );

        $this->expectException(web\BadRequestHttpException::class);
        $this->expectExceptionMessage('reCAPTCHA challenge failed');
        $this->expectExceptionCode(ReCaptcha\V3\Yii2\Behavior::ERROR_INVALID_ACTION);

        /** @noinspection PhpUnhandledExceptionInspection */
        $behavior->beforeAction(
            new base\ActionEvent(new base\Action(
                'login',
                new base\Controller('id-controller', new base\Module('id-module'))
            ))
        );
    }

    public function testTooHigh(): void
    {
        $behavior = new ReCaptcha\V3\Yii2\Behavior([
            'request' => $request = new web\Request([
                'methodParam' => 'get_method'
            ]),
            'actions' => [
                'login' => ['post'],
            ],
            'min' => 0.5,
            'max' => 0.7,
            'hostNames' => ['wearesho.com',],
        ]);

        $request->headers->set('X-ReCaptcha-Token', static::SECRET);

        $this->mock->append(
            new GuzzleHttp\Psr7\Response(200, [], json_encode([
                'success' => true,
                'score' => 1,
                'action' => 'id-controller-login-get',
                'challenge_ts' => date('c'),
                'hostname' => 'wearesho.com',
            ]))
        );

        $this->expectException(web\BadRequestHttpException::class);
        $this->expectExceptionMessage('reCAPTCHA challenge failed');
        $this->expectExceptionCode(ReCaptcha\V3\Yii2\Behavior::ERROR_TOO_HIGH);

        /** @noinspection PhpUnhandledExceptionInspection */
        $behavior->beforeAction(
            new base\ActionEvent(new base\Action(
                'login',
                new base\Controller('id-controller', new base\Module('id-module'))
            ))
        );
    }

    public function testExtendedEmptyToken(): void
    {
        $behavior = new class([
            'request' => $request = new web\Request([
                'methodParam' => 'get_method'
            ]),
            'actions' => [
                'login' => ['post'],
            ],
            'min' => 0.5,
            'max' => 1,
            'hostNames' => ['wearesho.com',],
        ]) extends ReCaptcha\V3\Yii2\Behavior
        {
            protected function challengeFailed(): void
            {
                return;
            }
        };

        $this->mock->append(
            new GuzzleHttp\Psr7\Response(200, [], json_encode([
                'success' => true,
                'score' => 0.6,
                'action' => 'id-controller-login-get',
                'challenge_ts' => date('c'),
                'hostname' => 'wearesho.com',
            ]))
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        $behavior->beforeAction(
            new base\ActionEvent(new base\Action(
                'login',
                new base\Controller('id-controller', new base\Module('id-module'))
            ))
        );

        $this->assertEquals(
            [web\Application::EVENT_BEFORE_ACTION => 'beforeAction'],
            $behavior->events()
        );
    }

    public function testExtendedTooLow(): void
    {
        $behavior = new class([
            'request' => $request = new web\Request([
                'methodParam' => 'get_method'
            ]),
            'actions' => [
                'login' => ['post'],
            ],
            'min' => 0.5,
            'max' => 1,
            'hostNames' => ['wearesho.com',],
        ]) extends ReCaptcha\V3\Yii2\Behavior
        {
            protected function validationError(ReCaptcha\V3\Response $response, int $code): void
            {
                return;
            }
        };

        $request->headers->set('X-ReCaptcha-Token', static::SECRET);

        $this->mock->append(
            new GuzzleHttp\Psr7\Response(200, [], json_encode([
                'success' => true,
                'score' => 0.2,
                'action' => 'id-controller-login-get',
                'challenge_ts' => date('c'),
                'hostname' => 'wearesho.com',
            ]))
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        $behavior->beforeAction(
            new base\ActionEvent(new base\Action(
                'login',
                new base\Controller('id-controller', new base\Module('id-module'))
            ))
        );

        $this->assertEquals(
            [web\Application::EVENT_BEFORE_ACTION => 'beforeAction'],
            $behavior->events()
        );
    }

    public function testExtendedTooHigh(): void
    {
        $behavior = new class([
            'request' => $request = new web\Request([
                'methodParam' => 'get_method'
            ]),
            'actions' => [
                'login' => ['post'],
            ],
            'min' => 0.5,
            'max' => 0.7,
            'hostNames' => ['wearesho.com',],
        ]) extends ReCaptcha\V3\Yii2\Behavior
        {
            protected function validationError(ReCaptcha\V3\Response $response, int $code): void
            {
                return;
            }
        };

        $request->headers->set('X-ReCaptcha-Token', static::SECRET);

        $this->mock->append(
            new GuzzleHttp\Psr7\Response(200, [], json_encode([
                'success' => true,
                'score' => 1,
                'action' => 'id-controller-login-get',
                'challenge_ts' => date('c'),
                'hostname' => 'wearesho.com',
            ]))
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        $behavior->beforeAction(
            new base\ActionEvent(new base\Action(
                'login',
                new base\Controller('id-controller', new base\Module('id-module'))
            ))
        );

        $this->assertEquals(
            [web\Application::EVENT_BEFORE_ACTION => 'beforeAction'],
            $behavior->events()
        );
    }

    public function testExtendedInvalidHostName(): void
    {
        $behavior = new class([
            'request' => $request = new web\Request([
                'methodParam' => 'get_method'
            ]),
            'actions' => [
                'login' => ['post'],
            ],
            'min' => 0.5,
            'max' => 0.7,
            'hostNames' => ['wearesho.com',],
        ]) extends ReCaptcha\V3\Yii2\Behavior
        {
            protected function validationError(ReCaptcha\V3\Response $response, int $code): void
            {
                return;
            }
        };

        $request->headers->set('X-ReCaptcha-Token', static::SECRET);

        $this->mock->append(
            new GuzzleHttp\Psr7\Response(200, [], json_encode([
                'success' => true,
                'score' => 0.7,
                'action' => 'id-controller-login-get',
                'challenge_ts' => date('c'),
                'hostname' => 'invalid.com',
            ]))
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        $behavior->beforeAction(
            new base\ActionEvent(new base\Action(
                'login',
                new base\Controller('id-controller', new base\Module('id-module'))
            ))
        );

        $this->assertEquals(
            [web\Application::EVENT_BEFORE_ACTION => 'beforeAction'],
            $behavior->events()
        );
    }

    public function testExtendedNotVerify(): void
    {
        $behavior = new class([
            'request' => $request = new web\Request([
                'methodParam' => 'get_method'
            ]),
            'actions' => [
                'login' => ['post'],
            ],
            'min' => 0.5,
            'max' => 0.7,
            'hostNames' => ['wearesho.com',],
        ]) extends ReCaptcha\V3\Yii2\Behavior
        {
            protected function notSuccessful(ReCaptcha\V3\Exception $exception): void
            {
                return;
            }
        };

        $request->headers->set('X-ReCaptcha-Token', static::SECRET);

        $this->mock->append(
            new GuzzleHttp\Psr7\Response(200, [], json_encode([
                'success' => false,
                'score' => 0.7,
                'action' => 'id-controller-login-get',
                'challenge_ts' => date('c'),
                'hostname' => 'wearesho.com',
            ]))
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        $behavior->beforeAction(
            new base\ActionEvent(new base\Action(
                'login',
                new base\Controller('id-controller', new base\Module('id-module'))
            ))
        );

        $this->assertEquals(
            [web\Application::EVENT_BEFORE_ACTION => 'beforeAction'],
            $behavior->events()
        );
    }

    public function testExtendedInvalidAction(): void
    {
        $behavior = new class([
            'request' => $request = new web\Request([
                'methodParam' => 'get_method'
            ]),
            'actions' => [
                'login' => ['post'],
            ],
            'min' => 0.5,
            'max' => 0.7,
            'hostNames' => ['wearesho.com',],
        ]) extends ReCaptcha\V3\Yii2\Behavior
        {
            protected function validationError(ReCaptcha\V3\Response $response, int $code): void
            {
                return;
            }
        };

        $request->headers->set('X-ReCaptcha-Token', static::SECRET);

        $this->mock->append(
            new GuzzleHttp\Psr7\Response(200, [], json_encode([
                'success' => true,
                'score' => 0.7,
                'action' => 'invalid-action',
                'challenge_ts' => date('c'),
                'hostname' => 'wearesho.com',
            ]))
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        $behavior->beforeAction(
            new base\ActionEvent(new base\Action(
                'login',
                new base\Controller('id-controller', new base\Module('id-module'))
            ))
        );

        $this->assertEquals(
            [web\Application::EVENT_BEFORE_ACTION => 'beforeAction'],
            $behavior->events()
        );
    }

    protected function tearDown(): void
    {
        putenv('RECAPTCHA_SECRET');
    }
}
