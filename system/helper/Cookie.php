<?php

/**
 * Cookie Class
 * Get Set Delete Update Cookie
 *
 * @package     CoolPHP
 * @subpackage  setting
 * @category    helper
 * @author      Intril.Leng <jj.comeback@gmail.com>
 */

class Cookie {

    /**
     * cookie前缀
     * @var unknown
     */
    private $_prefix;

    /**
     * Cookie有效期
     * @var unknown
     */
    private $_expire;

    /**
     * Cookie有效域名
     * @var unknown
     */
    private $_domain;

    /**
     * Cookie路径
     * @var unknown
     */
    private $_path;

    /**
     * Cookie安全传输
     * @var unknown
     */
    private $_secure;

    /**
     * 加密串
     * @var unknown
     */
    private $_secure_key;

    /**
     * Cookie httponly设置
     * @var unknown
     */
    private $_httponly;

    /**
     * 初始化Cookie配置
     * @param string $config
     */
    public function __construct ( $config = NULL ) {
        $this->_prefix = isset ( $config ['prefix'] ) ? $config ['prefix'] : 'CL:';
        $this->_expire = isset ( $config ['expire'] ) ? $config ['expire'] : 0;
        $this->_domain = isset ( $config ['domain'] ) ? $config ['domain'] : '';
        $this->_path = isset ( $config ['path'] ) ? $config ['path'] : '/';
        $this->_secure = isset ( $config ['secure'] ) ? $config ['secure'] : FALSE;
        $this->_secure_key = isset ( $config ['secure_key'] ) ? $config ['secure_key'] : 'CoolPHP';
        $this->_httponly = isset ( $config ['httponly'] ) ? $config ['httponly'] : TRUE;
        if ($this->_httponly == TRUE ){
            ini_set ( "session.cookie_httponly", 1 );
        }
    }

    /**
     * 设置cookie
     * @param String $name cookie name
     * @param mixed $value cookie value 可以是字符串,数组,对象等
     * @param int $expire  过期时间
     */
    public function set ( $name, $value, $expire = 0 ) {
        $cookie_name = $this->get_name ( $name );
        $cookie_expire = time () + ($expire ? $expire : $this->_expire);
        $cookie_value = $this->pack ( $value, $cookie_expire );
        $cookie_value = $this->authcode ( $cookie_value, 'ENCODE', $this->_securekey );
        if ($cookie_name && $cookie_value && $cookie_expire) {
            setcookie ( $cookie_name, $cookie_value, $cookie_expire, $this->_path, $this->_domain, $this->_secure, $this->_httponly );
        }
    }

    /**
     * 读取cookie
     * @param  String $name   cookie name
     * @return mixed          cookie value
     */
    public function get ( $name ) {
        $cookie_name = $this->get_name ( $name );
        if (isset ( $_COOKIE [$cookie_name] )) {
            $cookie_value = $this->authcode ( $_COOKIE [$cookie_name], 'DECODE', $this->_secure_key );
            $cookie_value = $this->unpack ( $cookie_value );
            return isset ( $cookie_value [0] ) ? $cookie_value [0] : null;
        } else {
            return null;
        }
    }

    /**
     * 更新cookie,只更新内容,如需要更新过期时间请使用set方法
     * @param String $name cookie name
     * @param mixed $value cookie value
     * @return boolean
     */
    public function update ( $name, $value ) {
        $cookie_name = $this->get_name ( $name );
        if (isset ( $_COOKIE [$cookie_name] )) {
            $old_cookie_value = $this->authcode ( $_COOKIE [$cookie_name], 'DECODE', $this->_secure_key );
            $old_cookie_value = $this->unpack ( $old_cookie_value );
            if (isset ( $old_cookie_value [1] ) && $old_cookie_vlaue [1] > 0) { // 获取之前的过期时间
                $cookie_expire = $old_cookie_value [1];
                // 更新数据
                $cookie_value = $this->pack ( $value, $cookie_expire );
                $cookie_value = $this->authcode ( $cookie_value, 'ENCODE', $this->_secure_key );
                if ($cookie_name && $cookie_value && $cookie_expire) {
                    setcookie ( $cookie_name, $cookie_value, $cookie_expire, $this->_path, $this->_domain, $this->_secure, $this->_httponly );
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 清除cookie
     *
     * @param String $name cookie name
     */
    public function clear ( $name ) {
        $cookie_name = $this->get_name ( $name );
        setcookie ( $cookie_name, '', time () - 3600, $this->_path, $this->_domain, $this->_secure, $this->_httponly );
        unset ( $_COOKIE [$cookie_name] );
    }

    /**
     * 获取cookie name
     * @param String $name
     * @return String
     */
    private function get_name ( $name ) {
        return $this->_prefix ? $this->_prefix . '_' . $name : $name;
    }

    /**
     * pack
     * @param Mixed $data 数据
     * @param int $expire 过期时间 用于判断
     * @return
     *
     */
    private function pack ( $data, $expire ) {
        if ($data === '') {
            return '';
        }
        $cookie_data = array ();
        $cookie_data ['value'] = $data;
        $cookie_data ['expire'] = $expire;
        return json_encode ( $cookie_data );
    }

    /**
     * unpack
     * @param Mixed $data 数据
     * @return array(数据,过期时间)
     */
    private function unpack ( $data ) {
        if ($data === '') {
            return array ( '', 0 );
        }
        $cookie_data = json_decode ( $data, true );
        if (isset ( $cookie_data ['value'] ) && isset ( $cookie_data ['expire'] )) {
            if (time () < $cookie_data ['expire']) { // 未过期
                return array ( $cookie_data ['value'], $cookie_data ['expire'] );
            }
        }
        return array ( '', 0 );
    }

    /**
     * 加密/解密数据
     * @param String $str 原文或密文
     * @param String $operation ENCODE or DECODE
     * @return String 根据设置返回明文活密文
     */
    private function authcode ( $string, $operation = 'DECODE' ) {
        $ckey_length = 4; // 随机密钥长度 取值 0-32;
        $key = $this->_secure_key;

        $key = md5 ( $key );
        $keya = md5 ( substr ( $key, 0, 16 ) );
        $keyb = md5 ( substr ( $key, 16, 16 ) );
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr ( $string, 0, $ckey_length ) : substr ( md5 ( microtime () ), - $ckey_length )) : '';

        $cryptkey = $keya . md5 ( $keya . $keyc );
        $key_length = strlen ( $cryptkey );

        $string = $operation == 'DECODE' ? base64_decode ( substr ( $string, $ckey_length ) ) : sprintf ( '%010d', 0 ) . substr ( md5 ( $string . $keyb ), 0, 16 ) . $string;
        $string_length = strlen ( $string );

        $result = '';
        $box = range ( 0, 255 );

        $rndkey = array ();
        for($i = 0; $i <= 255; $i ++) {
            $rndkey [$i] = ord ( $cryptkey [$i % $key_length] );
        }

        for($j = $i = 0; $i < 256; $i ++) {
            $j = ($j + $box [$i] + $rndkey [$i]) % 256;
            $tmp = $box [$i];
            $box [$i] = $box [$j];
            $box [$j] = $tmp;
        }

        for($a = $j = $i = 0; $i < $string_length; $i ++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box [$a]) % 256;
            $tmp = $box [$a];
            $box [$a] = $box [$j];
            $box [$j] = $tmp;
            $result .= chr ( ord ( $string [$i] ) ^ ($box [($box [$a] + $box [$j]) % 256]) );
        }

        if ($operation == 'DECODE') {
            if ((substr ( $result, 0, 10 ) == 0 || substr ( $result, 0, 10 ) - time () > 0) && substr ( $result, 10, 16 ) == substr ( md5 ( substr ( $result, 26 ) . $keyb ), 0, 16 )) {
                return substr ( $result, 26 );
            } else {
                return '';
            }
        } else {
            return $keyc . str_replace ( '=', '', base64_encode ( $result ) );
        }
    }
}