<?php

/**
 * Base Model Class
 * all model must be to extends this class
 *
 * @package CoolPHP
 * @subpackage setting
 * @category core
 * @author Intril.Leng <jj.comeback@gmail.com>
 */
abstract class CoolModel {

    /**
     * 辅助类实例数组
     *
     * @var PDOHelper
     */
    private static $_object = array ();

    /**
     * 当前的DB配置
     *
     * @var string
     */
    private $conf;

    /**
     * 选择要连接的DB key
     *
     * @var string
     */
    protected $db;

    /**
     * 默认字段
     * @var unknown
     */
    protected $fields = '*';

    /**
     * 默认表格
     * @var unknown
     */
    protected $table;

    /**
     * INSERT时的INGNORE
     * @var
     */
    private $ignore;

    /**
     * 默认查询条件
     * @var unknown
     */
    private $where;

    /**
     * 默认关联表
     * @var unknown
     */
    private $join;

    /**
     * duplicate key update
     * @var
     */
    private $duplicate;

    /**
     * 默认Group by
     * @var unknown
     */
    private $group_by;

    /**
     * 默认Order by
     * @var unknown
     */
    private $order_by;

    /**
     * 默认limit
     * @var unknown
     */
    private $limit;

    /**
     * 默认Data
     * @var unknown
     */
    private $data;

    /**
     * 最后一次的SQL语句
     *
     * @var string
     */
    private $_queried_sql;

    /**
     * 构造方法
     */
    public function __construct() {
        if ($this->db == NULL) {
            $this->db = Cool::$GC ['default_database'];
        }
    }

    /**
     * 返回Redis连接对象
     *
     * @param string $cache_name
     * @return RedisHelper
     */
    public function redis ( $cache_name = NULL ) {
        Cool::load_sys ( 'RedisHelper' );
        $redis = new RedisHelper ( $cache_name );
        return $redis;
    }

    /**
     * 获取DB辅助类的实例
     *
     * @return object
     */
    private function DH() {
        if (empty(self::$_object [$this->db])) {
            $this->connect ();
        }
        return self::$_object [$this->db];
    }

    /**
     * 连接数据库
     *
     * @throws CoolException
     */
    public function connect($database = NULL) {
        if (is_file(APP_PATH . 'config/db.conf.php')) {
            include_once APP_PATH . 'config/db.conf.php';
        }else{
            throw new CoolException ( CoolException::ERR_SYSTEM, 'Database Config File Is Not Found!' );
        }
        if ($database != NULL) {
            $this->db = $database;
        }
        // 检查要连接的db是否有配置
        if (! isset ( $db [$this->db] )) {
            throw new CoolException ( CoolException::ERR_SYSTEM, 'No Database inst key in db.conf.php!' );
        }
        // 设置当前连接的db配置
        $this->conf [$this->db] = $db [$this->db];
        // 通过配制的db连接引擎 自动初始化对应的helper
        if (strtolower ( $db [$this->db] ['driver'] ) == 'pdo') {
            $helper = 'PDOHelper';
        } else if (strtolower ( $db [$this->db] ['driver'] ) == 'mysql') {
            $helper = 'MySQLHelper';
        } else if (strtolower ( $db [$this->db] ['driver'] ) == 'mysqli') {
            $helper = 'MySQLiHelper';
        } else {
            throw new CoolException ( CoolException::ERR_SYSTEM, 'Connect Mysql Type Is Not Support!' );
        }
        // 初始化DB连接
        Cool::load_sys ( $helper );
        if (! isset ( self::$_object [$this->db] )) {
            self::$_object [$this->db] = new $helper ( $db [$this->db] );
        }
    }

    /**
     * 断开连接
     *
     * @param string $db
     */
    public function disconnect($database = NULL) {
        if ($database == NULL) {
            $database = Cool::$GC ['default_database'];
        }
        self::$_object [$database] = false;
    }

    /**
     * 获取多条记录
     *
     * @param int $type
     *            结果类型(MYSQL_ASSOC/MYSQL_NUM/MYSQL_BOTH/PDO::FETCH_ASSOC/PDO::FETCH_NUM/PDO::FETCH_BOTH)
     * @return array 找到记录时返回一个二维数组,否则返回空数组
     */
    public function find_all() {
        $sql = 'SELECT ' . $this->fields . ' FROM ' . $this->table . $this->join . $this->where . $this->group_by . $this->order_by . $this->limit;
        // 重置所有的属性
        $this->_reset_sql ( $sql );
        return $this->DH ()->get_all ( $sql );
    }

    /**
     * 获取一条记录
     *
     * @param int $type
     *            结果类型(MYSQL_ASSOC/MYSQL_NUM/MYSQL_BOTH/PDO::FETCH_ASSOC/PDO::FETCH_NUM/PDO::FETCH_BOTH)
     * @param bool $limit_one
     *            是否启用limit 1优化
     * @return array/false 找到记录时返回一个一维数组,否则返回false
     */
    public function find_one( $cache_name = NULL, $rkey = NULL, $expire = 0 ) {
        $return = NULL;
        if ( $cache_name ) { // 需要从Cache里面查询时
            $return = $this->redis ( $cache_name )->get ( $rkey, $expire );
        }
        if ( $return ) { // 取过Cache则不取DB
            return $return;
        }
        $this->limit ( 0, 1 );
        $sql = 'SELECT ' . $this->fields . ' FROM ' . $this->table . $this->join . $this->where . $this->group_by . $this->order_by . $this->limit;
        // 重置所有的属性
        $this->_reset_sql ( $sql );
        $return = $this->DH ()->get_one ( $sql );
        if ($cache_name) { // 需要设置到Cache时
            $this->redis ( $cache_name )->set ( $rkey, $return, $expire );
        }
        return $return;
    }

    /**
     * 插入记录
     *
     * @return bool 成功返回TRUE,失败返回FALSE
     */
    public function insert( $cache_name = NULL, $rkey = NULL, $expire = 0, $primary_key_pre = NULL ) {
        $sql = 'INSERT ' . $this->ignore . ' INTO ' . $this->table;
        $key_arr = $val_arr = array ();
        foreach ( $this->data as $key => $val ) {
            $key_arr [] = $this->_wrap_field ( $key );
            $val_arr [] = $this->_wrap_value ( $val );
        }
        $sql .= ' (' . implode ( ',', $key_arr ) . ') VALUES (' . implode ( ',', $val_arr ) . ')' . $this->duplicate;
        // 重置所有的属性
        $this->_reset_sql ( $sql );
        if ($this->DH ()->query ( $sql ) !== FALSE) {
            $insert_id = $this->DH ()->insert_id ();
            if ($primary_key_pre) {
                $rkey [] = $primary_key_pre . $insert_id;
            }
            if ( $cache_name ) { // 需要设置到Cache时
                $this->redis ( $cache_name )->set ( $rkey, $this->data, $expire );
            }
            return $insert_id;
        } else {
            return FALSE;
        }
    }

    /**
     * 更新记录
     *
     * @return bool 成功返回TRUE,失败返回FALSE
     */
    public function update( $cache_name = NULL, $rkey = NULL, $expire = 0 ) {
        $sql = 'UPDATE ' . $this->table;
        $tmp_arr = array ();
        foreach ( $this->data as $field => $value ) {
            if (! is_array ( $value )) { // key => value 结构
                $tmp_arr[] = $this->_wrap_field($field) . ' = ' . $this->_wrap_value($value);
            } else { // key => array(value1, value2, operation) 结构
                if (count ( $value ) < 2) { // 少参数,抛异常
                    throw new CoolException ( CoolException::ERR_SYSTEM, 'Update data val\'s arguments less than 2!' );
                }
                if (! isset ( $value [2] )) {
                    $value [2] = '=';
                }
                $tmp_arr [] = $this->_wrap_field ( $field ) . ' = ' . $value [0] . ' ' . $value [2] . ' ' . $this->_wrap_value ( $value [1] );
            }
        }
        $sql .= ' SET ' . implode ( ',', $tmp_arr ) . $this->where;
        // 重置所有的属性
        $this->_reset_sql ( $sql );
        $return = $this->DH ()->query ( $sql );
        if ($return !== FALSE) {
            if ( $cache_name ) { // 需要设置到Cache时
                $this->redis ( $cache_name )->set ( $rkey, $this->data, $expire );
            }
            return $return;
        }else {
            return FALSE;
        }
    }

    /**
     * 删除记录
     *
     * @return bool 成功返回TRUE,失败返回FALSE
     */
    public function delete( $cache_name = NULL, $rkey = NULL ) {
        if ($this->where == null) {
            throw new CoolException ( CoolException::ERR_SYSTEM, 'Can not Delete All data' );
        }
        $sql = 'DELETE FROM ' . $this->table . $this->where;
        // 重置所有的属性
        $this->_reset_sql ( $sql );
        if ( $cache_name ) { // 需要设置到Cache时
            $this->redis ( $cache_name )->rm ( $rkey );
        }
        return ($this->DH ()->query ( $sql ) !== FALSE);
    }

    /**
     * 获取记录条数
     *
     * @return string
     */
    public function count() {
        $sql = 'SELECT COUNT(' . $this->fields . ') FROM ' . $this->table . $this->join . $this->where . $this->group_by . $this->order_by;
        // 重置所有的属性
        $this->_reset_sql ( $sql );
        return $this->DH ()->count ( $sql );
    }

    /**
     * 取最大记录数
     * @return Ambigous <number, array/FALSE>
     */
    public function max(){
        $sql = 'SELECT MAX(' . $this->fields . ') as num FROM ' . $this->table . $this->join . $this->where . $this->group_by . $this->order_by;
        // 重置所有的属性
        $this->_reset_sql ( $sql );
        $return = $this->DH ()->get_one( $sql );
        return isset($return['num']) ? $return['num'] : 0;
    }

    /**
     * 获取上一步insert的自增ID
     */
    public function insert_id() {
        return $this->DH ()->insert_id ();
    }

    /**
     * duplicate key update
     * @param $data
     * @return $this
     * @throws CoolException
     */
    public function duplicate ( $data ) {
        if ( empty( $data ) ) {
            throw new CoolException ( CoolException::ERR_SYSTEM, 'duplicate data is null' );
        }
        $tmp_arr = array();
        foreach ( $data as $field => $value ) {
            if ( !is_array ( $value ) ) { // key => value 结构
                $tmp_arr[] = $this->_wrap_field ( $field ) . ' = ' . $this->_wrap_value ( $value );
            } else { // key => array(value1, value2, operation) 结构
                if ( count ( $value ) < 2 ) { // 少参数,抛异常
                    throw new CoolException ( CoolException::ERR_SYSTEM, 'Update data val\'s arguments less than 2!' );
                }
                if ( !isset ( $value [2] ) ) {
                    $value [2] = '=';
                }
                $tmp_arr [] = $this->_wrap_field ( $field ) . ' = ' . $value [0] . ' ' . $value [2] . ' ' . $this->_wrap_value ( $value [1] );
            }
        }
        $this->duplicate = ' ON DUPLICATE KEY UPDATE ' . implode ( ',', $tmp_arr );
        return $this;
    }

    /**
     * 设置要查询的table
     *
     * @param $table 查询表.
     *            "table, table2"
     * @return $this
     */
    public function table($table) {
        $table = str_replace ( array ( ' ', ',' ), array ( '', '`,`' ), $table );
        $this->table = ' ' . $this->_wrap_field ( $table );
        return $this;
    }

    /**
     * 设置查询字段
     *
     * @param string $fields
     *            查询字段,如:field1,field2,field3
     * @return $this
     */
    public function fields($fields = null) {
        if ($fields == null){
            $this->fields = '*';
        } else {
            $fields = str_replace ( array ( ' as ', ',' ), array ( '` as `', '`,`' ), $fields );
            $fields = str_replace('`,` ', '`, `', $fields);
            $this->fields = $this->_wrap_field ( $fields );
        }
        return $this;
    }

    /**
     * 设置where条件数组
     *
     * @param array $where
     *            where条件数组,如:array('k1' => 'v1', 'k2' => array('v2', '=',))
     * @return $this
     */
    public function where($where) {
        if (is_array($where)) {
            $sql = array();
            foreach ($where as $field => $value) {
                if (is_array ( $value )) { // 是数组时
                    $op = isset ( $value [2] ) ? $value [2] : "AND";
                    if (! in_array ( $op, array ( 'AND', 'OR' ) )) {
                        throw new CoolException ( CoolException::ERR_SYSTEM, 'Undefined where condition link operator(%s)!', $op );
                    }
                    $cmp = isset ( $value [1] ) ? strtoupper($value [1]) : "=";
                    if (in_array ( $cmp, array ( '=', '<>', '<=>', '!=', '<=', '<', '>=', '>', 'LIKE', 'NOT LIKE' ) )) {
                        $field = str_replace($cmp, '', $field);
                        $sql [] = $op . $this->_wrap_field ( $field ) . ' ' . $cmp . ' ' . $this->_wrap_value ( $value[0] );
                    } else if (in_array ( $cmp, array ( 'IN', 'NOT IN', 'IS', 'IS NOT' ) )) {
                        $sql [] = $op . $this->_wrap_field ( $field ) . ' ' . $cmp . ' ' . '(' . $value[0] . ')';
                    } else if ($cmp == 'FIND_IN_SET') {
                        $sql [] = $op .' ' . $cmp . ' (' . $value[0] . ', ' . $this->_wrap_field ( $field ) . ')';
                    } else {
                        throw new CoolException ( CoolException::ERR_SYSTEM, 'Undefined where condition compare operator(%s)!', $field );
                    }
                } else {
                    $sql [] = "AND " . $this->_wrap_field ( $field ) ." = ". $this->_wrap_value ( $value );
                }
            }
            // 组合成sql语句 并去掉首尾的AND和OR还有空格
            $sql = trim ( implode ( ' ', $sql ), 'AND' );
            $sql = trim ( $sql, 'OR' );
        } else { // 直接写SQL
            $sql = $where;
        }
        $this->where = ' WHERE ' . trim ( $sql );
        return $this;
    }

    public function ignore () {
        $this->ignore = 'IGNORE';
        return $this;
    }

    /**
     * 设置联接表
     *
     * @param string $table
     *            表名
     * @param string $on
     *            联接条件
     * @param string $type
     *            联接类型(LEFT/RIGHT/...)
     * @return $this
     */
    public function join($table, $on, $type = 'LEFT') {
        $this->join = ' ' . strtoupper ( $type ) . ' JOIN ' . $this->_wrap_field ( $table ) . ' ON ' . $on;
        return $this;
    }

    /**
     * 设置group by分组数组
     *
     * @param array $group_by
     *            分组数组,如:array('field1' => 'DESC', 'field2' => 'ASC')
     * @return $this
     */
    public function group_by($group_by) {
        $this->group_by = ' GROUP BY ' . $this->_make_by_sql ( $group_by );
        return $this;
    }

    /**
     * 设置order by排序数组
     *
     * @param array $order_by
     *            排序数组,如:array('field1' => 'DESC', 'field2' => 'ASC')
     * @return $this
     */
    public function order_by($order_by) {
        $this->order_by = ' ORDER BY ' . $this->_make_by_sql ( $order_by );
        return $this;
    }

    /**
     * 设置limit偏移及行数
     *
     * @param int $start
     *            从位置开始
     * @param int $offset
     *            偏移量
     * @return $this
     */
    public function limit ( $start = 0, $offset = 0 ) {
        $this->limit = ' LIMIT ' . intval ( $start * $offset ) . ', ' . intval ( $offset );
        return $this;
    }

    /**
     * Set部分的数据
     *
     * @param unknown $data
     * @return CoolModel
     */
    public function data ($data) {
        $this->data = $data;
        return $this;
    }

    /**
     * 获取最后一次查询的SQL语句
     *
     * @return string
     */
    public function sql ( $index = NULL ) {
        if ($index === null) {
            return $this->_queried_sql;
        }
        if (is_numeric ( $index ) && isset ( $this->_queried_sql [$index] )) {
            return $this->_queried_sql [$index];
        }
        return NULL;
    }

    /**
     * 重置所有条件和参数
     *
     * @param
     *            $sql
     */
    private function _reset_sql($sql) {
        $this->where = '';
        $this->join = '';
        $this->group_by = '';
        $this->order_by = '';
        $this->limit = '';
        $this->data = '';
        // 存储起来,方便调试
        $this->_queried_sql[] = $sql;
    }

    /**
     * 构造by子句
     *
     * @param array $by_arr
     *            group by分组数组/order by排序数组
     * @return string
     * @throws CoolException
     */
    private function _make_by_sql ( $by_arr ) {
        if ( is_array ( $by_arr ) ) {
            $data_arr = array ();
            foreach ( $by_arr as $key => $val ) {
                $val = strtoupper ( $val );
                if (! in_array ( $val, array ( '', 'ASC', 'DESC' ) )) {
                    throw new CoolException ( CoolException::ERR_SYSTEM, 'Invalid order method(%s)!', $val );
                }
                $data_arr [] = ($val === '') ? $key : $key . ' ' . $val;
            }
            return implode ( ', ', $data_arr );
        } else {
            return $by_arr;
        }
    }

    /**
     * 给字符串首尾加上反引号(`)
     *
     * @param string $key
     * @return string
     */
    private function _wrap_field ( $key ) {
        return '`' . str_replace('.', '`.`', $key) . '`';
    }

    /**
     * 给字符串首尾加上单引号(')
     *
     * @param
     *            string /int/float $val
     * @return string
     */
    private function _wrap_value ( $val ) {
        if (is_numeric ( $val ) && strlen($val) < 32) {
            return $val;
        }
        $val = str_replace ( array ( '\\', "\0", "\n", "\r", "'", '"', "\x1a" ), array ( '\\\\', '\0', '\n', '\r', "\'", '\"', "\Z" ), $val );
        return "'" . $val . "'";
    }
}