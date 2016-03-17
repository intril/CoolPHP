<?php
/*
 * -------------------------------------------------------------------
 *  Now that we know the path, set the main path constants
 * -------------------------------------------------------------------
 */
if ( !defined( 'BASE_PATH' ) ) {
    define ( 'BASE_PATH', realpath( dirname( __FILE__ ) ) );
}

// 脚本开始执行时间(秒)
define ( 'START_TIME', microtime ( TRUE ) );
define ( 'START_MEMORY', memory_get_usage () );
// 设置默认时区
date_default_timezone_set ( 'Asia/Shanghai' );

// 定义常量
define ( 'DS', DIRECTORY_SEPARATOR );
define ( 'INDEX', 'index.php' );
define ( 'SYS_PATH', BASE_PATH . DS . 'system' . DS );
define ( 'CORE_PATH', SYS_PATH . DS . 'core' . DS );
define ( 'APP_PATH', BASE_PATH . DS . 'applicate' . DS );

/*
 * -------------------------------------------------------------------
*  LOAD THE common.conf.php
* -------------------------------------------------------------------
*/
include_once BASE_PATH . '/applicate/config/common.conf.php';

/*
 * --------------------------------------------------------------------
* LOAD THE BOOTSTRAP FILE
* --------------------------------------------------------------------
*
* And away we go...give the configure and router to setting
*
*/
include_once SYS_PATH . 'Cool.php';

Cool::start( $config );
/* End of file index.php */