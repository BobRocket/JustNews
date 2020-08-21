<?php
defined( 'ABSPATH' ) || exit;

class WPCOM_Shortcodes {

    public function __construct() {
        $this->init_hooks();
    }

    private function init_hooks(){
        if (current_user_can('edit_posts') || current_user_can('edit_pages')) {
            add_action('load-post.php', array($this, 'init_plugin'));
            add_action('load-post-new.php', array($this, 'init_plugin'));
            add_action('wp_ajax_wpcom_mce_button', array($this, 'mce_button_js'));
            add_action('wp_ajax_wpcom_mce_panel', array($this, 'mce_panel'));
            add_action('wp_ajax_wpcom_sc_save', array($this, 'sc_save'));
        }
    }

    public function init_plugin(){
        add_action( 'admin_print_footer_scripts', array($this, 'admin_print_footer_scripts'), 100);
        add_filter( 'mce_external_plugins', array($this, 'mce_plugin'));
        add_filter( 'mce_buttons', array($this, 'mce_button'));
    }

    public function mce_button_js(){
        header("Content-type: text/javascript");
        echo '(function($) {
            tinymce.create("tinymce.plugins.wpcom_shortcodes", {
                init : function(ed, url) {
                    var _this = this;

                    // Set the shortcode url
                    this.shortcode_url = "'.FRAMEWORK_URI.'";

                    // Register example button
                    ed.addButton("wpcom_shortcodes", {
                        id : "wpcom_shortcode_button",
                        title : "添加组件",
                        //cmd : "wpcom_shortcodes",
                        image : this.shortcode_url + "/assets/images/shortcodes.png",
                        onclick: function(){
                            $("#sc-iframe").html(\'<iframe class="sc-iframe" frameborder="0" src="'.admin_url('admin-ajax.php?action=wpcom_mce_panel&post=\'+$(\'#post_ID\').val()+\'').'"></iframe>\');
                            $("#sc-modal").show();
                            jQuery("body").addClass("modal-open");
                        }
                    });
                    $("body").append(\'<div class="modal" id="sc-modal"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button><h4 class="modal-title">添加组件</h4></div><div class="modal-body" id="sc-iframe"></div></div></div></div>\');
                },
                getInfo : function() {
                    return {
                        longname : "WPCOM组件添加插件",
                        author : "Lomu",
                        authorurl : "https://www.wpcom.cn",
                        infourl : "http://www.tinymce.com/wiki.php/API3:method.tinymce.Plugin.init",
                        version : "1.0"
                    };
        }
        });
        // Register plugin
        tinymce.PluginManager.add("wpcom_shortcodes", tinymce.plugins.wpcom_shortcodes);
        })(jQuery);';
        exit;
    }
    public function mce_plugin($plugin_array){
        $plugin_array['wpcom_shortcodes'] = admin_url('admin-ajax.php?action=wpcom_mce_button');
        add_editor_style(FRAMEWORK_URI.'/assets/css/style.editor.css');
        return $plugin_array;
    }
    public function mce_button($buttons){
        array_push( $buttons, 'separator', 'wpcom_shortcodes');
        return $buttons;
    }

    public function mce_panel(){
        wp_enqueue_style("panel", FRAMEWORK_URI."/assets/css/shortcodes.css", false, FRAMEWORK_VERSION, "all");
        wp_enqueue_script("angular", 'https://cdn.bootcss.com/angular.js/1.6.10/angular.min.js', array(), FRAMEWORK_VERSION, true);
        wp_enqueue_script("froala", FRAMEWORK_URI."/assets/js/froala_editor.min.js", array('angular'), FRAMEWORK_VERSION, true);
        wp_enqueue_script("shortcodes", FRAMEWORK_URI."/assets/js/shortcodes.js", array('jquery', 'angular', 'froala'), FRAMEWORK_VERSION, true);

        wp_enqueue_media();
        $id = isset($_GET['id'])?$_GET['id']:'';
        $code =  isset($_GET['code'])?$_GET['code']:'';
        $pid =  isset($_GET['post'])?$_GET['post']:'';
        include FRAMEWORK_PATH . '/html/shortcode.php';
        exit;
    }

    public function sc_save(){
        $data = $_POST;
        if(isset($data['post_id'])) {
            if (isset($data['type']) && isset($data['data'])) {
                if (isset($data['sc_id']) && $data['sc_id']) {
                    $sc_id = $data['sc_id'];
                    if (!preg_match('/_scode_/i', $sc_id)) {
                        $time = microtime();
                        list($t1, $t2) = explode(' ', $time);
                        $sc_id = '_scode_' . date('ymdHis', $t2) . sprintf("%03d", floor($t1 * 1000));
                    }
                } else {
                    $time = microtime();
                    list($t1, $t2) = explode(' ', $time);
                    $sc_id = '_scode_' . date('ymdHis', $t2) . sprintf("%03d", floor($t1 * 1000));
                }

                update_post_meta($data['post_id'], $sc_id, maybe_serialize(wp_unslash($data['data'])));

                $res = array('id' => $sc_id);
                echo wp_json_encode($res);
            }
        }
        exit;
    }

    public function admin_print_footer_scripts() {
        echo "<script type=\"text/javascript\">
        (function($){
            var tags = ['gird', 'panel', 'tabs', 'accordion'];
            wp.mce = wp.mce || {};
            for(var i = 0; i<tags.length; i++){
                (function(i){
                    wp.mce[tags[i]] = {
                        edit: function( data, update ) {
                            var sc_data = wp.shortcode.next(tags[i], data);
                            var values = sc_data.shortcode.attrs.named;
                            values['shortcode'] = sc_data.shortcode.tag;
                            if($('#sc-modal').length==0){
                                $('body').append('<div class=\"modal\" id=\"sc-modal\"><div class=\"modal-dialog modal-lg\"><div class=\"modal-content\"><div class=\"modal-header\"><button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button><h4 class=\"modal-title\">添加组件</h4></div><div class=\"modal-body\" id=\"sc-iframe\"></div></div></div></div>');
                            }
                            $('#sc-iframe').html('<iframe class=\"sc-iframe\" frameborder=\"0\" src=\"".admin_url("admin-ajax.php?action=wpcom_mce_panel&code=")."'+tags[i]+'&id='+values.id+'&post='+$('#post_ID').val()+'\"></iframe>');
                            $('#sc-modal').show();
                            jQuery('body').addClass('modal-open');
                        },
                        getContent: function(){
                            return '<div class=\"wpview-inner text-'+tags[i]+'\"></div>';
                        }
                    };
                    wp.mce.views.register( tags[i], wp.mce[tags[i]] );
                })(i);
            }
        }(jQuery));
        </script>";
    }
}