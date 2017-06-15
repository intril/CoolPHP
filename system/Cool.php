<?php

/**
 * System Start
 * Loads the base classes and executes the request.
 *
 * @package     CoolPHP
 * @subpackage  setting
 * @category    setting
 * @author      Intril.Leng <jj.comeback@gmail.com>
 */
class Cool {

    /**
     * Helper对象
     * @var unknown
     */
    protected static $_helper;

    /**
     * Session对象
     * @var unknown
     */
    protected static $_session;

    /**
     * 路由对象
     * @var unknown
     */
    protected static $_route;

    /**
     * 全局配置变量
     * @var unknown
     */
    public static $GC;

    /**
     * 已加载的对象
     * @var unknown
     */
    private static $_loaded;

    /**
     * 初始化
     */
    private static function _init ( $config ) {
        // 先自动加载系统必须的库
        self::_load_core();
        // 设置自定义错误处理函数
        set_error_handler( array( __CLASS__, 'error_handler' ) );
        // 设置config变量到系统中
        foreach ( $config as $k => $v ) {
            Cool::$GC[$k] = $v;
        }
        // 加载路由配置文件
        if ( is_file( APP_PATH . 'config/routes.conf.php' ) ) {
            include_once APP_PATH . 'config/routes.conf.php';
        } else {
            throw new CoolException( CoolException::ERR_SYSTEM, 'Not Found routes.conf.php' );
        }
        if ( PHP_SAPI == 'cli' ) {
            self::$_route = isset( $route['cli'] ) ? $route['cli'] : null;
        } else {
            if ( isset( $route['cli'] ) ) {
                unset( $route['cli'] );
            }
            self::$_route = isset ( $route ) ? $route : null;
        }
    }

    /**
     * 运行Web模式的app,解析访问参数成路由模式.
     * Auto routing, sub folder, subdomain, sub folder on subdomain are supported.
     * It can be used with or without the <i>index.php</i> in the URI
     *
     * @return mixed HTTP status code such as 404 or URL for redirection
     */
    public static function start ( $config ) {
        // 初始化部分
        self::_init( $config );
        $route_result = Router::parse( self::$_route );
        if ( empty( $route_result ) ) {
            throw new CoolException( CoolException::ERR_INVALID_REQUEST, 'Invalid Router :' . json_encode( $route_result ) );
        }
        // 检查是否有子目录
        if ( isset( $route_result[2] ) ) {
            list( $controller, $action, $folder ) = $route_result;
            $class_folder = 'controller' . $folder;
        } else {
            list( $controller, $action ) = $route_result;
            $class_folder = 'controller';
        }
        // 加载App控制器
        self::auto_load( $controller, $class_folder );
        // 检查类是否存在
        if ( !class_exists( $controller, false ) ) {
            throw new CoolException( CoolException::ERR_SYSTEM, 'Controller has not Class ' . $controller );
        }
        // 检查控制器下是否存在方法
        if ( !method_exists( $controller, $action ) ) {
            throw new CoolException( CoolException::ERR_SYSTEM, 'Controller ' . $controller . ' has not method ' . $action );
        }
        // call function
        $obj = new $controller();
        // 检查是否有uri参数
        if ( isset( $route_result[3] ) ) {
            $obj->params = $route_result[3];
        }
        $obj->before( $controller, $action );
        $obj->$action ();
        $obj->after( $controller, $action );
    }

    /**
     * 错误处理函数
     *
     * @param int    $err_code   错误级别
     * @param string $err_msg    错误信息
     * @param string $err_script 错误所在文件名
     * @param int    $err_line   错误所在行号
     */
    public static function error_handler ( $err_code, $err_msg, $err_script, $err_line ) {
        $data_arr = array(
            'err_code'   => $err_code,
            'err_msg'    => $err_msg,
            'err_script' => $err_script,
            'err_line'   => $err_line,
        );
        throw new CoolException( CoolException::ERR_RUNTIME_ERROR, 'runtime error(%s)', json_encode( $data_arr ) );
    }

    /**
     * start session
     *
     * @return Session
     */
    public static function session ( $prefix = NULL ) {
        $prefix = empty ( $prefix ) ? 'COOLPHP' : $prefix;
        if (empty ( self::$_session  ) && Cool::$GC ['session'] ['start'] == true) {
            self::load_sys ( 'Session' );
            self::$_session = new Session ( $prefix );
        }
        return self::$_session;
    }

    /**
     * 初始化Helper下面的对象
     *
     * @return $object
     */
    public static function helper ( ) {
        // 获取传入来的参数
        $arguments = func_get_args ();
        // 第一个参数为需要加载的ClassName
        $class_name = array_shift ( $arguments );
        // 没有初始化时 初始化
        if (empty ( self::$_helper [$class_name] )) {
            self::load_sys ( $class_name );
            $object = new ReflectionClass ( $class_name );
            self::$_helper [$class_name] = $object->newInstanceArgs ( $arguments );
        }
        return self::$_helper [$class_name];
    }

    /**
     * 自动加载框架必须的核心Class
     *
     * @return void
     */
    private static function _load_core () {
        $class = array(
            'Router'         => 'core/',
            'CoolController' => 'core/',
            'CoolException'  => 'core/',
        );
        foreach ( $class as $name => $path ) {
            self::load( $name, SYS_PATH . $path );
        }
        // controller
        Cool::auto_load( 'Controller', 'controller' );
    }

    /**
     * 加载applicate 下面的脚本文件
     *
     * @param        $file
     * @param string $type
     */
    public static function auto_load ( $file, $type = 'controller' ) {
        self::load( $file, APP_PATH . $type . '/' );
    }

    /**
     * 加载一个指定的模型
     *
     * @param $model
     * @return mixed
     * @throws CoolException or return model object
     */
    public static function model ( $model ) {
        // 先加载系统CoolModel
        self::load ( 'CoolModel', SYS_PATH . 'core/' );
        // 再加载需要的Model
        $model = $model . 'Model';
        Cool::auto_load ( $model, 'model' );
        if (! class_exists ( $model )) {
            throw new CoolException ( CoolException::ERR_SYSTEM, 'Model Class:' . $model . 'Model is Not Found!' );
        }
        return new $model ();
    }

    /**
     * 加载指定位置的Class并初始化
     * @param unknown $class
     * @param unknown $folder
     * @throws CoolException
     */
    public static function vendor( ){
            // 获取传入来的参数
        $args = func_get_args ();
        if (count ( $args ) < 2) {
            throw new CoolException ( CoolException::ERR_SYSTEM, 'args is not enought!' );
        }
        // 没有初始化时 初始化
        $class = $args [0];
        $folder = $args [1];
        if (empty ( self::$_helper [$folder . '/' . $class] )) {
            Cool::auto_load ( $class, $folder );
            if (! class_exists ( $class )) {
                throw new CoolException ( CoolException::ERR_SYSTEM, 'Class:' . $folder . '/' . $class . ' is Not Found!' );
            }
            $object = new ReflectionClass ( $class );
            unset($args[0], $args[1]);
            self::$_helper [$folder . '/' . $class] = $object->newInstanceArgs ( $args );
        }
        return self::$_helper [$folder . '/' . $class];
    }

    /**
     * 加载一个指定的 Controller
     * @param unknown $controller
     * @throws CoolException
     * @return unknown
     */
    public static function controller($controller){
        $controller = $controller . 'Controller';
        Cool::auto_load($controller, 'controller');
        if (!class_exists($controller)) {
            throw new CoolException (CoolException::ERR_SYSTEM, 'Controller Class:'. $controller.'Controller is Not Found!');
        }
        return new $controller;
    }

    /**
     * Imports the definition of class(es) and tries to create an object/a list of objects of the class.
     *
     * @param string|array $class_name Name(s) of the class to be imported
     * @param string       $path       Path to the class file
     * @return mixed returns NULL by default.
     */
    protected static function load ( $class_name, $path ) {
        if (is_file ( $path . $class_name . '.php' ) && ! isset ( self::$_loaded [$path . $class_name] )) {
            include_once $path . $class_name . '.php';
        } else {
            throw new CoolException( CoolException::ERR_SYSTEM, 'Not Found File :' . $path . $class_name . '.php' );
        }
    }

    /**
     * 加载指定的system下面的类文件
     *
     * @return void
     */
    public static function load_sys ( $cls_name ) {
        $class = array(
            // core
            'Session'      => 'core/',
            'RedisDriver' => 'core/Session/',
            'DatabaseDriver' => 'core/Session/',

            // mysql
            'MySQLHelper'  => 'database/mysql/',
            'MySQLiHelper' => 'database/mysql/',
            'PDOHelper'    => 'database/mysql/',
            // redis
            'RedisHelper'  => 'database/redis/',
            // helper
            'File'     => 'helper/',
            'CSV'          => 'helper/',
            'Http'         => 'helper/',
            'Log'          => 'helper/',
            'Cookie'       => 'helper/',
        );

        // load file
        if ( is_array( $cls_name ) ) {
            foreach ( $cls_name as $name ) {
                if ( !isset( $class[$name] ) ) {
                    throw new CoolException( CoolException::ERR_SYSTEM, $name . 'is Not Found in setting' );
                }
                self::load( $name, SYS_PATH . $class[$name] );
            }
        } else {
            if ( !isset( $class[$cls_name] ) ) {
                throw new CoolException( CoolException::ERR_SYSTEM, $cls_name . 'is Not Found in setting' );
            }
            self::load( $cls_name, SYS_PATH . $class[$cls_name] );
        }
    }
}
/* End of file Cool.php */