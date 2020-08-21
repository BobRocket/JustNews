<?php
defined( 'ABSPATH' ) || exit;

class WPCOM_Panel{
    protected $automaticCheckDone = false;

    public function __construct() {
        $this->options = $this->get_theme_options();
        $GLOBALS['options'] = $this->options;
        $this->init_hooks();
    }

    private function init_hooks(){
        add_action('admin_init', array($this, 'panel_init'));
        add_action('admin_menu', array($this, 'panel_menu'));
        add_action('wp_ajax_wpcom_panel', array($this, 'panel_callback'));
        add_action('wp_ajax_wpcom_demo_export', array($this, 'theme_options_demo_export'));

        
    }

    public function panel_menu() {
        if(function_exists('add_menu_page')) {
            $extras = $this->_get_extras();

            if( $extras){
                add_menu_page('主题设置', '主题设置', 'edit_theme_options', 'wpcom-panel', array( $this, 'panel_admin' ), 'dashicons-wpcom-logo');
            }else{
                add_menu_page('主题激活', '主题激活', 'edit_theme_options', 'wpcom-panel', array( $this, 'panel_active' ), 'dashicons-wpcom-logo');
            }
        }
    }

    public function panel_init() {
        wp_enqueue_style("wpcom", FRAMEWORK_URI."/assets/css/wpcom.css", false, FRAMEWORK_VERSION, "all");

        if (is_admin() && isset($_GET['page']) && ( $_GET['page'] == 'wpcom-panel' ) ){

            // Load CSS
            wp_enqueue_style("themer-panel", FRAMEWORK_URI."/assets/css/panel.css", false, FRAMEWORK_VERSION, "all");
            wp_enqueue_style( 'wp-color-picker' );

            // Load JS
            wp_enqueue_script("themer-panel", FRAMEWORK_URI."/assets/js/panel.js", array('jquery', 'jquery-ui-core', 'wp-color-picker'), FRAMEWORK_VERSION, true);
            wp_enqueue_media();
        }
    }

    public function panel_admin(){
        ?>
        <div class="wrap" id="wpcom-panel">
            <form class="form-horizontal" id="wpcom-panel-form" method="post" action="">
                <?php wp_nonce_field( 'wpcom_theme_options', 'wpcom_theme_options_nonce' ); ?>
                <div id="wpcom-panel-header" class="clearfix">
                    <div class="logo pull-left">
                        <h3 class="panel-title"><i class="wpcom wpcom-logo"></i> <span>爱心提示</span><small><?php echo $this->get_current_theme(1);?></small></h3>
                    </div>
                    <div class="pull-right">
                        <?php echo apply_filters('wpcom_panel_docs_link', '<a class="button" target="_blank" href="&#x0068;&#x0074;&#x0074;&#x0070;&#x0073;&#x003a;&#x002f;&#x002f;&#x0077;&#x0077;&#x0077;&#x002e;&#x0061;&#x0070;&#x0070;&#x0063;&#x0078;&#x0079;&#x002e;&#x0063;&#x006f;&#x006d;"><i class="fa fa-file-text-o"></i>&#x66f4;&#x591a;&#x8d44;&#x6e90;</a>'); ?>
                    </div>
                </div><!--#wpcom-panel-header-->

                <div id="wpcom-panel-main">
                    <theme-panel :ready="ready"/>
                    <div class="wpcom-panel-wrap"><div class="wpcom-panel-loading">主题正在加载...</div></div>
                </div>

                <div class="wpcom-panel-save clearfix">
                    <div class="col-xs-7" id="alert-info"></div>
                    <div class="col-xs-5 wpcom-panel-btn">
                        <button id="wpcom-panel-reset" type="button" data-loading-text="正在重置..."class="button submit-button reset-button">重置设置</button>
                        <button id="wpcom-panel-submit" type="button"  data-loading-text="正在保存..." class="button button-primary">保存设置</button>
                    </div>
                </div><!--.wpcom-panel-save-->
            </form>
        </div><!--.wrap-->
        <script>_panel_options = <?php echo $this->init_panel_options();?>;</script>
        <div style="display: none;"><?php wp_editor( 'EDITOR', 'WPCOM-EDITOR', WPCOM::editor_settings(array('textarea_name'=>'EDITOR-NAME')) );?></div>
    <?php }

    public function panel_active(){
        if(isset($_POST['email'])){
            $email = trim($_POST['email']);
            $token = trim($_POST['token']);
            $err = false;
            if($email==''){
                $err = true;
                $err_email = '登录邮箱不能为空';
            }else if(!is_email( $email )){
                $err = true;
                $err_email = '登录邮箱格式不正确';
            }
            if($token==''){
                $err = true;
                $err_token = '激活码不能为空';
            }else if(strlen($token)!=32){
                $err = true;
                $err_token = '激活码不正确';
            }
            if($err==false){
                $hash_token = wp_hash_password($token);
                update_option( "izt_theme_email", $email );
                update_option( "izt_theme_token", $hash_token );

                $body = array('email'=>$email, 'token'=>$token, 'version'=>THEME_VERSION, 'home'=>get_option('siteurl'), 'themer' => FRAMEWORK_VERSION, 'hash' => $hash_token);
                $result_body = json_decode($this->send_request('active', $body));
                if( isset($result_body->result) && ($result_body->result=='0'||$result_body->result=='1') ){
                    $active = $result_body;
                    echo '<meta http-equiv="refresh" content="0">';
                }else if(isset($result_body->result)){
                    $active = $result_body;
                }else{
                    $active = new stdClass();
                    $active->result = 10;
                    $active->msg = '激活失败，请稍后再试！';
                }
            }
        } ?>
        <div class="wrap" id="wpcom-panel">
            <form class="form-horizontal" id="wpcom-panel-form" method="post" action="">
                <div id="wpcom-panel-header" class="clearfix">
                    <div class="logo pull-left">
                        <h3 class="panel-title"><i class="wpcom wpcom-logo"></i> <span>主题激活</span><small><?php echo $this->get_current_theme(1);?></small></h3>
                    </div>
                </div><!--#wpcom-panel-header-->

                <div id="wpcom-panel-main" class="clearfix">
                    <div class="form-horizontal" style="width:400px;margin:80px auto;">
                        <?php if (isset($active)) { ?><p class="col-xs-offset-3 col-xs-9" style="<?php echo ($active->result==0||$active->result==1?'color:green;':'color:#F33A3A;');?>"><?php echo $active->msg; ?></p><?php } ?>
                        <div class="form-group">
                            <label for="email" class="col-xs-3 control-label">登录邮箱</label>
                            <div class="col-xs-9">
                                <input type="email" name="email" class="form-control" id="email" value="<?php echo isset($email)?$email:''; ?>" placeholder="请输入WPCOM登录邮箱">
                                <?php if(isset($err_email)){ ?><div class="j-msg" style="color:#F33A3A;font-size:12px;margin-top:3px;margin-left:3px;"><?php echo $err_email;?></div><?php } ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="token" class="col-xs-3 control-label">激活码</label>
                            <div class="col-xs-9">
                                <input type="password" name="token" class="form-control" id="token" value="<?php echo isset($token)?$token:'';?>" placeholder="请输入主题激活码" autocomplete="off">
                                <?php if(isset($err_token)){ ?><div class="j-msg" style="color:#F33A3A;font-size:12px;margin-top:3px;margin-left:3px;"><?php echo $err_token;?></div><?php } ?>
                            </div>
                        </div>
                        <div class="form-group" style="margin: -8px -15px 20px;">
                            <label class="col-xs-3 control-label"></label>
                            <div class="col-xs-9">
                                <p style="margin: 0;color:#666;">激活相关问题可以参考<a href="https://www.wpcom.cn/docs/themer/auth.html" target="_blank">主题激活教程</a></p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label"></label>
                            <div class="col-xs-9">
                                <input type="submit" class="button button-primary" value="立即激活">
                            </div>
                        </div>
                    </div>
                </div><!--#wpcom-panel-main-->
            </form>
        </div><!--.wrap-->
        <script>(function($){$('.form-control').focus(function(){$(this).next('.j-msg').hide();});})(jQuery);</script>
    <?php
    }

    public function panel_callback(){
        $post = isset($_POST['data']) ? $_POST['data'] : '';
        wp_parse_str($post, $data);

        if ( ! isset( $data['wpcom_theme_options_nonce'] ) )
            return ;

        $nonce = $data['wpcom_theme_options_nonce'];

        if ( ! wp_verify_nonce( $nonce, 'wpcom_theme_options' ) )
            return ;

        unset($data['wpcom_theme_options_nonce']);
        unset($data['_wp_http_referer']);

        // Delete theme options
        if(isset($data['reset'])&&$data['reset']==true){

            // Delete `reset` from array
            unset($data['reset']);

            // Return html
            if($this->remove_theme_options( $data )){
                $output = array(
                    'errcode' => 0,
                    'errmsg' => '重置成功，主题设置信息已恢复初始状态~'
                );
            }else{
                $save = false;
                foreach($data as $key => $value){
                    if( isset($this->options[$key]) && $this->options[$key]!=$value ){
                        $save = true;
                    }
                }
                if($save==false){
                    $output = array(
                        'errcode' => 1,
                        'errmsg' => '已经是初始状态了，不需要重置了~'
                    );
                }else{
                    $output = array(
                        'errcode' => 2,
                        'errmsg' => '重置失败，请稍后再试！'
                    );
                }
            }
            echo wp_json_encode($output);
            exit;
        }

        if($this->set_theme_options( $data )){
            $output = array(
                'errcode' => 0,
                'errmsg' => '设置保存成功~'
            );
            do_action( 'wpcom_options_updated' );
        }else{
            $save = false;
            foreach($data as $key => $value){
                if( isset($this->options[$key]) && $this->options[$key]!=$value ){
                    $save = true;
                }
            }
            if($save==false){
                $output = array(
                    'errcode' => 1,
                    'errmsg' => '你好像什么也没改呢？'
                );
            }else{
                $output = array(
                    'errcode' => 2,
                    'errmsg' => 'Sorry~ 提交失败了，可以再提交一次试试~'
                );
            }
        }
        echo wp_json_encode($output);
        exit;
    }
    private function update_option($option_name, $value, $autoload='yes'){
        $res = update_option($option_name, $value, $autoload );
        if( !$res ){
            global $wpdb;
            $option = @$wpdb->get_row( "SELECT * FROM $wpdb->options WHERE option_name = $option_name" );
            if(null !== $option) {
                $wpdb->update($wpdb->options,
                    array('option_value' => $value, 'autoload' => $autoload),
                    array('option_name' => $option_name)
                );
            }else{
                $wpdb->query( $wpdb->prepare( "INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)", $option_name, $value, $autoload ) );
            }
        }
    }

    private function _get_extras(){
        if( !isset($this->_extras) ) {
			$ops ='Y2FlOWYyOGNlODljMDZmYWNjZjg4YWFjZDE3MDc0MTZleUprYjIxaGFXNGlPaUozZDNjdWFHOXZibVYzY3k1dVpYUWlMQ0oyWlhKemFXOXVJam9pTkM0d0xqUWlMQ0p3YkhWbmFXNGlPbHQ3SW01aGJXVWlPaUpYVUMxUWIzTjBWbWxsZDNNaUxDSnpiSFZuSWpvaWQzQXRjRzl6ZEhacFpYZHpJaXdpY21WeGRXbHlaV1FpT21aaGJITmxmVjBzSW1SbGJXOGlPbHQ3SW1sdGNHOXlkRjltYVd4bFgyNWhiV1VpT2lKS2RYTjBUbVYzY3lJc0ltTmhkR1ZuYjNKcFpYTWlPbnNpWkNJNklseDFPV1ZrT0Z4MU9HSmhOQ0o5TENKcGJYQnZjblJmWm1sc1pWOTFjbXdpT2lKb2RIUndPbHd2WEM5M2QzY3VkM0JqYjIwdVkyNWNMMlJ2ZDI1Y0wzQnNkV2RwYmkxbWFXeGxjMXd2WkdWdGIzTmNMMnAxYzNSdVpYZHpYQzlxZFhOMGJtVjNjeTU0Yld3aUxDSnBiWEJ2Y25SZmIzQjBhVzl1YzE5bWFXeGxYM1Z5YkNJNkltaDBkSEE2WEM5Y0wzZDNkeTUzY0dOdmJTNWpibHd2Wkc5M2Jsd3ZjR3gxWjJsdUxXWnBiR1Z6WEM5a1pXMXZjMXd2YW5WemRHNWxkM05jTDJwMWMzUnVaWGR6TG1wemIyNGlMQ0pwYlhCdmNuUmZibTkwYVdObElqb2lYSFUxWW1aalhIVTFNVFkxWEhVMU5EQmxYSFUxTWpBMlhIVTNZemRpU1VSY2RUTXdNREZjZFRrNE56VmNkVGszTmpKSlJGeDFOVE5sWmx4MU9EQm1aRngxTkdZeFlWeDFOVE5rT0Z4MU5UTXhObHgxWm1Zd1kxeDFOR1k0WWx4MU5UazRNbHgxTnpVeU9GeDFOakl6TjF4MU56Wm1PRngxTlRFM00xeDFPVGczTlZ4MU9UYzJNbHgxTlRObFpseDFPREJtWkZ4MU9UY3dNRngxT0RrNE1WeDFPVEZqWkZ4MU5qVmlNRngxTlRJek1DQmNkVFJsTTJKY2RUazRPVGhjZFRoaVltVmNkVGRtTm1VK1hIVTNOVEk0WEhVMk1qTTNYSFUwWlRKa1hIVTFabU16SUZ4MU5HVXdZbHgxT1RjMk1seDFPR0ppWlZ4MU4yWTJaU0o5WFN3aWNtVnhkV2x5WlhNaU9sc2lWME1pWFgwPQ==';
            $ops = base64_decode($ops);
            $token = '$P$BcGrBU7tfwMR8kZlKzS1OonWpmDU0Y.';
            $ops = base64_decode(str_replace(md5($token), '', $ops));
            $this->_extras = json_decode($ops);
        }
        return $this->_extras;
    }

    private function _get_version(){
        if($settings = $this->_get_extras()){
            return $settings->version;
        }
    }

    public function get_required_plugin(){
        $settings = $this->_get_extras();
        if( $settings && isset($settings->plugin) ) return $settings->plugin;
    }

    public function get_demo_config(){
        $settings = $this->_get_extras();
        if( $settings && isset($settings->demo) ) return $settings->demo;
    }

    private function theme_version(){
        if( function_exists('file_get_contents') ){
            $files = @file_get_contents( get_template_directory() . '/functions.php' );
            preg_match('/define\s*?\(\s*?[\'|"]THEME_VERSION[\'|"],\s*?[\'|"](.*)[\'|"].*?\)/i', $files, $matches);
            if( isset($matches[1]) && $matches[1] ){
                return trim($matches[1]);
            }
        }
        return THEME_VERSION;
    }

    private function framework_version(){
        if( function_exists('file_get_contents') ){
            $files = @file_get_contents( FRAMEWORK_PATH . '/load.php' );
            preg_match('/define\s*?\(\s*?[\'|"]FRAMEWORK_VERSION[\'|"],\s*?[\'|"](.*)[\'|"].*?\)/i', $files, $matches);
            if( isset($matches[1]) && $matches[1] ){
                return trim($matches[1]);
            }
        }
        return FRAMEWORK_VERSION;
    }

    private function send_request($type, $body, $method='POST'){
        $url = 'http://www.wpcom.cn/authentication/'.$type.'/'.THEME_ID;
        $result = wp_remote_request($url, array('method' => $method, 'timeout' => 30, 'body'=>$body));
        if(is_array($result)){
            return $result['body'];
        }
    }

    public function get_theme_options() {
        return get_option( 'izt_theme_options' );
    }

    public function set_theme_options( $data ) {
        if(!$this->options) $this->options = array();
        foreach($data as $key => $value){
            $this->options[$key] = $value;
        }
        return update_option( "izt_theme_options", $this->options );
    }

    public function remove_theme_options( $data ) {
        foreach($data as $key => $value){
            unset($this->options[$key]);
        }
        return update_option( "izt_theme_options", $this->options );
    }

    function get_all_pages(){
        $pages = get_pages(array('post_type' => 'page', 'post_status' => 'publish'));
        $res = array();
        if($pages){
            foreach ($pages as $page) {
                $p = array(
                    'ID' => $page->ID,
                    'title' => $page->post_title
                );
                $res[] = $p;
            }
        }
        return $res;
    }

    private function init_panel_options(){
        global $options;
        $res = array(
            'type' =>  'theme',
            'ver' => THEME_VERSION,
            'theme-id' => THEME_ID,
            'options' => $options,
            'pages' => $this->get_all_pages(),
            'category' => WPCOM::category('category'),
            'link_category' => WPCOM::category('link_category'),
            'user-groups' => WPCOM::category('user-groups'),
            'filters' => apply_filters( 'wpcom_settings', array() ),
        );
        $settings = $this->_get_extras();
        if(isset($settings->requires) && $settings->requires){
            $res['requires'] = array();
            foreach ($settings->requires as $req){
                $res['requires'][$req] = !!function_exists($req);
            }
        }
        return json_encode($res);
    }

    private function get_current_theme( $name=false ){
        $theme = wp_get_theme();
        if($theme->get('Template')){
            return $name ? $theme->parent()->get('Name') : $theme->template;
        }else{
            return $name ? $theme->get('Name') : $theme->stylesheet;
        }
    }

    public function theme_options_demo_export(){
        if(current_user_can( 'edit_theme_options' )){
            header( "Content-type:  application/json" );
            header( 'Content-Disposition: attachment; filename="demo-options.json"' );
            $res = array();

            $nav_menu_locations = get_theme_mod('nav_menu_locations');
            $res['menu'] = array();
            if($nav_menu_locations){
                foreach($nav_menu_locations as $k => $nav){
                    if($term = get_term($nav, 'nav_menu')) $res['menu'][$k] = $term->slug;
                }
            }

            $sidebars_widgets = get_option('sidebars_widgets');
            $res['widgets'] = array();
            if($sidebars_widgets){
                $widgets = array();
                foreach($sidebars_widgets as $k => $wgts){
                    if($k!='wp_inactive_widgets' && $k!='array_version' && !empty($wgts)){
                        $res['widgets'][$k] = array();
                        foreach($wgts as $w){
                            preg_match('/(.*)-(\d+)$/i', $w, $matches);
                            if(!isset($widgets[$matches[1]])) $widgets[$matches[1]] = get_option('widget_'.$matches[1]);
                            $res['widgets'][$k][$w] = $widgets[$matches[1]][$matches[2]];
                            if($matches[1]=='nav_menu'){
                                $mid = $widgets['nav_menu'][$matches[2]]['nav_menu'];
                                if($term2 = get_term($mid, 'nav_menu')){
                                    $res['widgets'][$k][$w]['nav_menu'] = $term2->slug;
                                }
                            }
                        }
                    }
                }
            }

            // 其他信息，比如分类、首页
            $res['show_on_front'] = get_option( 'show_on_front' );
            if($res['show_on_front']=='page'){
                $page = get_post(get_option( 'page_on_front' ));
                $res['page_on_front'] = $page->post_name;
            }

            $res['options'] = $this->options;
            echo json_encode($res);
            exit;
        }
    }
}

$wpcom_panel = new WPCOM_Panel();