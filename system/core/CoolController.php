<?php

/**
 * Base Controller Class
 * all controller must be to extends this class
 *
 * @package     CoolPHP
 * @subpackage  setting
 * @category    core
 * @author      Intril.Leng <jj.comeback@gmail.com>
 */
abstract class CoolController {

    /**
     * Associative array of the PUT values sent by client.
     *
     * @var array
     */
    protected $puts;

    /**
     * This will be called before the actual action is executed
     */
    public function before ( $resource, $action ) {
        if ( $_SERVER['REQUEST_METHOD'] === 'PUT' ) {
            $this->init_put_vars();
        }
    }

    /**
     * This will be called if the action method returns null or success status(200 to 299 not including 204) after the actual action is executed
     *
     * @param mixed $routeResult The result returned by an action
     */
    public function after ( $resource, $action ) {
        // default nothing
    }

    /**
     * Set PUT request variables in a controller. This method is to be used by the main web app class.
     */
    public function init_put_vars () {
        parse_str( file_get_contents( 'php://input' ), $this->puts );
    }

    /**
     * Get client's IP
     *
     * @return string
     */
    public function client_ip () {
        if ( getenv( 'HTTP_CLIENT_IP' ) && strcasecmp( getenv( 'HTTP_CLIENT_IP' ), 'unknown' ) ) {
            return getenv( 'HTTP_CLIENT_IP' );
        } elseif ( getenv( 'HTTP_X_FORWARDED_FOR' ) && strcasecmp( getenv( 'HTTP_X_FORWARDED_FOR' ), 'unknown' ) ) {
            return getenv( 'HTTP_X_FORWARDED_FOR' );
        } elseif ( getenv( 'REMOTE_ADDR' ) && strcasecmp( getenv( 'REMOTE_ADDR' ), 'unknown' ) ) {
            return getenv( 'REMOTE_ADDR' );
        } elseif ( isset( $_SERVER['REMOTE_ADDR'] ) && $_SERVER['REMOTE_ADDR'] && strcasecmp( $_SERVER['REMOTE_ADDR'], 'unknown' ) ) {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    /**
     * Check if the request is an AJAX request usually sent with JS library such as JQuery/YUI/MooTools
     *
     * @return bool
     */
    public function is_ajax () {
        return ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) === 'xmlhttprequest' );
    }

    /**
     * URL跳转
     *
     * @param string $uri
     * @param string $msg
     * @param number $time
     */
    public function redirect ( $uri = '', $msg = '', $time = 0 ) {
        Router::redirect( $uri, $msg, $time);
    }

    /**
     * 输出JSON数据
     *
     * @param unknown $data
     * @param string $type
     */
    public function to_json($data = array()){
        header('Content-Type:application/json; charset=utf-8');
        if (is_array($data)){
            echo json_encode($data);
        }else{
            echo $data;
        }
        exit();
    }

    /**
     * 显示模板
     * @param unknown $view
     * @param unknown $data
     * @throws CoolException
     */
    public function display ( $view, $data = array() ) {
        $view_path = APP_PATH . 'view/' . $view . '.php';
        if (! is_file ( $view_path )) {
            throw new CoolException ( CoolException::ERR_SYSTEM, 'Not Found View:' . APP_PATH . 'view/' . $view . '.php' );
        }
        include $view_path;
    }
}

/* End of file CoolController.php */