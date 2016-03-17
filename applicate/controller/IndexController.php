<?php

/**
 * 默认访问控制器
 *
 * @author       Intril.Leng <jj.comeback@gmail.com>
 * @Date         2015年8月7日
 * @Time         下午3:18:48
 */
class IndexController extends Controller {


    /**
     * 显示模板页
     *
     * @param       $view
     * @param array $data
     * @throws CoolException
     */
    public function index () {
        // 处理导行的问题
        $navigate_id = fetch_val('get.navigate_id');
        // sidebar 部分数据
        $this->data['navigate'] = $this->get_navigate ();
        if ( $navigate_id && isset( $this->data['navigate'][$navigate_id] ) ) {
            $this->data['sidebar'] = $this->data['navigate'][$navigate_id];
        } else {
            $this->data['sidebar'] = current ( $this->data['navigate'] );
        }
        Cool::session()->set_data('navigate_id', $this->data['sidebar']['navigate_id']);
        $this->data['sidebar'] = $this->data['sidebar']['module'];
        $this->data['footer'] = array(
            'version' => 'v.0.1', 'author' => 'Seven.Leng', 'email' => 'Seven.Leng@top25.cn'
        );
        $this->data['sess'] = Cool::session()->get_data('user_data');
        $this->data['navigate_id'] = Cool::session()->get_data( 'navigate_id' );
        $this->display('index', $this->data);
    }

    /**
     * 获取导行条
     */
    private function get_navigate () {
        $user_data = Cool::session()->get_data('user_data');
        $table = "admin_menu_url, admin_module";
        if ( $user_data['user_group'] == 1 ) { // admin 帐号所有权限
            $where = "admin_menu_url.module_id = admin_module.module_id and admin_module.module_online = 1 and admin_menu_url.menu_online = 1 and admin_menu_url.is_show = 1";
        } else {
            $where = "admin_module.module_online = 1 and admin_menu_url.menu_online = 1 and admin_menu_url.module_id = admin_module.module_id and admin_menu_url.is_show = 1 and admin_menu_url.menu_id in (" . $user_data['group_role'] . ")";
        }
        $where .= " ORDER BY admin_module.module_sort asc, admin_menu_url.menu_id asc";
        $menu = Cool::model('Admin')->return_admin ( $table, $where );
        if ( empty( $menu ) ) {
            return array();
        }
        if ( $user_data['user_group'] == 1 ) {
            $nav_where = '1 = 1';
        } else {
            $nav_where = 'navigate_id in (' . trim ( $user_data['nav_id'], ',' ) . ')';
        }
        $navigate = Cool::model('Admin')->return_admin ( 'admin_navigate', 'navigate_online = 1 and ' . $nav_where . ' order by navigate_desc asc' );
        if ( empty( $navigate ) ) {
            return array();
        }
        $list_nav = array();
        foreach ( $navigate as $key => $value ) {
            $module_id_array = explode ( ',', $value['module_id'] );
            foreach ( $menu as $k => $v ) {
                if ( in_array ( $v['module_id'], $module_id_array ) ) {
                    $list_nav[$value['navigate_id']]['navigate_id'] = $value['navigate_id'];
                    $list_nav[$value['navigate_id']]['navigate_name'] = $value['navigate_name'];
                    $list_nav[$value['navigate_id']]['navigate_url'] = $value['navigate_url'] . '?navigate_id=' . $value['navigate_id'];
                    $list_nav[$value['navigate_id']]['navigate_icon'] = $value['navigate_icon'];
                    $list_nav[$value['navigate_id']]['module'][$v['module_id']]['module_id'] = $v['module_id'];
                    $list_nav[$value['navigate_id']]['module'][$v['module_id']]['module_name'] = $v['module_name'];
                    $list_nav[$value['navigate_id']]['module'][$v['module_id']]['module_icon'] = $v['module_icon'];
                    $list_nav[$value['navigate_id']]['module'][$v['module_id']]['menu'][$v['menu_id']] = $v;
                }
            }
        }
        return $list_nav;
    }
}