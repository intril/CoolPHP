<?php

/**
 * 微信公众平台SDK
 *
 * User: Intril.leng
 * Date: 2016/1/26
 * Time: 11:02
 */
class Weixin {

    /**
     * 微信API
     * @var null
     */
    private $wx_api;
    /**
     * Token
     * @var null
     */
    private $token;

    /**
     * appid 从微信公众平台获取
     * @var
     */
    private $app_id;

    /**
     * app_secret 从微信公众平台获取
     * @var
     */
    private $app_secret;

    /**
     * 初始化数据
     */
    public function __construct () {
        if ( empty( Cool::$GC['weixin'] ) || empty( Cool::$GC['weixin']['token'] ) ) {
            return false;
        }
        $this->token = Cool::$GC['weixin']['token'];
        $this->wx_api = Cool::$GC['weixin']['api_url'];
        $this->app_id = Cool::$GC['weixin']['app_id'];
        $this->app_secret = Cool::$GC['weixin']['app_secret'];
        Cool::load_sys ( 'Http' );
    }

    /**
     * 获取access_token
     * @param $grant_type
     * @return bool
     */
    public function get_access_token ( $grant_type ) {
        if ( $grant_type == null ) {
            return false;
        }
        $params = array(
            'grant_type' => 'client_credential',
            'appid'      => $this->app_id,
            'secret'     => $this->app_secret,
        );
        $api = $this->wx_api . '/cgi_bin/token';
        $result = Http::get ( $api, $params );
        if (empty($result)) {
            return null;
        }
        return json_decode($result, true);
    }

    /**
     * 获取微信服务器IP列表
     * @param $access_token
     */
    public function get_callback_ip($access_token){
        if ($access_token == null ){
            return false;
        }
        $params = array('access_token' => $access_token);
        $api = $this->wx_api . '/cgi-bin/getcallbackip';
        $result = Http::post($api, $params);
        return $result;
    }

    /**
     * 生成二维码
     * @param $access_token
     * @param $expire     过期时间（秒）
     * @return bool|string
     */
    public function create_qrcode ( $access_token, $expire = 604800 ) {
        if ( $access_token == null ) {
            return false;
        }
        $params = array( 'action_name' => 'QR_SCENE', 'action_info' => array( 'scene' => array('scene_id' => 123)) );
        if ( $expire > 0 ) {
            $params = array( 'action_name' => 'QR_SCENE', 'action_info' => array( 'scene' => array( 'scene_id' => 123 ) ) );
        } else {
            $params = array( 'action_name' => 'QR_LIMIT_STR_SCENE', 'action_info' => array( 'scene' => array( 'scene_str' => 123 ) ) );
        }
        $api = $this->wx_api . '/cgi-bin/qrcode/create?access_token='.$access_token;
        $result = Http::get ( $api, $params );
        return $result;
    }

    public function download_qrcode ($ticket) {
        $api = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$ticket;
        $image = Http::get($api);
    }

    /**
     * 返回结果
     * @return string
     */
    public function response_msg () {
        //get post data, May be due to the different environments
        $post_str = $GLOBALS["HTTP_RAW_POST_DATA"];
        if ( empty ( $post_str ) ) {
            return null;
        }
        /* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection, the best way is to check the validity of xml by yourself */
        libxml_disable_entity_loader ( true );
        $PostObj = simplexml_load_string ( $post_str, 'SimpleXMLElement', LIBXML_NOCDATA );
        $from_username = $PostObj->FromUserName;
        $to_username = $PostObj->ToUserName;
        $keyword = trim ( $PostObj->Content );
        $time = time ();
        if ( empty( $keyword ) ) {
            return null;
        }
        $text_tpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            <FuncFlag>0</FuncFlag>
        </xml>";
        $result_str = sprintf ( $text_tpl, $from_username, $to_username, $time, 'text', 'Welcome to wechat world!' );
        return $result_str;
    }

    /**
     * Check signature
     * @return bool
     */
    public function check_signature ( $signature, $timestamp, $nonce, $echo_str ) {
        $tmp_arr = array( $this->token, $timestamp, $nonce );
        // use SORT_STRING rule
        sort ( $tmp_arr, SORT_STRING );
        $tmp_str = implode ( $tmp_arr );
        $tmp_str = sha1 ( $tmp_str );
        if ( $tmp_str == $signature ) {
            return $echo_str;
        } else {
            return false;
        }
    }
}