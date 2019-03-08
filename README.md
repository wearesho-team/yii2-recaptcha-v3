# reCAPTCHA v3 integration (Yii2)
[![Build Status](https://travis-ci.org/wearesho-team/yii2-recaptcha-v3.svg?branch=master)](https://travis-ci.org/wearesho-team/yii2-recaptcha-v3)
[![codecov](https://codecov.io/gh/wearesho-team/yii2-recaptcha-v3/branch/master/graph/badge.svg)](https://codecov.io/gh/wearesho-team/yii2-recaptcha-v3)

This module provides behavior, validator and bootstrap to connection reCAPTCHA to Yii2 Application.

## Installation

```bash
composer require wearesho-team/yii2-recaptcha-v3:^1.0.0
```

## Usage

### Configuration
To configure current reCAPTCHA environment (will be used in [Behavior](./src/Behavior.php)) you have to use
[ConfigInterface](./src/ConfigInterface). 

#### Environment Configuration
| Key                      | Required | Format  | Description                  |
|--------------------------|----------|---------|------------------------------|
| RECAPTCHA_ENVIRONMENT    | -        | string  | YII_ENV will be used default |

### Bootstrap
```php
<?php

use Wearesho\ReCaptcha;

// config.php

return [
    'bootstrap' => [
        'reCaptcha' => [
            'class' => ReCaptcha\V3\Yii2\Bootstrap::class,
            'config' => ReCaptcha\V3\EnvironmentConfig::class, // or another config interface implementation
            'yiiConfig' => ReCaptcha\V3\Yii2\EnvironmentConfig::class, // will be used for environment checking
        ],
        // another bootstraps      
    ],
];

```
See [wearesho-team/recaptcha-v3](https://github.com/wearesho-team/recaptcha-v3) docs for environment config details.

### Validator

```php
<?php

use yii\base;
use Wearesho\ReCaptcha;

class Model extends base\Model {
    /** @var string token for reCAPTCHA verification */
    public $token;
    
    public function rules(): array
    {
        return [
            [['token',], 'required',],
            [['token',], ReCaptcha\V3\Yii2\Validator::class,
                'min' => 0.5,
                'max' => 1,
                'actions' => ['login',],
                'hostNames' => ['wearesho.com',],
            ],
        ];
    }
}
```
See [Validator](./src/Validator.php) source code for properties details.

### Behavior
Behavior is way to validate reCAPTCHA token in `web\Controller`.
```php
<?php

use yii\web;
use Wearesho\ReCaptcha;

class Controller extends web\Controller 
{
    public function behaviors(): array
    {
        return [
            'reCaptcha' => [
                'class' => ReCaptcha\V3\Yii2\Behavior::class,
                'actions' => [
                    'login' => ['post',],   
                ],
                'min' => 0.5,
                'max' => 1,
                'hostNames' => ['wearesho.com',],
            ],      
        ];
    }
    
    // controller code
}
```
In this example behavior will check for `X-ReCaptcha-Token` header before `login` action (only in case of POST action).
If header is missing or it's value invalid `\yii\web\BadRequestHttpException` will be threw.
See [Behavior](./src/Behavior.php) source code for code details and property docs.

When checking reCAPTCHA response `action` attribute will be used current controller ID, action ID and request method:
`controlleractionmethod`, without dividers. Example: `loginindexpost`, where `login` is controller ID, `index` is action ID and `post` is request method).

*Note: `Behavior::actions` property works different way compared to `Validator`*

## Contributors
- [Alexander Letnikow](mailto:reclamme@gmail.com)
- [Alexander Yagich](mailto:aleksa.yagich@gmail.com)


## License
[MIT](./LICENSE)
