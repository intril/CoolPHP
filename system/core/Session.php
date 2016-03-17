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
     * Namespace - Name of Cool session namespace
     *
     * @var string
     */
    protected $_namespace = "Default";

    /**
     * Variable that defines if session started
     *
     * @var boolean
     */
    protected $_session_started = false;

    /**
     * Constructor - returns an instance object of the session that is named by namespace name
     *
     * @param string $namespace - Name of session namespace
     * @param string $sessionId - Optional param for setting session id
     * @return void
     *
     * <code>
     * $session = new Session('mywebsite', $mySessionId)
     * </code>
     */
    public function __construct($namespace = 'Default', $session_id = null) {
        // should not be empty
        if ($namespace === '') {
            throw new CoolException ( 'Namespace cant be empty string.' );
        }
        // should not start with underscore
        if ($namespace [0] == "_") {
            throw new CoolException ( 'You cannot start session with underscore.' );
        }
        // should not start with numbers
        if (preg_match ( '#(^[0-9])#i', $namespace [0] )) {
            throw new CoolException ( 'Session should not start with numbers.' );
        }

        $this->_namespace = $namespace;
        if ($session_id != null) {
            $this->set_id ( $session_id );
        }
        $this->start ();
    }

    /**
     * Start session
     *
     * @return void
     */
    public function start() {
        if ($this->_session_started == true) {
            return;
        }
        if (! session_id ()) {
            session_start ();
        }
        $_SESSION [$this->_namespace] ['session_id'] = $this->get_id ();
        $this->_session_started = true;
    }

    /**
     * check if session start
     *
     * @return boolean
     */
    public function is_start(){
        if (isset($_SESSION)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * Keeping a session open for a long operation causes subsequent requests from
     * a user of that session having to wait for session's file to be freed.
     * Therefore if you do not need the session anymore you can call this function
     * to store the session and close the lock it has
     *
     * @return void
     */
    public function stop() {
        if ($this->_session_started == true) {
            session_write_close ();
            $this->_session_started = false;
        }
    }

    /**
     * Set variable into session
     *
     * @param string $name Name of key
     * @param mixed $value Value for keyname ($name)
     * @return void
     */
    public function set_data($name, $value) {
        if ($name === "") {
            throw new CoolException ( "Keyname should not be empty string!" );
        }
        if (! $this->_session_started) {
            throw new CoolException ( "Session not started." );
        }
        if ($value === null) {
            unset ( $_SESSION [$this->_namespace] [$name] );
        } else {
            $_SESSION [$this->_namespace] [$name] = $value;
        }
    }

    /**
     * Get variable from namespace by reference
     *
     * @param string $name if that variable doesnt exist returns null
     *
     * @return mixed
     */
    public function get_data($name) {
        if (! $this->_session_started) {
            throw new CoolException ( "Session not started, use Session::start()" );
        }
        if ($name == '') {
            throw new CoolException ( "Name should not be empty" );
        }
        if (! isset ( $_SESSION [$this->_namespace] [$name] )) {
            return null;
        } else {
            return $_SESSION [$this->_namespace] [$name];
        }
    }

    /**
     * Get all variables from namespace in a array
     *
     * @return array Variables from session
     */
    public function get_all() {
        if (isset ( $_SESSION [$this->_namespace] ) && is_array ( $_SESSION [$this->_namespace] )) {
            return $_SESSION [$this->_namespace];
        } else {
            return array ();
        }
    }

    /**
     * Destroy all session data
     *
     * @throws DooSessionException
     * @return void
     */
    public function destroy() {
        if (! $this->_session_started) {
            throw new CoolException ( "Session not started." );
        }
        if (isset ( $_SESSION [$this->_namespace] )){
            unset ( $_SESSION [$this->_namespace] );
        }
        session_destroy ();
        $this->_session_started = false;
    }

    /**
     *  Unset whole session namespace or some value inside it
     *
     *  @param string $name If name is provided it will unset some value in session namespace
     *  if not it will unset session.
     */
    public function namespace_unset($name = null) {
        if (! $this->_session_started) {
            throw new CoolException ( "Session not started, use Session::start()" );
        }
        if (empty ( $name )) {
            unset ( $_SESSION [$this->_namespace] );
        } else {
            unset ( $_SESSION [$this->_namespace] [$name] );
        }
    }

    /**
     * Get session id
     * @return session_id
     */
    public function get_id() {
        if (! isset ( $_SESSION )) {
            throw new CoolException ( "Session not started, use Session::start()" );
        }
        return session_id ();
    }

    /**
     * Sets session id
     *
     * @param $id session identifier
     */
    public function set_id($id) {
        if (isset ( $_SESSION )) {
            throw new CoolException ( "Session is already started, id must be set before." );
        }
        if (! is_string ( $id ) || $id === '') {
            throw new CoolException ( "Session id must be string and cannot be empty." );
        }
        if (headers_sent ( $filename, $linenum )) {
            throw new CoolException ( "Headers already sent, output started at " . $filename . " on line " . $linenum );
        }
        session_id ( $id );
    }

    /**
     * Check if CoolSession variable is stored
     * @return bool
     */
    public function has($name) {
        if (!$this->_session_started) {
            throw new CoolException("Session not started, use Session::start()");
        }
        if (isset($_SESSION[$this->_namespace][$name])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Unset CoolSession variable
     * @return bool
     */
    public function remove($name) {
        if (! $this->_session_started) {
            throw new CoolException ( "Session not started, use Session::start()" );
        }
        if (isset ( $_SESSION [$this->_namespace] [$name] )) {
            unset ( $_SESSION [$this->_namespace] [$name] );
            return true;
        }
        return false;
    }
}
/* End of file Session.php */