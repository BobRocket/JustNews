<?php
class WPCOM_Session{
    private static $table = 'wpcom_sessions';
    public static function set($name, $value, $expired=''){
        global $wpdb;
        $table = $wpdb->prefix . self::$table;
        $session = array();
        if(!preg_match('/^_/i', $name)) $name = self::session_prefix() . '_' . $name;
        $session['name'] = $name;
        $session['value'] = $value;
        $session['expired'] = $expired && is_numeric($expired) ? $expired : 900;
        $session['time'] = date('Y-m-d H:i:s', current_time( 'timestamp', 1 ));
        self::init_database();
        $option = @$wpdb->get_row( "SELECT * FROM `$table` WHERE name = '$name'" );
        if($option && $option->value) {
            unset($session['name']);
            $res = $wpdb->update($table, $session, array('name' => $name));
        }else{
            $res = $wpdb->insert($table, $session);
        }
        return $res;
    }

    public static function get($name){
        global $wpdb;
        $table = $wpdb->prefix . self::$table;
        if($name) {
            self::init_database();
            if(!preg_match('/^_/i', $name)) $name = self::session_prefix() . '_' . $name;
            $row = $wpdb->get_row("SELECT * FROM `$table` WHERE name = '$name'");
            if($row && $row->value){
                if( (strtotime($row->time) + $row->expired) > current_time( 'timestamp', 1 ) ) {
                    return $row->value;
                } else {
                    self::delete($row->ID);
                }
            }
        }
    }

    public static function delete($id='', $name=''){
        global $wpdb;
        $table = $wpdb->prefix . self::$table;
        if( $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table ) {
            $array = array();
            if($id) $array['ID'] = $id;
            if($name) {
                if(!preg_match('/^_/i', $name)) $name = self::session_prefix() . '_' . $name;
                $array['name'] = $name;
            }
            @$wpdb->delete($table, $array);
        }
    }

    public static function cron(){
        global $wpdb;
        $table = $wpdb->prefix . self::$table;
        if( $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table ) {
            $timestamp = current_time( 'timestamp', 1 );
            $temps = $wpdb->get_results("SELECT * FROM `$table` WHERE UNIX_TIMESTAMP(time)+expired < $timestamp");
            if ($temps) {
                foreach ($temps as $temp) {
                    @$wpdb->delete($table, array('ID' => $temp->ID));
                }
            }
        }
    }

    private static function init_database(){
        global $wpdb;
        $table = $wpdb->prefix . self::$table;
        if( $wpdb->get_var("SHOW TABLES LIKE '$table'") != $table ){
            $charset_collate = $wpdb->get_charset_collate();
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

            // 缓存表
            $create_sql = "CREATE TABLE $table (".
                "ID BIGINT(20) NOT NULL auto_increment,".
                "name text NOT NULL,".
                "value longtext NOT NULL,".
                "expired text,".
                "time datetime,".
                "PRIMARY KEY (ID)) $charset_collate;";

            dbDelta( $create_sql );
        }
    }

    public static function session_prefix(){
        $session_prefix = $_COOKIE['session_prefix'] ? $_COOKIE['session_prefix'] : '';
        if( $session_prefix == '' ) {
            $ip = "none";
            if(!empty($_SERVER["HTTP_CLIENT_IP"])){
                $ip = $_SERVER["HTTP_CLIENT_IP"];
            } elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
                $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } elseif (!empty($_SERVER["REMOTE_ADDR"])){
                $ip = $_SERVER["REMOTE_ADDR"];
            }
            $session_prefix = md5(time() . $ip . $_SERVER['HTTP_USER_AGENT'].'-'.rand(100,999).'-'.rand(100,999));
            @setcookie('session_prefix', $session_prefix, time()+315360000, '/', '', 0, 1);
        }
        return $session_prefix;
    }
}