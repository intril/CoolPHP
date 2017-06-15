<?php

/**
 * Http Curl helper
 * create a http request that by curl mod
 *
 * @package     CoolPHP
 * @subpackage  setting
 * @category    helper
 * @author      Intril.Leng <jj.comeback@gmail.com>
 */
class Http {

    /**
     * 执行GET请求
     * @param string $url 请求URL
     * @param array/string $data_arr 数据数组
     * @param array $opt_arr Curl中自定义的curl_setopt_array
     * @return string
     */
    public static function get ( $url, $data_arr = array(), $opt_arr = array() ) {
        $query_str = '';
        if (is_array ( $data_arr ) && ! empty ( $data_arr )) {
            $query_str = urldecode(http_build_query ( $data_arr ));
        }
        if (! empty ( $query_str )) {
            $pos = strrpos ( $url, '?' );
            if ($pos === FALSE) {
                $url .= '?';
            } else if ($pos !== (strlen ( $url ) - 1)) {
                $url .= '&';
            }
            $url .= $query_str;
        }
        return self::request('GET', $url, array(), $opt_arr);
    }

    /**
     * 执行POST请求
     * @param string $url 请求URL
     * @param array/string $data_arr 数据数组
     * @param array $opt_arr Curl中自定义的curl_setopt_array
     * @return string
     */
    public static function post ( $url, $data_arr = array(), $opt_arr = array(), $header_arr = array() ) {
        return self::request ( 'POST', $url, $data_arr, $opt_arr, $header_arr );
    }

    /**
     * 执行HTTP请求
     * @param string $method 请求方法
     * @param string $url 请求URL
     * @param array/string $data_arr 数据数组
     * @param array $opt_arr Curl中自定义的curl_setopt_array
     * @return string
     */
    public static function request ( $method, $url, $data_arr = array(), $opt_arr = array(), $header_arr = array() ) {
        $ch = curl_init();
        $option_arr = array(
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_CONNECTTIMEOUT_MS => 2000,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_TIMEOUT_MS => 3000,
            CURLOPT_BINARYTRANSFER => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_FOLLOWLOCATION => TRUE,
            CURLOPT_MAXCONNECTS => 1000,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_URL => $url,
        );
        if (strtoupper($method) == 'POST'){
            $option_arr[CURLOPT_POST] = TRUE;
            $option_arr[CURLOPT_POSTFIELDS] = $data_arr;
        }
        if (! empty ( $opt_arr )) {
            $option_arr = array_merge ( $option_arr, $opt_arr );
        }
        if (! empty ( $header_arr )) {
            curl_setopt ( $ch, CURLOPT_HTTPHEADER, $header_arr ); // 设置HTTP头
        }
        curl_setopt_array($ch, $option_arr);
        $response_str = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error_no = curl_errno($ch);
        curl_close($ch);
        return array( 'http_code' => $http_code, 'error_no' => $error_no, 'response' => $response_str );
    }

}
