<?php
defined( 'ABSPATH' ) || exit;

class WPCOM_Module{
    private $options;
    function __construct($id, $name, $options = array(), $icon='', $cache = true) {
        $this->id = $id;
        $this->name = $name;
        $this->options = $options;
        $this->icon = $icon;
        $this->is_cache = $cache;
        add_action( 'init', array( $this, '_register_module' ) );

        add_action( 'save_post', array( $this, 'flush_module_cache' ) );
        add_action( 'deleted_post', array( $this, 'flush_module_cache' ) );
        add_action( 'switch_theme', array( $this, 'flush_module_cache' ) );
    }

    function display( $atts, $depth = 0 ){
        if ( $this->get_cached_module( $atts ) ) return;
        ob_start();

        $classes = 'modules-'.$this->id;
        $more_classes = $this->classes( $atts, $depth );
        $classes .= $more_classes ? ' ' . $more_classes : '';
        ?>
        <section class="section wpcom-modules <?php echo $classes;?>" id="modules-<?php echo $atts['modules-id'];?>" <?php echo $this->_style_inline($atts); ?>>
            <?php $this->template($atts, $depth);?>
        </section>
        <?php echo $this->cache_module( $atts, ob_get_clean() );
    }

    function style_inline_default( $atts ){
        $style = '';
        if(isset($atts['margin-top'])) $style .= 'margin-top: '.$atts['margin-top'].';';
        if(isset($atts['margin-bottom'])) $style .= 'margin-bottom: '.$atts['margin-bottom'].';';
        if(isset($atts['padding-top'])) $style .= 'padding-top: '.$atts['padding-top'].';';
        if(isset($atts['padding-bottom'])) $style .= 'padding-bottom: '.$atts['padding-bottom'].';';
        return $style;
    }

    function _style_inline( $atts ){
        $style = '';
        $default = $this->style_inline_default( $atts );
        $more = $this->style_inline( $atts );
        if($default || $more) $style = 'style="'.$default.''.$more.'"';
        return $style;
    }

    function classes( $atts, $depth ){
        $classes = $depth==0 ? 'container' : '';
        return $classes;
    }

    function module_options( $modules ){
        $modules->{$this->id} = array(
            'name'  => $this->name,
            'icon'  => $this->icon,
            'options' => $this->options
        );
        return $modules;
    }

    function _register_module(){
        add_action('wpcom_modules_'.$this->id, array( $this, 'display' ), 10, 2);
        add_filter('wpcom_modules', array( $this, 'module_options' ));
    }

    function template($atts, $depth){}
    function style($atts){}
    function style_inline($atts){}

    public function get_cached_module( $args ) {
        if( !$this->is_cache || is_customize_preview() ) return false;
        $cache = wp_cache_get( $this->get_module_id_for_cache( $this->id ), 'module' );

        if ( ! is_array( $cache ) ) {
            $cache = array();
        }

        if ( isset( $cache[ $this->get_module_id_for_cache( $args['modules-id'] ) ] ) ) {
            echo $cache[ $this->get_module_id_for_cache( $args['modules-id'] ) ];
            return true;
        }

        return false;
    }

    public function cache_module( $args, $content ) {
        if( !$this->is_cache || is_customize_preview() ) return $content;
        $cache = wp_cache_get( $this->get_module_id_for_cache( $this->id ), 'module' );
        if ( ! is_array( $cache ) ) {
            $cache = array();
        }
        $cache[ $this->get_module_id_for_cache( $args['modules-id'] ) ] = $content;
        wp_cache_set( $this->get_module_id_for_cache( $this->id ), $cache, 'module' );
        return $content;
    }

    public function flush_module_cache() {
        foreach ( array( 'https', 'http' ) as $scheme ) {
            wp_cache_delete( $this->get_module_id_for_cache( $this->id, $scheme ), 'module' );
        }
    }

    protected function get_module_id_for_cache( $module_id, $scheme = '' ) {
        $module_id = get_queried_object_id() . '-' .$module_id;
        if ( $scheme ) {
            $module_id_for_cache = $module_id . '-' . $scheme;
        } else {
            $module_id_for_cache = $module_id . '-' . ( is_ssl() ? 'https' : 'http' );
        }

        return apply_filters( 'wpcom_cached_module_id', $module_id_for_cache );
    }
}

if( !function_exists('register_module') ){
    function register_module( $module_class ){
        global $wpcom_modules;
        if(!isset($wpcom_modules)) $wpcom_modules = array();
        $module = new $module_class();
        $wpcom_modules[$module->id] = $module;
    }
}