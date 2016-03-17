# CoolPHP Framework

Thank you for choosing CoolPHP framework,  A simple, efficient, raw PHP framework for Web system apis



DIRECTORY STRUCTURE
--

````
CoolPHP/
├── applicate
│   ├── cache
│   │   └── logger.log
│   ├── config
│   │   ├── code.conf.php
│   │   ├── common.conf.php
│   │   ├── db.conf.php
│   │   ├── redis.conf.php
│   │   └── routes.conf.php
│   ├── controller
│   │   ├── Controller.php
│   │   └── IndexController.php
│   ├── helper
│   │   └── function.php
│   ├── libraries
│   │   ├── QRcode.php
│   │   └── Weixin.php
│   └── model
│       └── Model.php
├── index.php
└── system
    ├── Cool.php
    ├── core
    │   ├── CoolController.php
    │   ├── CoolException.php
    │   ├── CoolModel.php
    │   ├── Router.php
    │   └── Session.php
    ├── database
    │   ├── mysql
    │   │   ├── MySQLHelper.php
    │   │   ├── MySQLiHelper.php
    │   │   └── PDOHelper.php
    │   └── redis
    │       └── RedisHelper.php
    └── helper
        ├── Cookie.php
        ├── CSV.php
        ├── File.php
        ├── Http.php
        └── Log.php
````


REQUIREMENTS
--
The minimum requirement by Yii is that your Web server supports PHP 5.4.