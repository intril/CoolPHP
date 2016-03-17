<?php

// 设置错误报告级别
error_reporting ( E_ALL | E_STRICT ); //

// session 配置
$config ['session'] = array( 'start' => true, 'namespace' => 'Default');
$config ['sub_folder'] = str_replace($_SERVER['DOCUMENT_ROOT'], '', str_replace('\\', '/', BASE_PATH));
$config ['site_url'] = 'http://' . $_SERVER['HTTP_HOST'] . $config['sub_folder'];
$config ['base_path'] = BASE_PATH;
$config ['log_path'] = APP_PATH . 'cache/';
$config ['debug'] = true;
$config ['auto_route'] = true;

$config ['default_database'] = 'backend';
$config ['default_redis'] = 'default';
$config ['page_size'] = 10;

$config ['weixin'] = array(
    'token' => 'test_token',
    'api_url' => 'https://api.weixin.qq.com',
    'app_id' => 'xxxxxxxx',
    'app_secret' => 'xxxxxxx',
);

// 加载错误码定义文件
include 'code.conf.php';
/* End of file config.php */