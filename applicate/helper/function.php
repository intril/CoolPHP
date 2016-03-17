<?php

/**
 * 数据操作和处理相关的Helper方法
 *
 * @author      Intril.Leng <jj.comeback@gmail.com>
 * @Date         2015年8月7日
 * @Time         下午4:06:48
 */

/**
 * clear_html函数用于过滤标签，输出没有html的干净的文本
 * @param str $text 文本内容
 * @param string $allow_tags 允许的HTML标签
 * @return string 处理后内容
 */
function clear_html ( $text, $allow_tags = "" ) {
    $text = nl2br ( $text );
    $text = html_entity_decode ( $text, ENT_QUOTES, 'UTF-8' );
    $text = strip_tags ( $text, $allow_tags );
    $text = addslashes ( $text );
    $text = trim ( $text );
    return $text;
}

/**
 * 以二维数组的某一个Key的值作为新Key
 *
 * @param array $array
 * @param string $key
 * @return array
 */
function array_change_key ( $array, $key = 'id', $val = null ) {
    $return = array ();
    foreach ( $array as $value ) {
        if ( array_key_exists ( $key, $value )) {
            if (array_key_exists ( $val, $value )) {
                $return [$value [$key]] = $value[$val];
            } else {
                $return [$value [$key]] = $value;
            }
        }
    }
    return ( array ) $return;
}

/**
 * 获取输入参数 支持过滤和默认值
 * 使用方法:
 * <code>
 * fetch_val('post.name','','htmlspecialchars'); 获取$_POST['name']
 * fetch_val('get.'); 获取$_GET
 * fetch_val('input.'); 获取$_INPUT
 * fetch_val('request.'); 获取$_REQUEST
 * </code>
 * @param string $name 变量的名称 支持指定类型
 * @param mixed $default 不存在的时候默认值
 * @param mixed $filter 参数过滤方法 多个过滤方法时","分开
 * @return mixed
 */
function fetch_val ( $name, $default = NULL, $filter = 'clear_html' ) {
    if (strpos ( $name, '.' )) {
        list ( $method, $name ) = explode ( '.', $name, 2 );
    } else { // 默认为自动判断
        $method = 'request';
    }
    switch (strtolower ( $method )) {
        case 'get' :
            $input = & $_GET;
            break;
        case 'post' :
            $input = & $_POST;
            break;
        case 'put' :
            parse_str ( file_get_contents ( 'php://input' ), $_PUT );
            $input = $_PUT;
            break;
        case 'request' :
            $input = & $_REQUEST;
            break;
    }
    if ($name == null) {
        $data = $input;
    } else {
        $data = isset($input[$name]) ? $input[$name] : $default;
    }
    if ($filter) {
        $filter = explode ( ',', $filter );
        foreach ( $filter as $ft ) {
            if (function_exists ( $ft )) {
                $data = is_array ( $data ) ? array_map_recursive ( $ft, $data ) : $ft ( $data ); // 参数过滤
            }
        }
    }
    is_array ( $data ) && array_walk_recursive ( $data, 'filter_sql_key_words' );
    return $data;
}

/**
 * 过滤查询特殊字符
 *
 * @param sql $value
 */
function filter_sql_key_words ( &$value ) {
    // TODO 其他安全过滤

    // 过滤查询特殊字符
    if (preg_match ( '/^(EXP|NEQ|GT|EGT|LT|ELT|OR|XOR|LIKE|NOTLIKE|NOT BETWEEN|NOTBETWEEN|BETWEEN|NOTIN|NOT IN|IN)$/i', $value )) {
        $value .= ' ';
    }
}

/**
 * 遍历处理数组
 *
 * @param unknown $filter
 * @param unknown $data
 * @return multitype:NULL
 */
function array_map_recursive ( $filter, $data ) {
    $result = array ();
    foreach ( $data as $key => $val ) {
        $result [$key] = is_array ( $val ) ? array_map_recursive ( $filter, $val ) : call_user_func ( $filter, $val );
    }
    return $result;
}

/**
 * 数组排序
 *
 * @param unknown $array
 * @param string $key
 * @return multitype:
 */
function sort_array ( $array, $key = 'id' ) {
    if (! is_array ( $array )) {
        return array ();
    }
    $sorts = array();
    foreach ($array as $arr) {
        $sorts[] = $arr[$key];
    }
    array_multisort($array, SORT_ASC, $sorts);
    return $array;
}