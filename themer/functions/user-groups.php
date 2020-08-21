<?php
defined( 'ABSPATH' ) || exit;

class WPCOM_User_Groups {
    function __construct(){
        add_action( 'admin_init', array( $this, 'set_default_user_groups' ) );
        add_action( '_admin_menu', array( $this, 'user_groups_menu' ) );
        add_action( 'manage_user-groups_custom_column', array( $this, 'user_groups_column_count' ), 10, 3 );
        add_action( 'show_user_profile', array( $this, 'edit_user_groups' ) );
        add_action( 'edit_user_profile', array( $this, 'edit_user_groups' ) );
        add_action( 'personal_options_update', array( $this, 'save_user_groups_terms' ) );
        add_action( 'edit_user_profile_update', array( $this, 'save_user_groups_terms' ) );
        add_action( 'manage_users_custom_column', array( $this, 'manage_users_custom_column' ), 10, 3 );
        add_action( 'restrict_manage_users', array( $this, 'restrict_manage_users' ) );

        add_filter( 'wpcom_tax_metas', array( $this, 'user_groups_metas' ) );
        add_filter( 'parent_file', array( $this, 'user_groups_parent_file' ) );
        add_filter( 'manage_edit-user-groups_columns', array( $this, 'user_groups_column' ) );
        add_filter( 'manage_users_columns', array( $this, 'manage_users_columns' ) );
        add_filter( 'pre_get_users', array( $this, 'filter_users_by_groups' ) );
        add_filter( 'admin_init', array( $this, 'change_users_group' ) );

        $this->register_user_groups();
    }

    function register_user_groups(){
        $labels = array(
            'name' => __('Groups', 'wpcom'),
            'singular_name' => __('User Group', 'wpcom'),
            'search_items' => __('Search Groups', 'wpcom'),
            'all_items' => __('All Groups', 'wpcom'),
            'edit_item' => __('Edit Group', 'wpcom'),
            'update_item' => __('Update Group', 'wpcom'),
            'add_new_item' => __('Add Group', 'wpcom'),
            'not_found' => __('No Group', 'wpcom'),
            'menu_name' => __('Groups', 'wpcom'),
        );

        $args = array(
            'public' => false,
            'hierarchical' => false,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'user-groups'),
            'capabilities' => array(
                'manage_terms' => 'edit_users',
                'edit_terms' => 'edit_users',
                'delete_terms' => 'edit_users',
                'assign_terms' => 'edit_users',
            ),
            'update_count_callback' => array( $this, 'user_group_update_count_callback' )
        );
        register_taxonomy('user-groups', 'user', $args);
    }

    function user_groups_menu(){
        global $submenu;

        add_submenu_page('users.php', __('Groups', 'wpcom'), __('Groups', 'wpcom'), 'manage_options', 'edit-tags.php?taxonomy=user-groups', '');

        $find_page = 'users.php';
        $find_sub = __('Groups', 'wpcom');

        foreach ($submenu as $page => $items):
            if ($page == $find_page):
                foreach ($items as $id => $meta):
                    if ($meta[0] == $find_sub):
                        $submenu[$find_page][11] = $meta;
                        unset ($submenu[$find_page][$id]);
                        ksort($submenu[$find_page]);
                    endif;
                endforeach;
            endif;
        endforeach;
    }

    function user_groups_metas($metas){

        if (!isset($metas['user-groups'])) $metas['user-groups'] = array();

        $default_roles = array(
            'subscriber' => 'Subscriber',
            'contributor' => 'Contributor',
            'author' => 'Author',
            'editor' => 'Editor',
            'administrator' => 'Administrator'
        );

        foreach ($default_roles as $role => $name) {
            $roles[$role] = translate_user_role($name);
        }

        $group_metas = array(
            array(
                'name' => 'sys_role',
                'title' => '系统角色',
                'desc' => '选择当前用户角色在wordpress系统中的角色',
                'type' => 'select',
                'options' => $roles
            ),
            array(
                'name' => 'wpadmin',
                'title' => '后台访问权限',
                'desc' => '是否可以访问网站wp-admin后台',
                'type' => 'toggle'
            ),
            array(
                'name' => 'adminbar',
                'title' => '前端工具条',
                'desc' => '是否显示前端页面顶部管理工具条',
                'type' => 'toggle'
            )
        );

        $metas['user-groups'] = array_merge($metas['user-groups'], $group_metas);

        return $metas;
    }

    function user_groups_parent_file($parent_file = ''){
        global $pagenow;

        if (!empty($_GET['taxonomy']) && ($_GET['taxonomy'] == 'user-groups') && $pagenow == 'edit-tags.php') {
            $parent_file = 'users.php';
        }

        return $parent_file;
    }

    function user_group_update_count_callback($terms, $taxonomy){
        global $wpdb;

        foreach ((array)$terms as $term) {
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d", $term));

            do_action('edit_term_taxonomy', $term, $taxonomy);
            $wpdb->update($wpdb->term_taxonomy, compact('count'), array('term_taxonomy_id' => $term));
            do_action('edited_term_taxonomy', $term, $taxonomy);
        }
    }

    function user_groups_column($columns){
        unset($columns['posts']);
        unset($columns['description']);
        $columns['users'] = __('Users', 'wpcom');
        return $columns;
    }

    function user_groups_column_count($display, $column, $term_id){
        if ('users' === $column) {
            $term = get_term($term_id, 'user-groups');
            echo $term->count;
        }
    }

    function edit_user_groups($user){

        $tax = get_taxonomy('user-groups');

        /* Make sure the user can assign terms of the profession taxonomy before proceeding. */
        if (!current_user_can($tax->cap->assign_terms))
            return;

        /* Get the terms of the 'profession' taxonomy. */
        $terms = get_terms('user-groups', array('hide_empty' => false)); ?>

        <h3><?php _e('Groups', 'wpcom'); ?></h3>

        <table class="form-table">

            <tr>
                <th><label for="user-groups"><?php _e('Select Group', 'wpcom'); ?></label></th>
                <td>
                    <select name="user-groups">
                        <option value=""><?php _e('Select Group', 'wpcom'); ?></option>
                        <?php
                        if (!empty($terms)) {
                            foreach ($terms as $term) { ?>
                                <option value="<?php echo esc_attr($term->slug); ?>" <?php echo(is_object_in_term($user->ID, 'user-groups', $term) ? 'selected' : '') ?>><?php echo $term->name; ?></option>
                            <?php }
                        } ?>
                    </select>
                </td>
            </tr>

        </table>
    <?php }

    function save_user_groups_terms($user_id){
        $tax = get_taxonomy('user-groups');
        if (!current_user_can('edit_user', $user_id) && current_user_can($tax->cap->assign_terms))
            return false;

        if (isset($_POST['user-groups'])) {
            wp_set_object_terms($user_id, array($_POST['user-groups']), 'user-groups', false);
            clean_object_term_cache($user_id, 'user-groups');
        }
    }

    function manage_users_columns($columns){
        $new_columns = array();
        foreach ($columns as $role => $name) {
            if ($role == 'role') {
                $new_columns[$role] = $name;
                $new_columns['group'] = __('Group', 'wpcom');
            } else {
                $new_columns[$role] = $name;
            }
        }
        return $new_columns;
    }

    function manage_users_custom_column($value, $column_name, $user_id){
        if ($column_name == 'group') {
            $group = wpcom_get_user_group($user_id);
            if ( $group ) return $group->name;
        }

        return $value;
    }

    function restrict_manage_users($which){
        $change = $which == 'bottom' ? 'change_group2' : 'change_group';
        $change_btn = $which == 'bottom' ? 'change_btn2' : 'change_btn';
        $change_group = isset($_REQUEST['change_btn']) ? (isset($_REQUEST['change_group']) ? $_REQUEST['change_group'] : '') : (isset($_REQUEST['change_group2']) ? $_REQUEST['change_group2'] : '');

        $filter = $which == 'bottom' ? 'filter_group2' : 'filter_group';
        $filter_btn = $which == 'bottom' ? 'filter_btn2' : 'filter_btn';
        $filter_group = isset($_REQUEST['filter_btn']) ? (isset($_REQUEST['filter_group']) ? $_REQUEST['filter_group'] : '') : (isset($_REQUEST['filter_group2']) ? $_REQUEST['filter_group2'] : '');

        $groups = get_terms(array(
            'taxonomy' => 'user-groups',
            'hide_empty' => false,
        ));
        ?>
        <div style="display: inline-block;margin-left: 10px;">
            <label class="screen-reader-text" for="<?php echo $change; ?>">分组变更</label>
            <select name="<?php echo $change; ?>" id="<?php echo $change; ?>">
                <option value="">将分组变更为...</option>
                <?php
                foreach ($groups as $group) { ?>
                    <option value="<?php echo esc_attr($group->slug); ?>" <?php selected($group->slug, $change_group); ?>><?php echo $group->name; ?></option>
                <?php } ?>
            </select>
            <input name="<?php echo $change_btn; ?>" class="button" value="<?php _e('Change'); ?>" type="submit"/>
        </div>
        <div style="display: inline-block">
            <label class="screen-reader-text" for="<?php echo $filter; ?>"><?php _e('User Groups', 'wpcom'); ?></label>
            <select name="<?php echo $filter; ?>" id="<?php echo $filter; ?>">
                <option value="">筛选用户分组</option>
                <?php
                foreach ($groups as $group) { ?>
                    <option value="<?php echo esc_attr($group->slug); ?>" <?php selected($group->slug, $filter_group); ?>><?php echo $group->name; ?></option>
                <?php } ?>
            </select>
            <input name="<?php echo $filter_btn; ?>" class="button" value="<?php _e('Filter'); ?>" type="submit"/>
        </div>
        <div style="display: inline-block;margin-left: 10px;">
            <input name="update-default-groups" class="button button-primary" value="更新无分组用户" type="submit"/>
        </div>
        <?php
    }

    function filter_users_by_groups($query){
        global $pagenow;
        if (is_admin() && 'users.php' == $pagenow && (isset($_REQUEST['filter_btn']) || isset($_REQUEST['filter_btn2']))) {
            $filter_group = isset($_REQUEST['filter_btn']) ? $_REQUEST['filter_group'] : $_REQUEST['filter_group2'];
            $group = get_term_by('slug', $filter_group, 'user-groups');
            $users = get_objects_in_term($group->term_id, 'user-groups');
            $query->set('include', $users);
        }
    }

    function change_users_group(){
        global $pagenow;
        if ( is_admin() && 'users.php' == $pagenow && (isset($_REQUEST['change_btn']) || isset($_REQUEST['change_btn2'])) ) {
            $change_group = isset($_REQUEST['change_btn']) ? $_REQUEST['change_group'] : $_REQUEST['change_group2'];
            $uids = isset($_REQUEST['users']) ? $_REQUEST['users'] : '';
            if( $change_group && $uids ){
                foreach ( $uids as $id ){
                    wp_set_object_terms( $id, array( $change_group ), 'user-groups', false );
                }
            }
        }
    }

    function set_default_user_groups(){
        global $pagenow, $options;
        if( current_user_can( 'edit_users' ) && 'users.php' == $pagenow && isset($_REQUEST['update-default-groups']) && $_REQUEST['update-default-groups'] != '') {
            $default_group = isset($options['member_group']) && $options['member_group'] ? $options['member_group'] : '';
            if( !$default_group ) return false;
            $users = get_users( array('number' => -1) );
            foreach ( $users as $user ) {
                $group = wp_get_object_terms( $user->ID, 'user-groups' );
                if ( is_wp_error($group) || !isset($group[0]) ) {
                    wp_set_object_terms( $user->ID, array( (int)$default_group ), 'user-groups', false );
                    clean_object_term_cache( $user->ID, 'user-groups' );
                }
            }
        }
    }
}