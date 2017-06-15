<?php

/**
 * Session class, manage all session options
 *
 * @package     CoolPHP
 * @subpackage  setting
 * @category    core
 * @author      Intril.Leng <jj.comeback@gmail.com>
 */

class Session {

    /**
     * Namespace - Name of Cool session prefix
     * @var string
     */
    private $_prefix = 'COOL';

    /**
     * Variable that defines if session started
     * @var boolean
     */
    private $_started = false;

    /**
     * Constructor - returns an instance object of the session that is named by prefix name
     * @param string $prefix - Name of session prefix
     * @param string $sessionId - Optional param for setting session id
     * @return void
     */
    public function __construct ( $prefix ) {
        // should not start with underscore
        if ($prefix [0] == "_" || preg_match ( '#(^[0-9])#i', $prefix [0] )) {
            throw new CoolException ( CoolException::ERR_SYSTEM, 'You cannot start session with underscore or numbers.' );
        }
        $this->_prefix = $prefix;
        $config = Cool::$GC['session'];
        $this->configure ( $config );
        // 有配置driver时使用对应的库
        if (!empty($config ['driver'])) {
            $class = ucwords ( $config ['driver'] ) . 'Driver';
            Cool::load_sys ( $class );
            $hander = new $class ( $this->_prefix );
            session_set_save_handler(
                array(&$hander,"open"),
                array(&$hander,"close"),
                array(&$hander,"read"),
                array(&$hander,"write"),
                array(&$hander,"destroy"),
                array(&$hander,"gc")
            );
        }
            // // 启动session
        if ($config ['start'] == true) {
            ini_set ( 'session.auto_start', 1 );
            session_start ();
            $this->_started = TRUE;
        }
    }

    /**
     * 初始化Session配置数据
     * @param unknown $configure
     */
    private function configure ( $configure ) {
        ini_set ( 'session.auto_start', 1 );
        isset ( $configure ['name'] ) ? session_name ( $configure ['name'] ) : NULL;
        isset ( $configure ['path'] ) ? session_save_path ( $configure ['path'] ) : NULL;
        isset ( $configure ['domain'] ) ? ini_set ( 'session.cookie_domain', $configure ['domain'] ) : NULL;
        if (isset ( $configure ['expires'] )) {
            ini_set ( 'session.gc_maxlifetime', $configure ['expires'] );
            ini_set ( 'session.cookie_lifetime', $configure ['expires'] );
        }
        if (isset ( $configure ['use_trans_sid'] )) {
            ini_set ( 'session.use_trans_sid', intval ( $configure ['use_trans_sid'] ) );
        }
        if (isset ( $configure ['use_cookies'] )) {
            ini_set ( 'session.use_cookies', intval ( $configure ['use_cookies'] ) );
        }
        isset ( $configure ['cache_limiter'] ) ? session_cache_limiter ( $configure ['cache_limiter'] ) : NULL;
        isset ( $configure ['cache_expire'] ) ? session_cache_expire ( $configure ['cache_expires'] ) : NULL;
    }

    /**
     * Set variable into session
     * @param string $name Name of key
     * @param mixed $value Value for keyname ($name)
     * @return void
     */
    public function set_data ( $name, $value = NULL ) {
        if ($name === "") {
            return false;
        }
        if ($this->_started == FALSE) {
            throw new CoolException ( CoolException::ERR_SYSTEM, 'Session not started, use Session::start().' );
        }
        if ($value === null) {
            unset ( $_SESSION [$this->_prefix] [$name] );
        } else {
            $_SESSION [$this->_prefix] [$name] = $value;
        }
    }

    /**
     * Get variable from prefix by reference
     * @param string $name if that variable doesnt exist returns null
     * @return mixed
     */
    public function get_data ( $name ) {
        if ($name == '') {
            return false;
        }
        if ($this->_started == FALSE) {
            throw new CoolException ( CoolException::ERR_SYSTEM, 'Session not started, use Session::start().' );
        }
        if (! isset ( $_SESSION [$this->_prefix] [$name] )) {
            return null;
        } else {
            return $_SESSION [$this->_prefix] [$name];
        }
    }

    /**
     * Get all variables from prefix in a array
     * @return array Variables from session
     */
    public function get_all ( ) {
        if ($this->_started == FALSE) {
            throw new CoolException ( CoolException::ERR_SYSTEM, 'Session not started, use Session::start().' );
        }
        if (isset ( $_SESSION [$this->_prefix] ) && is_array ( $_SESSION [$this->_prefix] )) {
            return $_SESSION [$this->_prefix];
        } else {
            return array ();
        }
    }

    /**
     * Destroy all session data
     * @throws DooSessionException
     * @return void
     */
    public function destroy ( ) {
        if ($this->_started == FALSE) {
            throw new CoolException ( CoolException::ERR_SYSTEM, 'Session not started, use Session::start().' );
        }
        if (isset ( $_SESSION [$this->_prefix] )) {
            unset ( $_SESSION [$this->_prefix] );
        }
        session_destroy ();
        $this->_started = false;
    }

    /**
     *  Unset whole session prefix or some value inside it
     *  @param string $name If name is provided it will unset some value in session prefix
     *  if not it will unset session.
     */
    public function prefix_unset ( $prefix = null ) {
        if ($this->_started == FALSE) {
            throw new CoolException ( CoolException::ERR_SYSTEM, 'Session not started, use Session::start().' );
        }
        if ($prefix) {
            $this->_prefix = $prefix;
        }
        if (isset ( $_SESSION [$this->_prefix] )) {
            unset ( $_SESSION [$this->_prefix] );
        }
        return true;
    }

    /**
     * Sets session id
     * @param $id session identifier
     */
    public function set_sessid ( $sess_id ) {
        if ($this->_started == FALSE) {
            throw new CoolException ( CoolException::ERR_SYSTEM, 'Session not started, use Session::start().' );
        }
        if (! is_string ( $sess_id ) || $sess_id === '') {
            throw new CoolException ( CoolException::ERR_SYSTEM, 'Session id must be string and cannot be empty.' );
        }
        session_id ( $id );
    }

    /**
     * 获取SessionID
     * @throws CoolException
     */
    public function get_sessid(){
        if ($this->_started == FALSE) {
            throw new CoolException ( CoolException::ERR_SYSTEM, 'Session not started, use Session::start().' );
        }
        return session_id();
    }

    /**
     * Check if CoolSession variable is stored
     * @return bool
     */
    public function exists ( $name ) {
        if ($this->_started == FALSE) {
            throw new CoolException ( CoolException::ERR_SYSTEM, 'Session not started, use Session::start().' );
        }
        if (isset ( $_SESSION [$this->_prefix] [$name] )) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Unset CoolSession variable
     * @return bool
     */
    public function remove ( $name ) {
        if ($this->_started == FALSE) {
            throw new CoolException ( CoolException::ERR_SYSTEM, 'Session not started, use Session::start().' );
        }
        if (isset ( $_SESSION [$this->_prefix] [$name] )) {
            unset ( $_SESSION [$this->_prefix] [$name] );
            return true;
        }
        return false;
    }
}
/* End of file Session.php */