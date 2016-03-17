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
     * @param array $header_arr 请求头信息
     * @param bool $selfHandle 是否手动处理响应(FALSE)
     * @return string
     */
    public static function get($url, $data_arr = array(), $header_arr = array(), $self_handle = FALSE) {
        return self::request('GET', $url, $data_arr, $header_arr, $self_handle);
    }

    /**
     * 执行POST请求
     * @param string $url 请求URL
     * @param array/string $data_arr 数据数组
     * @param array $header_arr 请求头信息
     * @param bool $selfHandle 是否手动处理响应(FALSE)
     * @return string
     */
    public static function post($url, $data_arr = array(), $header_arr = array(), $self_handle = FALSE) {
        return self::request('POST', $url, $data_arr, $header_arr, $self_handle);
    }

    /**
     * 执行HTTP请求
     * @param string $method 请求方法
     * @param string $url 请求URL
     * @param array/string $data_arr 数据数组
     * @param array $header_arr 请求头信息
     * @param bool $selfHandle 是否手动处理响应(FALSE)
     * @return string
     */
    public static function request($method, $url, $data_arr = array(), $header_arr = array(), $self_handle = FALSE) {
        $ch = curl_init();
        $option_arr = array(
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_CONNECTTIMEOUT_MS => 0,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_TIMEOUT_MS => 0,
            CURLOPT_BINARYTRANSFER => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_FOLLOWLOCATION => TRUE,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            //CURLOPT_HEADER => TRUE,
            //CURLOPT_COOKIEFILE => 'cookie.txt',
            //CURLOPT_COOKIEJAR => 'cookie.txt',
        );
        switch (strtoupper($method)) {
            case 'GET':
                $query_str = '';
                if (is_array($data_arr) && !empty($data_arr)) {
                    $query_str = http_build_query($data_arr);
                }
                if (!empty($query_str)) {
                    $pos = strrpos($url, '?');
                    if ($pos === FALSE) {
                        $url .= '?';
                    } else if ($pos !== (strlen($url) - 1)) {
                        $url .= '&';
                    }
                    $url .= $query_str;
                }
                break;
            case 'POST':
                $option_arr[CURLOPT_POST] = TRUE;
                $option_arr[CURLOPT_POSTFIELDS] = $data_arr;
                break;
        }
        $option_arr[CURLOPT_URL] = $url;
        if (!empty($header_arr)) {
            $option_arr[CURLOPT_HTTPHEADER] = $header_arr;
        }
        curl_setopt_array($ch, $option_arr);
        $response_str = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error_no = curl_errno($ch);
        curl_close($ch);
        if ($self_handle) {
            return array(
                'http_code' => $http_code, 'error_no' => $error_no, 'response' => $response_str,
            );
        }
        if ($http_code !== 200 || $error_no !== 0) { // CURL错误
            throw new CoolException ( CoolException::ERR_CURL, 'Curl fail(http_code=%s,error_no=%s)!', $http_code, $error_no );
        }
        return $response_str;
    }

}
