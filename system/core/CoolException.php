<?php

/**
 * Exception class
 * user defeine exception class
 *
 * @package     CoolPHP
 * @subpackage  setting
 * @category    helper
 * @author      Intril.Leng <jj.comeback@gmail.com>
 */
class CoolException extends Exception {
    /**
     * 未定义
     * @var int
     */
    const ERR_UNDEFINED = 10000;

    /**
     * 操作成功
     * @var int
     */
    const ERR_SUCCESS = 0;

    /**
     * 系统错误
     * @var int
     */
    const ERR_SYSTEM = 10001;

    /**
     * 运行时错误
     * @var int
     */
    const ERR_RUNTIME_ERROR = 10002;

    /**
     * SESSION过期(未登录或登录超时)
     * @var int
     */
    const ERR_SESSION_EXPIRED = 10003;

    /**
     * 无效请求
     * @var int
     */
    const ERR_INVALID_REQUEST = 10004;

    /**
     * 没有访问权限
     * @var int
     */
    const ERR_NO_PRIVILEGE = 10005;

    /**
     * CURL操作错误
     * @link http://curl.haxx.se/libcurl/c/libcurl-errors.html
     * @var int
     */
    const ERR_CURL = 10006;

    /**
     * 控制器不存在
     * @var int
     */
    const ERR_CONTROLLER_NOT_EXISTS = 10007;

    /**
     * MYSQLI错误
     * @var int
     */
    const ERR_MYSQLI = 10008;

    /**
     * PDO错误
     * @var int
     */
    const ERR_PDO = 10009;

    /**
     * MYSQL错误
     * @var int
     */
    const ERR_MYSQL = 10010;

    /**
     * 构造函数
     * @param int $code 错误编号
     * @param string $format 错误消息格式字符串
     * @param mixed $arg1,$arg2,... 可变参数(格式参数)
     */
    public function __construct($code = 10000, $format = 'Undefined error!') {
        // 获取参数数组
        $args = func_get_args();
        // 移去第一个参数
        array_shift($args);
        // 移去第二个参数
        array_shift($args);
        if (!empty($args)) {
            $msg = vsprintf($format, $args);
        } else {
            $msg = $format;
        }
        // 调用父类的构造函数
        parent::__construct($msg, $code);
    }

    /**
     * 获取所有错误代码
     * @return array
     */
    public static function get_all_error_code() {
        $reflectionClass = new ReflectionClass(__CLASS__);
        $constArr = $reflectionClass->getConstants();
        return array_flip($constArr);
    }
}
/* End of file CoolException.php */