<?php

/**
 * CoolPHP Session Database Driver
 *
 * @package     CoolPHP
 * @subpackage  setting
 * @category    core
 * @author      Intril.Leng <jj.comeback@gmail.com>
 */
/**
 * 数据库方式Session驱动
 *    CREATE TABLE think_session (
 *      session_id varchar(255) NOT NULL,
 *      session_expire int(11) NOT NULL,
 *      session_data blob,
 *      UNIQUE KEY `session_id` (`session_id`)
 *    );
 */
class DatabaseDriver {

    /**
     * configure
     * @var unknown
     */
    private $config;

    /**
     * Session有效时间
     */
    private $life_time;

    /**
     * 数据库句柄
     */
    private $hander;

    /**
     * Class constructor
     * @param array $params Configuration parameters
     * @return void
     */
    public function __construct ( $prefix = NULL ) {
        $this->config = Cool::$GC['session'];
        $this->config ['prefix'] = $prefix ? $prefix : NULL;
        $this->life_time = $this->config ['expires'] ? $this->config ['expires'] : ini_get ( 'session.gc_maxlifetime' );
        if (empty ( $this->config ['path'] )) {
            throw new CoolException ( CoolException::ERR_SYSTEM, 'Session: No Database save path configured.' );
        }
        if (! isset ( $this->handler )) {
            $this->hander = Cool::model ( ucfirst($this->config ['path']) );
        }
    }

    /**
     * 打开Session
     * @param unknown $save_path
     * @param unknown $sess_name
     */
    public function open ( $save_path, $sess_name ) {
        return true;
    }

    /**
     * 关闭Session
     * @access public
     */
    public function close ( ) {
        $this->gc ( $this->life_time );
        return $this->hander->disconnect ( $this->config ['path'] );
    }

    /**
     * 读取Session
     *
     * @access public
     * @param string $sessID
     */
    public function read ( $sess_id ) {
        $where = array (
            'session_id' => $sess_id, 'session_expire' => array ( '>', time () )
        );
        $return = $this->hander->where ( $where )->find_one ();
        if (empty ( $return ['session_data'] )) {
            return '';
        } else {
            return $return ['session_data'];
        }
    }

    /**
     * 写入Session
     * @access public
     * @param string $sessID
     * @param String $sessData
     */
    public function write ( $sess_id, $sess_data ) {
        $expire = time () + $this->life_time;
        $data = array (
            'session_id' => $sess_id, 'session_expire' => $expire, 'session_data' => $sess_data
        );
        $return = $this->hander->data ( $data )->replace ();
        return ( boolean ) $return;
    }

    /**
     * 删除Session
     * @access public
     * @param string $sessID
     */
    public function destroy ( $sess_id ) {
        $where = array (
            'session_id' => $sess_id
        );
        $return = $this->hander->where ( $where )->delete ();
        return $return;
    }

    /**
     * Session 垃圾回收
     * @access public
     * @param string $sessMaxLifeTime
     */
    public function gc ( $sess_max_life_time ) {
        $where = array (
                'session_expire' => array( '<', time() )
        );
        $return = $this->hander->where ( $where )->delete ();
        return $return;
    }
}