<?php
/**
 * Redis缓存配置
 */
$redis = array (
        'default' => array (
                'host' => '120.25.89.38',
                'port' => 6379,
                'db' => 0,
                'timeout' => 300,
                'persistent' => TRUE
        ),
        'RQ:default' => array (
                'host' => '120.25.89.38',
                'port' => 6379,
                'db' => 0,
                'max_length' => 100000,
                'timeout' => 300,
                'persistent' => TRUE
        ),
);