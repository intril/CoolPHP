<?php

/**
 * Router class
 *
 * @package     CoolPHP
 * @subpackage  setting
 * @category    core
 * @author      Intril.Leng <jj.comeback@gmail.com>
 */
class Router {
    /**
     * 解析访问进来的URI. 返回App需要访问的目录 控制器 和 方法
     *
     * @param array $routeArr Routes defined in <i>routes.conf.php</i>
     * @return array returns an array consist of the Controller class, action method and parameters of the route
     */
    public static function parse ( $route_arr ) {
        $route_result = array();
        $request_uri = self::_get_request_uri();
        if ( $request_uri == '' || $request_uri == '/' ) {
            if ( isset( $route_arr['/'] ) ) {
                return $route_arr['/'];
            } else {
                throw new CoolException( CoolException::ERR_SYSTEM, 'Please Set default router in routes.conf.php' );
            }
        }
        if ( Cool::$GC['auto_route'] == true ) { // 自动路由时
            $folder = '/';
            $uri_arr = explode( '/', trim( $request_uri, '/' ) );
            if ( count( $uri_arr ) <= 2 ) {
                $route_result = array( $uri_arr[0], isset($uri_arr[1]) ? $uri_arr[1] : 'index' );
            } else {
                $controller_path = APP_PATH . 'controller' . DS;
                foreach ( $uri_arr as $value ) {
                    $controller_path .= $value . '/';
                    if ( is_dir( $controller_path ) ) {
                        $folder .= $value . '/';
                    } else {
                        if ( count( $route_result ) < 2 ) {
                            $route_result[] = $value;
                        }
                    }
                }
            }
            if ($folder != '/'){
                $route_result[2] = $folder;
            }
            $route_result[3] = substr($request_uri, strlen($folder . $route_result[0] .'/'. $route_result[1]));
            $route_result[0] = ucfirst( $route_result[0] ) . 'Controller';
        } else { // 按route.conf.php里面绝对路由时
            if ( isset( $route_arr[$request_uri] ) ) {
                $route_result = $route_arr[$request_uri];
            } else {
                throw new CoolException( CoolException::ERR_INVALID_REQUEST, 'router is not found in routes.conf.php ' . $request_uri );
            }
        }
        return $route_result;
    }

    /**
     * 获取当前请求时的URI
     * eg. /test/index?id=1
     *
     * @return string $request_uri eg. /test/index?id=1&name=coolphp
     */
    private static function _get_request_uri () {
        $request_uri = '';
        if ( isset( $_SERVER['REQUEST_URI'] ) ) {
            $request_uri = $_SERVER['REQUEST_URI'];
        } else if ( isset( $_SERVER['HTTP_X_REWRITE_URL'] ) ) { // check this first so IIS will catch
            $request_uri = $_SERVER['HTTP_X_REWRITE_URL'];
        } else if ( isset( $_SERVER['REDIRECT_URL'] ) ) { // Check if using mod_rewrite
            $request_uri = $_SERVER['REDIRECT_URL'];
        } else if ( isset( $_SERVER['ORIG_PATH_INFO'] ) ) { // IIS 5.0, PHP as CGI
            $request_uri = $_SERVER['ORIG_PATH_INFO'];
            if ( !empty( $_SERVER['QUERY_STRING'] ) ) {
                $request_uri .= '?' . $_SERVER['QUERY_STRING'];
            }
        }
        // Remove get part of url (eg example.com/test/?foo=bar trimed to example.com/test/)
        if (false !== ($position = strpos($request_uri, '?'))) {
            $request_uri = substr($request_uri, 0, $position);
        }
        // Remove Subfolder
        $request_uri = substr($request_uri, strlen(Cool::$GC['sub_folder']));
        // Remove index.php from URL if it exists
        if (0 === strpos($request_uri, '/index.php')) {
            $request_uri = substr($request_uri, 10);
        }
        return $request_uri;
    }

    /**
     * 获取URI
     *
     * @return string
     */
    public static function current_uri () {
        return self::_get_request_uri();
    }

    /**
     * 根据给的URL指定跳转
     *
     * @param unknown $url
     * @param number $time
     * @param string $msg
     */
    public static function redirect( $url, $msg = '', $time = 0 ) {
        $url = str_replace ( array ( "\n", "\r" ), '', $url );
        if (empty ( $msg ))
            $msg = "系统将在{$time}秒之后自动跳转到{$url}！";
        if (! headers_sent ()) {
            if (0 === $time) {
                header ( 'Location: ' . $url );
            } else {
                header ( "refresh:{$time};url={$url}" );
                echo ($msg);
            }
            exit ();
        } else {
            $str = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
            if ($time != 0){
                $str .= $msg;
            }
            exit ( $str );
        }
    }
}

/* End of file Router.php */