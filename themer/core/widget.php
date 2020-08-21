<?php
defined( 'ABSPATH' ) || exit;

abstract class WPCOM_Widget extends WP_Widget {
    public $widget_cssclass;
    public $widget_description;
    public $widget_id;
    public $widget_name;
    public $settings;

    public function __construct() {
        $widget_ops = array(
            'classname'   => $this->widget_cssclass,
            'description' => $this->widget_description,
            'customize_selective_refresh' => true,
        );

        parent::__construct( 'wpcom-'.$this->widget_id, $this->widget_name, $widget_ops );

        add_action( 'save_post', array( $this, 'flush_widget_cache' ) );
        add_action( 'deleted_post', array( $this, 'flush_widget_cache' ) );
        add_action( 'switch_theme', array( $this, 'flush_widget_cache' ) );
    }

    public function get_cached_widget( $args ) {
        $cache = wp_cache_get( $this->get_widget_id_for_cache( $this->widget_id ), 'widget' );

        if ( ! is_array( $cache ) ) {
            $cache = array();
        }

        if ( isset( $cache[ $this->get_widget_id_for_cache( $args['widget_id'] ) ] ) ) {
            echo $cache[ $this->get_widget_id_for_cache( $args['widget_id'] ) ];
            return true;
        }

        return false;
    }

    public function cache_widget( $args, $content ) {
        $cache = wp_cache_get( $this->get_widget_id_for_cache( $this->widget_id ), 'widget' );
        if ( ! is_array( $cache ) ) {
            $cache = array();
        }
        $cache[ $this->get_widget_id_for_cache( $args['widget_id'] ) ] = $content;
        wp_cache_set( $this->get_widget_id_for_cache( $this->widget_id ), $cache, 'widget' );
        return $content;
    }

    public function flush_widget_cache() {
        foreach ( array( 'https', 'http' ) as $scheme ) {
            wp_cache_delete( $this->get_widget_id_for_cache( $this->widget_id, $scheme ), 'widget' );
        }
    }

    public function widget_start( $args, $instance ) {
        echo $args['before_widget'];
        if ( $title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base ) ) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
    }

    public function widget_end( $args ) {
        echo $args['after_widget'];
    }

    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        if ( empty( $this->settings ) ) {
            return $instance;
        }

        // Loop settings and get values to save.
        foreach ( $this->settings as $key => $setting ) {
            if ( ! isset( $setting['type'] ) ) {
                continue;
            }

            // Format the value based on settings type.
            switch ( $setting['type'] ) {
                case 'number':
                    $instance[ $key ] = absint( $new_instance[ $key ] );

                    if ( isset( $setting['min'] ) && '' !== $setting['min'] ) {
                        $instance[ $key ] = max( $instance[ $key ], $setting['min'] );
                    }

                    if ( isset( $setting['max'] ) && '' !== $setting['max'] ) {
                        $instance[ $key ] = min( $instance[ $key ], $setting['max'] );
                    }
                    break;
                case 'textarea':
                    // $instance[ $key ] = wp_kses( trim( wp_unslash( $new_instance[ $key ] ) ), wp_kses_allowed_html( 'post' ) );
                    $instance[ $key ] = $new_instance[ $key ];
                    break;
                case 'checkbox':
                    $instance[ $key ] = empty( $new_instance[ $key ] ) ? 0 : 1;
                    break;
                default:
                    $instance[ $key ] = isset( $new_instance[ $key ] ) ? sanitize_text_field( $new_instance[ $key ] ) : $setting['std'];
                    break;
            }

            $instance[ $key ] = apply_filters( 'wpcom_widget_settings_sanitize_option', $instance[ $key ], $new_instance, $key, $setting );
        }

        $this->flush_widget_cache();

        return $instance;
    }

    public function form( $instance ) {

        if ( empty( $this->settings ) ) {
            return;
        }

        foreach ( $this->settings as $key => $setting ) {

            $class = isset( $setting['class'] ) ? $setting['class'] : '';
            $value = isset( $instance[ $key ] ) ? $instance[ $key ] : $setting['std'];

            switch ( $setting['type'] ) {

                case 'text':
                    ?>
                    <p>
                        <label for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"><?php echo $setting['label']; ?></label>
                        <input class="widefat <?php echo esc_attr( $class ); ?>" id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>" />
                    </p>
                    <?php
                    break;

                case 'number':
                    ?>
                    <p>
                        <label for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"><?php echo $setting['label']; ?></label>
                        <input class="widefat <?php echo esc_attr( $class ); ?>" id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>" type="number" step="<?php echo esc_attr( $setting['step'] ); ?>" min="<?php echo esc_attr( $setting['min'] ); ?>" max="<?php echo esc_attr( $setting['max'] ); ?>" value="<?php echo esc_attr( $value ); ?>" />
                    </p>
                    <?php
                    break;

                case 'select':
                    ?>
                    <p>
                        <label for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"><?php echo $setting['label']; ?></label>
                        <select class="widefat <?php echo esc_attr( $class ); ?>" id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>">
                            <?php foreach ( $setting['options'] as $option_key => $option_value ) : ?>
                                <option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( $option_key, $value ); ?>><?php echo esc_html( $option_value ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <?php
                    break;

                case 'textarea':
                    ?>
                    <p>
                        <label for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"><?php echo $setting['label']; ?></label>
                        <textarea class="widefat <?php echo esc_attr( $class ); ?>" id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>" cols="20" rows="3"><?php echo esc_textarea( $value ); ?></textarea>
                        <?php if ( isset( $setting['desc'] ) ) : ?>
                            <small><?php echo esc_html( $setting['desc'] ); ?></small>
                        <?php endif; ?>
                    </p>
                    <?php
                    break;

                case 'checkbox':
                    ?>
                    <p>
                        <input class="checkbox <?php echo esc_attr( $class ); ?>" id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>" type="checkbox" value="1" <?php checked( $value, 1 ); ?> />
                        <label for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"><?php echo $setting['label']; ?></label>
                    </p>
                    <?php
                    break;
                case 'upload':
                    wp_enqueue_media(); ?>
                    <script>
                        // Uploader
                        $('#widgets-right').off('click.upload').on('click.upload', '.upload-btn', function(e) {
                            e.preventDefault();
                            var uploader, id = $(this).attr('id');
                            if (uploader) {
                                uploader.open();
                            }else{
                                uploader = wp.media.frames.file_frame = wp.media({
                                    title: '选择文件',
                                    button: {
                                        text: '选择文件'
                                    },
                                    multiple: false
                                });
                                uploader.on('select', function() {
                                    var attachment = uploader.state().get('selection').first().toJSON();
                                    var inputId = id.replace(/_upload/i,'');
                                    $('#'+inputId).val(attachment.url).change();
                                });
                                uploader.open();
                            }
                        });
                    </script>
                    <p>
                        <label style="display: block" for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"><?php echo $setting['label']; ?></label>
                        <input id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>" type="text" value="<?php echo esc_url( $value ); ?>">
                        <button id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>_upload" type="button" class="button upload-btn">上传</button>
                    </p>
                    <?php break;
                // Default: run an action.
                default:
                    do_action( 'wpcom_widget_field_' . $setting['type'], $key, $value, $setting, $instance );
                    break;
            }
        }
    }

    protected function get_widget_id_for_cache( $widget_id, $scheme = '' ) {
        if ( defined( 'ICL_SITEPRESS_VERSION' ) ){ // WPML兼容
            $lang = defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : get_bloginfo( 'language' );
            $widget_id = $widget_id . '-' . $lang;
        }

        if ( $scheme ) {
            $widget_id_for_cache = $widget_id . '-' . $scheme;
        } else {
            $widget_id_for_cache = $widget_id . '-' . ( is_ssl() ? 'https' : 'http' );
        }

        return apply_filters( 'wpcom_cached_widget_id', $widget_id_for_cache );
    }
}

add_action('admin_menu', 'wpcom_widgets_add_prefix');
function wpcom_widgets_add_prefix() {
    global $_wp_sidebars_widgets;
    $sidebars_widgets = get_option('sidebars_widgets');
    $_widgets = $GLOBALS['wp_widget_factory']->widgets;
    if($sidebars_widgets){
        $_sidebars_widgets = array();
        foreach ($sidebars_widgets as $k => $widgets){
            if($widgets && is_array($widgets)) {
                $_sidebars_widgets[$k] = array();
                foreach ($widgets as $widget) {
                    $added = 0;
                    $_widget = $widget;
                    $widget = preg_replace('/-[\d]+$/i', '', $widget);
                    foreach ($_widgets as $w) {
                        if (isset($w->widget_id) && $w->widget_id == $widget && $w->id_base == 'wpcom-' . $w->widget_id) {
                            $_sidebars_widgets[$k][] = 'wpcom-' . $_widget;
                            preg_match('/(.*)-(\d+)$/i', $_widget, $matches);
                            update_option('widget_' . $w->id_base, get_option('widget_' . $matches[1]));
                            $added = 1;
                            break;
                        }
                    }
                    if (!$added) $_sidebars_widgets[$k][] = $_widget;
                }
            }else{
                $_sidebars_widgets[$k] = $widgets;
            }
        }

        if($sidebars_widgets != $_sidebars_widgets) {
            $_wp_sidebars_widgets = null;
            update_option( 'sidebars_widgets', $_sidebars_widgets );
        }
    }
}