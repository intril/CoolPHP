<?php

/**
 * 共公模型，业务中共公的方法
 *
 * @author       Intril.Leng <jj.comeback@gmail.com>
 * @Date         2015年8月7日
 * @Time         下午3:18:48
 */
class Model extends CoolModel {

    /**
     * 连接的表
     *
     * @var
     */
    protected $table;

    /**
     * 初始化公用Model
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * 返回指定表所有数据
     *
     * @param null  $where
     * @param null  $fields
     * @param       $page_num
     * @param       $page_size
     * @param array $order_by
     * @param bool  $is_count
     * @return array|bool
     * @throws CoolException
     */
    public function get_all($where = null, $fields = null, $page_num, $page_size, $order_by = array('id' => 'DESC'), $is_count = true) {
        if ($this->table == null) {
            return false;
        }
        $return = $this->table($this->table)
            ->where($where)
            ->fields($fields)
            ->limit($page_num, $page_size)
            ->order_by($order_by)
            ->find_all();
        if ($is_count == true) {
            $total = $this->table($this->table)
                ->where($where)
                ->count();
            return array('total' => $total, 'list' => $return);
        } else {
            return $return;
        }
    }

    /**
     * 返回单条记录
     *
     * @param      $where
     * @param null $fields
     * @param null $cache_name
     * @param null $rkey
     * @param int  $expire
     * @return array|bool
     */
    public function get_one($where, $fields = null, $cache_name = null, $rkey = null, $expire = 0) {
        if (empty($where) || $this->table == null) {
            return false;
        }
        $return = $this->table($this->table)
            ->fields($fields)
            ->find_one($cache_name, $rkey, $expire);
        return $return;
    }

    /**
     * 更新数据
     *
     * @param      $where
     * @param      $data
     * @param null $cache_name
     * @param null $rkey
     * @param int  $expire
     * @return bool
     * @throws CoolException
     */
    public function updated($where, $data, $cache_name = null, $rkey = null, $expire = 0) {
        if (empty($data) || empty($where) || $this->table == null) {
            return false;
        }
        $return = $this->table($this->table)
            ->data($data)
            ->where($where)
            ->update($cache_name, $rkey, $expire);
        return $return;
    }

    /**
     * 添加数据
     *
     * @param      $data
     * @param null $cache_name
     * @param null $rkey
     * @param int  $expire
     * @param null $cache_insert_id
     * @return bool
     */
    public function add($data, $cache_name = null, $rkey = null, $expire = 0, $cache_insert_id = null) {
        if (empty($data) || $this->table == null) {
            return false;
        }
        $insert_id = $this->table($this->table)
            ->data($data)
            ->insert($cache_name, $rkey, $expire);
        if ($cache_insert_id != null) {
            $this->redis($cache_name)->set($cache_insert_id . $insert_id, $data, $expire);
        }
        return $insert_id;
    }

    /**
     * 删除数据
     *
     * @param      $where
     * @param null $cache_name
     * @param null $rkey
     * @return bool
     * @throws CoolException
     */
    public function remove($where, $cache_name = null, $rkey = null) {
        if (empty($where) || $this->table == null) {
            return false;
        }
        $return = $this->table($this->table)
            ->where($where)
            ->delete($cache_name, $rkey);
        return $return;
    }
}