<?php

/**
 * MySQLHelper Class
 *
 * @package     CoolPHP
 * @subpackage  setting
 * @category    databases.mysql
 * @author      Intril.Leng <jj.comeback@gmail.com>
 */
class MySQLHelper {

    /**
     * 配置数组
     *
     * @var array
     */
    private $_config = array();

    /**
     * 连接标识
     *
     * @var int
     */
    private $_link_id;

    /**
     * 构造函数
     *
     * @param array $config 配置数组array($host, $port, $username, $password, $dbname, $charset)
     */
    public function __construct ( $config ) {
        $this->_config = array(
            'host'     => $config['host'], // 主机
            'port'     => $config['port'], // 端口
            'username' => $config['user'], // 用户名
            'password' => $config['password'], // 密码
            'dbname'   => $config['dbname'], // 数据库
            'charset'  => $config['charset'], // 字符集(注意MySQL当中utf-8需写成utf8)
            'pconnect' => $config['pconnect'], // 是否为长连接
        );
    }

    /**
     * 建立到数据库服务器的连接
     */
    public function connect () {
        if ( $this->_config['pconnect'] == true ) {
            $this->_link_id = mysql_pconnect( $this->_config['host'] . ':' . $this->_config['port'], $this->_config['username'], $this->_config['password'] ) or $this->error( 'mysql_connect' );
        } else {
            $this->_link_id = mysql_connect( $this->_config['host'] . ':' . $this->_config['port'], $this->_config['username'], $this->_config['password'] ) or $this->error( 'mysql_connect' );
        }

        // 设置字符集
        if ( $this->_config['charset'] ) {
            $this->query( 'SET NAMES ' . $this->_config['charset'] );
        }

        // 设置活动数据库
        if ( $this->_config['dbname'] ) {
            $this->select_db( $this->_config['dbname'] );
        }
    }

    /**
     * 选择指定数据库作为活动数据库
     *
     * @param string $databaseName
     * @return true
     */
    public function select_db ( $dbname ) {
        $ret = mysql_select_db( $dbname, $this->_link_id ) or $this->error( 'mysql_select_db' );
        return $ret;
    }

    /**
     * 执行指定查询语句，并返回结果集
     *
     * @param string $sql SQL语句
     * @return resource/true
     */
    public function query ( $sql ) {
        // 检查连接是否建立
        if ( !isset( $this->_link_id ) ) {
            $this->connect();
        }

        // 注意：赋值运算的优先级高于逻辑or运算，所以总是先执行赋值运算，再进行逻辑or运算
        $result = mysql_query( $sql, $this->_link_id ) or $this->error( 'mysql_query', $sql );

        return $result;
    }

    /**
     * 从指定结果集中获取一行作为关联数组、数字数组或二者兼有
     *
     * @param resource $result 结果集
     * @param int      $type   结果类型(MYSQL_ASSOC/MYSQL_NUM/MYSQL_BOTH)
     * @return array/false 返回根据从结果集取得的行生成的数组，如果没有更多行则返回 FALSE。
     */
    public function fetch_array ( $result, $type = MYSQL_BOTH ) {
        return mysql_fetch_array( $result, $type );
    }

    /**
     * 从指定结果集中获取一行作为关联数组
     *
     * @param resource $result 结果集
     * @return array/false 返回根据从结果集取得的行生成的关联数组，如果没有更多行则返回 FALSE。
     */
    public function fetch_assoc ( $result ) {
        return mysql_fetch_assoc( $result );
    }

    /**
     * 从指定结果集中获取一行作为枚举数组
     *
     * @param resource $result 结果集
     * @return array/false 返回根据所取得的行生成的数组，如果没有更多行则返回 FALSE。
     */
    public function fetch_row ( $result ) {
        return mysql_fetch_row( $result );
    }

    /**
     * 获取查询记录的条数
     *
     * @param string $sql SQL语句
     * @return string
     */
    public function count ( $sql ) {
        $stmt = $this->query( $sql );
        return mysql_result( $stmt, 0, 0 );
    }

    /**
     * 执行指定查询语句并从结果集中获取一条记录,以一维数组形式返回
     *
     * @param string $sql  SQL语句
     * @param int    $type 结果类型(MYSQL_ASSOC/MYSQL_NUM/MYSQL_BOTH)
     * @return array/false
     */
    public function get_one ( $sql, $type = MYSQL_ASSOC ) {
        $stmt = $this->query( $sql );
        return $this->fetch_array( $stmt, $type );
    }

    /**
     * 执行指定查询语句并从结果集中获取所有记录，以二维数组形式返回
     *
     * @param string $sql        SQL语句
     * @param int    $resultType 结果类型(MYSQL_ASSOC/MYSQL_NUM/MYSQL_BOTH)
     * @return array
     */
    public function get_all ( $sql, $type = MYSQL_ASSOC ) {
        $result = $this->query( $sql );
        $row_arr = array();
        while ( $row = $this->fetch_array( $result, $type ) ) {
            $row_arr[] = $row;
        }
        return $row_arr;
    }

    /**
     * 返回给定的连接中上一步INSERT查询中产生的AUTO_INCREMENT的ID号;如果上一查询没有产生AUTO_INCREMENT的值,则返回0
     *
     * @return int
     */
    public function insert_id () {
        return mysql_insert_id( $this->_link_id );
    }

    /**
     * 错误处理函数
     *
     * @param string $where
     * @param string $sql
     */
    public function error ( $where, $sql = '' ) {
        throw new CoolException( CoolException::ERR_MYSQL, "Where:%s\nSQL:%s\nError:%s\nErrno:%d", $where, $sql, mysql_error( $this->_link_id ), mysql_errno( $this->_link_id ) );
    }
}
