<?php

/**
 * PDOHelper Class
 * connect mysql by pdo and return connect resource
 *
 * @package     CoolPHP
 * @subpackage  setting
 * @category    database.mysql
 * @author      Intril.Leng <jj.comeback@gmail.com>
 */
class PDOHelper {

    /**
     * 配置数组
     *
     * @var array
     */
    private $_config = array();

    /**
     * PDO实例
     *
     * @var PDO
     */
    private $_pdo;

    /**
     * 构造函数
     *
     * @param array  $config 配置数组array($host, $port, $username, $password, $dbname, $charset)
     * @param string $type
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
        try {
            $this->_pdo = new PDO(
                'mysql:host=' . $this->_config['host'] . ';port=' . $this->_config['port'] . ';dbname=' . $this->_config['dbname'], $this->_config['username'], $this->_config['password'], array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                )
            );
        } catch ( PDOException $e ) {
            $this->error( 'PDO::__construct', $e->getMessage() );
        }

        // 设置字符集
        if ( $this->_config['charset'] ) {
            $this->query( 'SET NAMES ' . $this->_config['charset'] );
        }
    }

    /**
     * 选择指定数据库作为活动数据库
     *
     * @param string $databaseName
     * @return true
     */
    public function select_db ( $databaseName ) {
        return $this->query( 'USE ' . $databaseName );
    }

    /**
     * 执行指定查询语句，并返回结果集
     *
     * @param string $sql SQL语句
     * @return PDOStatement
     */
    public function query ( $sql ) {
        // 检查连接是否建立
        if ( !isset( $this->_pdo ) ) {
            $this->connect();
        }

        // 执行查询
        try {
            return $this->_pdo->query( $sql );
        } catch ( PDOException $e ) {
            $this->error( 'PDO::query', $sql );
        }
    }

    /**
     * 从指定PDOStatement对象中获取一行作为关联数组、数字数组或二者兼有
     *
     * @param PDOStatement $stmt PDOStatement对象
     * @param int          $type 结果类型(PDO::FETCH_ASSOC/PDO::FETCH_NUM/PDO::FETCH_BOTH)
     * @return array
     */
    public function fetch_array ( $stmt, $type = PDO::FETCH_BOTH ) {
        return $stmt->fetch( $type );
    }

    /**
     * 从指定PDOStatement对象中获取一行作为关联数组
     *
     * @param PDOStatement $stmt PDOStatement对象
     * @return array
     */
    public function fetch_assoc ( $stmt ) {
        return $stmt->fetch( PDO::FETCH_ASSOC );
    }

    /**
     * 从指定PDOStatement对象中获取一行作为枚举数组
     *
     * @param PDOStatement $stmt PDOStatement对象
     * @return array
     */
    public function fetch_row ( $stmt ) {
        return $stmt->fetch( PDO::FETCH_NUM );
    }

    /**
     * 获取查询记录的条数
     *
     * @param string $sql SQL语句
     * @return string
     */
    public function count ( $sql ) {
        $stmt = $this->query( $sql );
        $row = $this->fetch_row( $stmt );
        return $row[0];
    }

    /**
     * 执行指定查询语句并从结果集中获取一条记录,以一维数组形式返回
     *
     * @param string $sql  SQL语句
     * @param int    $type 结果类型(PDO::FETCH_ASSOC/PDO::FETCH_NUM/PDO::FETCH_BOTH)
     * @return array/FALSE
     */
    public function get_one ( $sql, $type = PDO::FETCH_ASSOC ) {
        $stmt = $this->query( $sql );
        return $stmt->fetch( $type );
    }

    /**
     * 执行指定查询语句并从结果集中获取所有记录，以二维数组形式返回
     *
     * @param string $sql  SQL语句
     * @param int    $type 结果类型(PDO::FETCH_ASSOC/PDO::FETCH_NUM/PDO::FETCH_BOTH)
     * @return array
     */
    public function get_all ( $sql, $type = PDO::FETCH_ASSOC ) {
        $stmt = $this->query( $sql );
        return $stmt->fetchAll( $type );
    }

    /**
     * 返回给定的连接中上一步INSERT查询中产生的AUTO_INCREMENT的ID号;如果上一查询没有产生AUTO_INCREMENT的值,则返回0
     *
     * @return int
     */
    public function insert_id () {
        return $this->_pdo ? intval( $this->_pdo->lastInsertId() ) : 0;
    }

    /**
     * 错误处理函数
     *
     * @param string $where
     * @param string $sql
     */
    public function error ( $where, $sql = '' ) {
        $error_info = $this->_pdo ? $this->_pdo->errorInfo() : array( '', 'PDO Error', 0 );
        throw new CoolException( CoolException::ERR_PDO, "Where:%s\nSQL:%s\nError:%s\nErrno:%d", $where, $sql, $error_info[2], $error_info[1] );
    }
}
