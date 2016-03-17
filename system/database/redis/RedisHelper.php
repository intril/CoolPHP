<?php

/**
 * RedisHelper类
 * @author xianlinli@gmail.com
 */
class RedisHelper {

    /**
     * 配置数组
     * @var array
     */
    protected $conf = array();

    /**
     * 连接超时(秒)
     * @var float
     */
    private $_timeout = 3.0;

    /**
     * 数据库索引
     * @var int
     */
    private $_dbindex = 0;

    /**
     * cache_name
     * @var unknown
     */
    protected $cache_name = NULL;

    /**
     * 辅助类实例数组
     *
     * @var PDOHelper
     */
    private static $_object = array ();

    /**
     * 构造方法
     */
    public function __construct( $cache_name = NULL ) {
        if ($cache_name == NULL) {
            $this->cache_name = Cool::$GC ['default_redis'];
        } else {
            $this->cache_name = $cache_name;
        }
        if (file_exists(APP_PATH . 'config/redis.conf.php')) {
            include APP_PATH . 'config/redis.conf.php';
        }else{
            throw new CoolException ( CoolException::ERR_SYSTEM, 'Redis Config File Is Not Found!' );
        }
        // 检查要连接的redis是否有配置
        if (! isset ( $redis [$this->cache_name] )) {
            throw new CoolException ( CoolException::ERR_SYSTEM, 'No Redis inst key ' . $this->cache_name . ' in redis.conf.php!' );
        }
        // 设置当前连接的redis配置
        $this->conf [$this->cache_name] = $redis [$this->cache_name];
        // set timeout
        if ($this->conf [$this->cache_name] ['timeout'] !== false) {
            $this->_timeout = $this->conf [$this->cache_name] ['timeout'];
        }
        if (! isset ( self::$_object [$this->cache_name] )) {
            if (! extension_loaded ( 'redis' )) {
                throw new CoolException ( CoolException::ERR_SYSTEM, 'Extension(%s) not loaded!', 'redis' );
            }
            try {
                self::$_object [$this->cache_name] = new Redis ();
                $func = $this->conf [$this->cache_name] ['persistent'] ? 'pconnect' : 'connect';
                self::$_object [$this->cache_name]->$func ( $this->conf [$this->cache_name] ['host'], $this->conf [$this->cache_name] ['port'], $this->_timeout );
                if (isset ( $this->conf [$this->cache_name] ['auth'] )) {
                    self::$_object [$this->cache_name]->auth ( $this->conf [$this->cache_name] ['auth'] );
                }
            } catch ( RedisException $e ) {
                throw new CoolException ( CoolException::ERR_SYSTEM, 'Cannot connect to redis server(%s)!', $e->getMessage () );
            }
        }
    }

    /**
     * 动态调用Redis库方法
     * @param $op
     * @param $params
     * @return mixed
     */
    public function exec ( $op, $params ) {
        if ( is_array ( $params ) ) {
            return call_user_func_array ( array( self::$_object[$this->cache_name], $op ), $params );
        } else {
            return call_user_func ( array( self::$_object[$this->cache_name], $op ), $params );
        }
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @return mixed
     */
    public function get ( $name, $expire = 0 ) {
        $value = self::$_object [$this->cache_name]->get ( $name );
        $json_data = json_decode ( $value, true );
        if (is_int ( $expire ) && $expire > 0) {
            self::$_object [$this->cache_name]->expire ( $name, $expire );
        }
        // 检测是否为JSON数据 true 返回JSON解析数组, false返回源数据
        return ($json_data === NULL) ? $value : $json_data;
    }

    /**
     * 写入缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value  存储数据
     * @param integer $expire  有效时间（秒）
     * @return boolean
     */
    public function set ( $name, $value, $expire = 0 ) {
        // 对数组/对象数据进行缓存处理，保证数据完整性
        $value = (is_object ( $value ) || is_array ( $value )) ? json_encode ( $value ) : $value;
        if (is_int ( $expire ) && $expire) {
            $result = self::$_object [$this->cache_name]->setex ( $name, $expire, $value );
        } else {
            $result = self::$_object [$this->cache_name]->set ( $name, $value );
        }
        return $result;
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    public function rm ( $name ) {
        return self::$_object [$this->cache_name]->delete ( $name );
    }

    /**
     * 清除缓存
     * @access public
     * @return boolean
     */
    public function clear() {
        return self::$_object [$this->cache_name]->flushDB();
    }

    /**
     * 写入一个数据到队列中（从右边写入）
     *
     * @param unknown $queue_name
     * @param unknown $value
     * @throws Exception
     * @return boolean|multitype:string |multitype:number unknown
     */
    public function push ( $queue_name, $value ) {
        if (empty ( $value )) {
            return false;
        }
        $value = is_array ( $value ) ? json_encode ( $value ) : $value;
        $write = self::$_object[$this->cache_name]->rPush ( $queue_name, $value );
        // 写入后超出队列长度，进行lTrim删除掉最早进来的队列数据
        if ($write > $this->conf [$this->cache_name] ['max_length']) {
            $length = $write - $host ['max_length'];
            self::$_object[$this->cache_name]->lTrim($key, $length, -1);
            return false;
        }
        return $write;
    }

    /**
     * 取队列数据（从左边取出）
     *
     * @param unknown $queue_name
     * @return boolean|unknown
     */
    public function pop ( $queue_name ) {
        if (empty ( $queue_name )) {
            return false;
        }
        $return = self::$_object [$this->cache_name]->lPop ( $queue_name );
        $result = json_decode ( $return, true );
        if ($result === NULL) {
            return $return;
        } else {
            return $result;
        }
    }

    /**
     * 关闭连接
     */
    public function close ( ) {
        if (self::$_object [$this->cache_name] !== NULL) {
            self::$_object [$this->cache_name]->close ();
            self::$_object [$this->cache_name] = NULL;
        }
    }
}
