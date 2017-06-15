<?php

/**
 * Base Model Class
 * all model must be to extends this class
 *
 * @package         CoolPHP
 * @subpackage      setting
 * @category        core
 * @author          Intril.Leng <jj.comeback@gmail.com>
 */
abstract class CoolModel {
    // 辅助类实例数组
    private static $_object = array ();
    // 当前的DB配置
    private $conf;
    // 选择要连接的DB key
    protected $db;
    // 默认表格
    protected $table;
    // sql query 语句块
    private $_query = array(
            'fields'    => '*',             // 默认字段
            'where'     => '',              // 默认查询条件
            'join'      => '',              // 默认关联表
            'duplicate' => '',              // duplicate key update
            'group_by'  => '',              // 默认Group by
            'order_by'  => '',              // 默认Order by
            'limit'     => '',              // 默认limit
            'data'      => '',              // 默认Data
            'data_sql'  => '',              // 默认Data Sql
            'ignore'    => '',              // INSERT时的INGNORE
    );
    // 最后一次的SQL语句
    private $_queried_sql;
    // 数据库表达式
    private $_exp = array('eq'=>'=','neq'=>'<>','gt'=>'>','egt'=>'>=','lt'=>'<','elt'=>'<=','notlike'=>'NOT LIKE','like'=>'LIKE','in'=>'IN','notin'=>'NOT IN','not in'=>'NOT IN','between'=>'BETWEEN','not between'=>'NOT BETWEEN','notbetween'=>'NOT BETWEEN');

    /**
     * 构造方法
     */
    public function __construct ( ) {
        if ($this->db == NULL) {
            $this->db = Cool::$GC ['default_database'];
        }
    }

    /**
     * 返回Redis连接对象
     *
     * @param   string  $cache_name
     * @return  object  RedisHelper
     */
    public function redis ( $cache_name = NULL ) {
        Cool::load_sys ( 'RedisHelper' );
        $redis = new RedisHelper ( $cache_name );
        return $redis;
    }

    /**
     * 获取DB辅助类的实例
     * @return object
     */
    private function DH ( ) {
        if (empty ( self::$_object [$this->db] )) {
            $this->connect ();
        }
        return self::$_object [$this->db];
    }

    /**
     * 连接数据库
     * @param   string          $database  database.conf.php中的数据库key
     * @throws  CoolException
     */
    public function connect ( $database = NULL ) {
        if (is_file ( APP_PATH . 'config/db.conf.php' )) {
            include APP_PATH . 'config/db.conf.php';
        } else {
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
     * @param string $db
     */
    public function disconnect ( $database = NULL ) {
        if ($database == NULL) {
            $database = Cool::$GC ['default_database'];
        }
        self::$_object [$database] = false;
    }

    /**
     * 获取多条记录
     * @param int $type     结果类型(MYSQL_ASSOC/MYSQL_NUM/MYSQL_BOTH/PDO::FETCH_ASSOC/PDO::FETCH_NUM/PDO::FETCH_BOTH)
     * @return array        找到记录时返回一个二维数组,否则返回空数组
     */
    public function find_all ( ) {
        $sql = 'SELECT ' . $this->_query ['fields'] . ' FROM ' . $this->table . $this->_query ['join'] . $this->_query ['where'] . $this->_query ['group_by'] . $this->_query ['order_by'] . $this->_query ['limit'];
        // 重置所有的属性
        $this->_reset_sql ( $sql );
        return $this->DH ()->get_all ( $sql );
    }

    /**
     * 获取一条记录
     *  这里有处理缓存穿透，但要在新增的时候对相应的缓存进行一次请理。
     * @param unknown $cache_name   缓存名称
     * @param unknown $rkey         缓存前缀
     * @param number $expire        过期时间
     */
    public function find_one ( $cache_name = NULL, $rkey = NULL, $expire = 0 ) {
        $return = NULL;
        if ($cache_name != NULL) {
            $return = $this->redis ( $cache_name )->get ( $rkey, $expire );
            if (! empty ( $return )) { // 存在缓存Key时，不读DB直接返回
                $this->redis ( $cache_name )->set ( $rkey, $return, $expire );
                return $return;
            }
        }
        // 不在缓存取DB
        $sql = 'SELECT ' . $this->_query ['fields'] . ' FROM ' . $this->table . $this->_query ['join'] . $this->_query ['where'] . $this->_query ['group_by'] . $this->_query ['order_by'] . ' LIMIT 0, 1';
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
     */
    public function insert ( ) {
        $sql = 'INSERT ' . $this->_query['ignore'] . ' INTO ' . $this->table . ' SET '.$this->_query['data_sql'] . $this->_query['duplicate'];
        // 重置所有的属性
        $this->_reset_sql ( $sql );
        $result = $this->DH ()->query ( $sql );
        if ($result !== FALSE) {
            $insert_id = $this->DH ()->insert_id ();
            return $insert_id ? $insert_id : $result;
        } else {
            return FALSE;
        }
    }

    /**
     * 更新记录 同时删除指定的缓存Key
     * @param unknown $cache_name
     * @param unknown $rkey
     * @return unknown
     */
    public function update ( $cache_name = NULL, $rkey = NULL ) {
        if ($this->_query['where'] == null) {
            throw new CoolException ( CoolException::ERR_SYSTEM, 'Can not Delete All data' );
        }
        $sql = 'UPDATE ' . $this->table . ' SET ' . $this->_query['data_sql'] . $this->_query ['where'];
        // 重置所有的属性
        $this->_reset_sql ( $sql );
        $return = $this->DH ()->query ( $sql );
        if ($cache_name != NULL) {
            if (is_array ( $rkey )) {
                foreach ( $rkey as $key ) {
                    $this->redis ( $cache_name )->rm ( $key );
                }
            } else {
                $this->redis ( $cache_name )->rm ( $rkey );
            }
        }
        return $return;
    }

    /**
     * Replace更新
     */
    public function replace ( ) {
        $sql = 'REPLACE INTO ' . $this->table . ' SET ' . $this->_query ['data_sql'];
        // 重置所有的属性
        $this->_reset_sql ( $sql );
        $return = $this->DH ()->query ( $sql );
        return $return;
    }

    /**
     * 删除记录
     * @param unknown $cache_name       缓存名称
     * @param unknown $rkey             缓存前缀
     * @throws CoolException
     */
    public function delete ( $cache_name = NULL, $rkey = NULL ) {
        if ($this->_query['where'] == null) {
            throw new CoolException ( CoolException::ERR_SYSTEM, 'Can not Delete All data' );
        }
        $sql = 'DELETE FROM ' . $this->table . $this->_query['where'];
        // 重置所有的属性
        $this->_reset_sql ( $sql );
        if ($cache_name) { // 需要设置到Cache时
            $this->redis ( $cache_name )->rm ( $rkey );
        }
        return ($this->DH ()->query ( $sql ) !== FALSE);
    }

    /**
     * 获取记录条数
     * @return string
     */
    public function count ( ) {
        $sql = 'SELECT COUNT(' . $this->_query['fields'] . ') FROM ' . $this->table . $this->_query['join'] . $this->_query['where'] . $this->_query['group_by'] . $this->_query['order_by'];
        // 重置所有的属性
        $this->_reset_sql ( $sql );
        return $this->DH ()->count ( $sql );
    }

    /**
     * 取最大记录数
     * @return Ambigous <number, array/FALSE>
     */
    public function max ( ) {
        $sql = 'SELECT MAX(' . $this->_query['fields'] . ') as max FROM ' . $this->table . $this->_query['join'] . $this->_query['where'] . $this->_query['group_by'] . $this->_query['order_by'];
        // 重置所有的属性
        $this->_reset_sql ( $sql );
        $return = $this->DH ()->get_one ( $sql );
        return isset ( $return ['num'] ) ? $return ['num'] : 0;
    }

    /**
     * duplicate key update
     * @param   $data
     * @return  $this
     * @throws  CoolException
     */
    public function duplicate ( $data ) {
        $this->_query ['duplicate'] = ' ON DUPLICATE KEY UPDATE ' . $this->_make_data ( $data );
        return $this;
    }

    /**
     * 设置要查询的table
     * @param   $table  查询表.  "table, table2"
     * @return  $this
     */
    public function table ( $table ) {
        $this->table = $this->_parse_field ( $table );
        return $this;
    }

    /**
     * 设置查询字段
     * @param   string  $fields     查询字段,如:field1,field2,field3
     * @return  $this
     */
    public function fields ( $fields = null ) {
        if ($fields == null){
            $this->_query['fields'] = '*';
        } else {
            $this->_query['fields'] = $this->_parse_field($fields);
        }
        return $this;
    }

    /**
     * 指定查询条件 支持安全过滤
     * @access  public
     * @param   mixed   $where  条件表达式
     * @param   mixed   $parse  预处理参数
     * @return  Model
     */
    public function where ( $where ) {
        if (is_string ( $where )) {
            $where_str = empty ( $where ) ? '' :  $where;
        }else{
            $where_str = $this->parse_complex_where ( $where );
        }
        $this->_query['where'] = empty ( $where_str ) ? '' : ' WHERE ' . $where_str;
        return $this;
    }

    /**
     * 处理复杂查询
     * @param unknown $where_arr
     * @return string
     */
    private function parse_complex_where( $where_arr ){
        $where_str = '';
        $operate = isset ( $where_arr ['_logic'] ) ? strtoupper ( $where_arr ['_logic'] ) : '';
        if (in_array ( $operate, array ( 'AND', 'OR', 'XOR' ) )) {
            $operate = ' ' . $operate . ' ';
            unset ( $where_arr ['_logic'] );
        } else {
            $operate = ' AND ';
        }
        foreach ( $where_arr as $key => $val ) {
            if ($key === '_string') {
                $where_str .= '( ' . $val . ' )';
            } else if ($key === '_complex') {
                $where_str .= '( ' . $this->parse_complex_where ( $val, FALSE ) . ' )';
            } else {  // 多条件支持
                $where_str .= $this->parse_where_item ( trim ( $key ), $val );
            }
            $where_str .= $operate;
        }
        $where_str = substr ( $where_str, 0, - strlen ( $operate ) );
        return $where_str;
    }

    /**
     * where子单元分析
     * @param   unknown $key
     * @param   unknown $val
     * @throws  CoolException
     */
    private function parse_where_item ( $key, $val ) {
        $where_str = '';
        if (!is_array ( $val )) {
            $where_str .= $key . ' = ' . $this->_parse_value ( $val );
            return $where_str;
        }
        if (is_string ( $val [0] )) {
            $exp = strtolower ( $val [0] );
            if (preg_match ( '/^(eq|neq|gt|egt|lt|elt)$/', $exp )) { // 比较运算
                $where_str .= $key . ' ' . $this->_exp [$exp] . ' ' . $this->_parse_value ( $val [1] );
            } else if ('bind' == $exp) { // 使用表达式
                $where_str .= $key . ' = :' . $val [1];
            } else if ('exp' == $exp) { // 使用表达式
                $where_str .= $key . ' ' . $val [1];
            } else if (preg_match ( '/^(notlike|like)$/', $exp )) { // 模糊查找
                if (is_array ( $val [1] )) {
                    $like_logic = isset ( $val [2] ) ? strtoupper ( $val [2] ) : 'OR';
                    if (in_array ( $like_logic, array ( 'AND', 'OR', 'XOR' ) )) {
                        $like = array ();
                        foreach ( $val [1] as $item ) {
                            $like [] = $key . ' ' . $this->_exp [$exp] . ' ' . $this->_parse_value ( $item );
                        }
                        $where_str .= '(' . implode ( ' ' . $like_logic . ' ', $like ) . ')';
                    }
                } else {
                    $where_str .= $key . ' ' . $this->_exp [$exp] . ' ' . $this->_parse_value ( $val [1] );
                }
            } elseif (preg_match ( '/^(notin|not in|in)$/', $exp )) { // IN 运算
                if (isset ( $val [2] ) && 'exp' == $val [2]) {
                    $where_str .= $key . ' ' . $this->_exp [$exp] . ' ' . $val [1];
                } else {
                    if (is_string ( $val [1] )) {
                        $val [1] = explode ( ',', $val [1] );
                    }
                    $zone = implode ( ',', $this->_parse_value ( $val [1] ) );
                    $where_str .= $key . ' ' . $this->_exp [$exp] . ' (' . $zone . ')';
                }
            } elseif (preg_match ( '/^(notbetween|not between|between)$/', $exp )) { // BETWEEN运算
                $where_str .= $key . ' ' . $this->_exp [$exp] . ' ' . $this->_parse_value ( $val [1] ) . ' AND ' . $this->_parse_value ( $val [2] );
            } else {
                $where_str .= $key . ' ' . $exp . ' ' . $this->_parse_value ( $val [1] );
//                 throw new CoolException ( CoolException::ERR_SYSTEM, 'sql is error' );
            }
        } else {
            $count = count ( $val );
            $rule = isset ( $val [$count - 1] ) ? (is_array ( $val [$count - 1] ) ? strtoupper ( $val [$count - 1] [0] ) : strtoupper ( $val [$count - 1] )) : '';
            if (in_array ( $rule, array ( 'AND', 'OR', 'XOR' ) )) {
                $count = $count - 1;
            } else {
                $rule = 'AND';
            }
            for($i = 0; $i < $count; $i ++) {
                $data = is_array ( $val [$i] ) ? $val [$i] [1] : $val [$i];
                if ('exp' == strtolower ( $val [$i] [0] )) {
                    $where_str .= $key . ' ' . $data . ' ' . $rule . ' ';
                } else {
                    $where_str .= $this->parse_where_item ( $key, $val [$i] ) . ' ' . $rule . ' ';
                }
            }
            $where_str = '( ' . substr ( $where_str, 0, - 4 ) . ' )';
        }
        return $where_str;
    }

    /**
     * IGNORE 关键词
     */
    public function ignore ( ) {
        $this->_query['ignore'] = 'IGNORE';
        return $this;
    }

    /**
     * 设置联接表
     * @param string $table 表名
     * @param string $on    联接条件
     * @param string $type  联接类型(LEFT/RIGHT/...)
     * @return $this
     */
    public function join ( $table, $on, $type = 'LEFT' ) {
        $this->_query['join'] = ' ' . strtoupper ( $type ) . ' JOIN ' . $this->_parse_field( $table ) . ' ON ' . $on;
        return $this;
    }

    /**
     * 设置group by分组数组
     * @param   array $group_by 分组数组,如:array('field1', 'field2')
     * @return  $this
     */
    public function group_by ( $group_by ) {
        $group_by = is_array($group_by) ? implode(',', $group_by) : $group_by;
        $this->_query['group_by'] = ' GROUP BY ' . $group_by;
        return $this;
    }

    /**
     * 设置order by排序数组
     * @param   array $order_by 排序数组,如:array('field1' => 'DESC', 'field2' => 'ASC')
     * @return $this
     */
    public function order_by ( $order_by ) {
        if (is_array ( $order_by )) {
            $data_arr = array ();
            foreach ( $order_by as $key => $val ) {
                $val = in_array ( strtoupper ( $val ), array ('','ASC','DESC' ) ) ? strtoupper ( $val ) : 'DESC';
                $data_arr [] = ($val === '') ? $key : $key . ' ' . $val;
            }
            $this->_query['order_by'] = ' ORDER BY ' . implode ( ', ', $data_arr );
        } else {
            $this->_query['order_by'] = ' ORDER BY ' . $order_by;
        }
        return $this;
    }

    /**
     * 设置limit偏移及行数
     * @param int $start 从位置开始
     * @param int $offset 偏移量
     * @return $this
     */
    public function limit ( $start = 0, $offset = 0 ) {
        $this->_query['limit'] = ' LIMIT ' . intval ( $start * $offset ) . ', ' . intval ( $offset );
        return $this;
    }

    /**
     * data部分的数据
     * @param unknown $data
     * @return CoolModel
     */
    public function data ( $data ) {
        $this->_query['data_sql'] = $this->_make_data ( $data );
        $this->_query['data'] = $data;
        return $this;
    }

    /**
     * 构造Set数据
     * @param unknown $data
     * @throws CoolException
     */
    private function _make_data( $data ){
        if (is_string ( $data )) {
            return $data;
        }
        $tmp_arr = array ();
        foreach ( $data as $field => $value ) {
            if (! is_array ( $value )) { // key => value 结构
                $tmp_arr [] = $field . ' = ' . $this->_parse_value( $value );
            } else { // key => array(value1, value2, operation) 结构
                if (count ( $value ) < 2) { // 少参数,抛异常
                    throw new CoolException ( CoolException::ERR_SYSTEM, 'Update data val\'s arguments less than 2!' );
                }
                if (! isset ( $value [2] )) {
                    $value [2] = '=';
                }
                $tmp_arr [] = $field . ' = ' . $value [0] . ' ' . $value [2] . ' ' . $this->_parse_value ( $value [1] );
            }
        }
        return implode ( ',', $tmp_arr );
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
        if (isset ( $this->_queried_sql [$index] )) {
            return $this->_queried_sql [$index];
        }
        return 'no sql query';
    }

    /**
     * 重置所有条件和参数
     * @param $sql
     */
    private function _reset_sql ( $sql ) {
        $this->_query = array(
                'fields'    => '*',             // 默认字段
                'where'     => '',              // 默认查询条件
                'join'      => '',              // 默认关联表
                'duplicate' => '',              // duplicate key update
                'group_by'  => '',              // 默认Group by
                'order_by'  => '',              // 默认Order by
                'limit'     => '',              // 默认limit
                'data'      => '',              // 默认Data
                'data_sql'  => '',              // 默认Data SQL
                'ignore'    => '',              // INSERT时的INGNORE
        );
        // 存储起来,方便调试
        $this->_queried_sql [] = $sql;
    }

    /**
     * value分析
     * @access protected
     * @param mixed $value
     * @return string
     */
    private function _parse_value ( $value ) {
        if (is_string ( $value )) {
            $value = '\'' . addslashes ( $value ) . '\'';
        } elseif (isset ( $value [0] ) && is_string ( $value [0] ) && strtolower ( $value [0] ) == 'exp') {
            $value = addslashes ( $value [1] );
        } elseif (is_array ( $value )) {
            $value = array_map ( array ( $this, '_parse_value' ), $value );
        } elseif (is_bool ( $value )) {
            $value = $value ? '1' : '0';
        } elseif (is_null ( $value )) {
            $value = 'null';
        }
        return $value;
    }

    /**
     * field分析
     * @access protected
     * @param mixed $fields
     * @return string
     */
    private function _parse_field ( $data ) {
        if (is_string ( $data )) {
            $data = explode ( ',', $data );
        }
        if (is_array ( $data )) { // 支持 'table'=>'tablename' 这样的表别名定义
            $array = array ();
            foreach ( $data as $key => $val ) {
                if (! is_numeric ( $key )) {
                    $array [] = $key . ' AS ' . $val;
                } else {
                    $array [] = $val;
                }
            }
            return implode ( ',', $array );
        } else {
            return $data;
        }
    }
}