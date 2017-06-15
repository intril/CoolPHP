<?php
/**
 * CoolPHP Session Redis Driver
 *
 * @package     CoolPHP
 * @subpackage  setting
 * @category    core
 * @author      Intril.Leng <jj.comeback@gmail.com>
 */
class RedisDriver {

    /**
     * configure
     * @var unknown
     */
    private $config;

    /**
     * phpRedis instance
     * @var resource
     */
    private $handler;

    /**
     * Class constructor
     * @param array $params Configuration parameters
     * @return void
     */
    public function __construct ( $prefix = NULL ) {
        $this->config = Cool::$GC['session'];
        $this->config ['prefix'] = $prefix ? $prefix . ':' : NULL;
        if (empty ( $this->config ['path'] )) {
            throw new CoolException ( CoolException::ERR_SYSTEM, 'Session: No Redis save path configured.' );
        }
        Cool::load_sys ( 'RedisHelper' );
        if (! isset ( $this->handler )) {
            $this->handler = new RedisHelper ( $this->config ['path'] );
        }
    }

    /**
     * 打开Session
     * @param unknown $savePath
     * @param unknown $sessName
     * @return boolean
     */
    public function open($save_path, $sess_name) {
        return true;
    }

    /**
     * 关闭Session
     * @return boolean
     */
    public function close() {
        $configure = $this->handler->configure();
        if ($configure[$this->config ['path']] == 'pconnect') {
            $this->handler->close();
        }
        return true;
    }

    /**
     * 读取Session
     * @param unknown $sess_id
     */
    public function read ( $sess_id ) {
        $result = $this->handler->get ( $this->config ['prefix'] . $sess_id );
    }

    /**
     * 写入session
     * @param unknown $sess_id
     * @param unknown $sess_data
     * @return boolean|unknown
     */
    public function write ( $sess_id, $sess_data ) {
        if (! $sess_data) {
            return true;
        }
        $expires = $this->config ['expires'];
        $sess_id = $this->config ['prefix'] . $sess_id;
        $result = $this->handler->set ( $sess_id, $sess_data, $expires );
        return (boolean)$result;
    }

    /**
     * 删除Session
     * @param unknown $sessID
     */
    public function destroy ( $sess_id ) {
        return $this->handler->rm ( $this->config ['prefix'] . $sess_id );
    }

    /**
     * Session 垃圾回收
     * @param unknown $sessMaxLifeTime
     * @return boolean
     */
    public function gc ( $sess_max_life_time ) {
        return true;
    }

    /**
     * 打开Session
     * @internal param string $savePath
     * @internal param mixed $sessName
     */
    public function execute() {
        session_set_save_handler(
            array( &$this, 'open' ),
            array( &$this, 'close' ),
            array( &$this, 'read' ),
            array( &$this, 'write' ),
            array( &$this, 'destroy' ),
            array( &$this, 'gc' )
        );
    }

    public function __destruct ( ) {
        $configure = $this->handler->configure();
        if ($configure[$this->config ['path']] == 'pconnect') {
            $this->handler->close ();
        }
        session_write_close ();
    }

}
