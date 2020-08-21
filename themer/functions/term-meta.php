<?php
defined( 'ABSPATH' ) || exit;

class wpcom_term_meta{
    public function __construct( $tax ) {

        $this->tax = $tax;

        add_action( $tax . '_add_form_fields', array($this, 'add'), 10, 2 );
        add_action( $tax . '_edit_form_fields', array($this, 'edit'), 10, 2 );
        add_action( 'created_' . $tax, array($this, 'save'), 10, 2 );
        add_action( 'edited_' . $tax, array($this, 'save'), 10, 2 );
    }

    function add(){
        // Load CSS
        wp_enqueue_style("themer-panel", FRAMEWORK_URI."/assets/css/panel.css", false, FRAMEWORK_VERSION, "all");
        wp_enqueue_style( 'wp-color-picker' );

        wp_enqueue_script("themer-panel", FRAMEWORK_URI . "/assets/js/panel.js", array('jquery', 'jquery-ui-core', 'wp-color-picker'), FRAMEWORK_VERSION, true);
        wp_enqueue_media();
        ?>

        <div id="wpcom-panel" class="wpcom-term-wrap"><term-panel :ready="ready"/></div>
        <script>_panel_options = <?php echo $this->get_term_metas(0);?>;</script>
        <div style="display: none;"><?php wp_editor( 'EDITOR', 'WPCOM-EDITOR', WPCOM::editor_settings(array('textarea_name'=>'EDITOR-NAME')) );?></div>
    <?php }

    function edit($term){
        // Load CSS
        wp_enqueue_style("themer-panel", FRAMEWORK_URI."/assets/css/panel.css", false, FRAMEWORK_VERSION, "all");
        wp_enqueue_style( 'wp-color-picker' );

        wp_enqueue_script("themer-panel", FRAMEWORK_URI . "/assets/js/panel.js", array('jquery', 'jquery-ui-core', 'wp-color-picker'), FRAMEWORK_VERSION, true);
        wp_enqueue_media();
        ?>

        <tr id="wpcom-panel" class="wpcom-term-wrap"><td colspan="2"><term-panel :ready="ready"/></td></tr>
        <tr style="display: none;"><th></th><td><script>_panel_options = <?php echo $this->get_term_metas($term->term_id);?></script>
                <div style="display: none;"><?php wp_editor( 'EDITOR', 'WPCOM-EDITOR', WPCOM::editor_settings(array('textarea_name'=>'EDITOR-NAME')) );?></div></td></tr>
    <?php }

    function save($term_id){
        $values = array();
        $_post = $_POST;
        foreach($_post as $key => $value) {
            if (preg_match('/^wpcom_/i', $key)) {
                $name = preg_replace('/^wpcom_/i', '', $key);
                $values[$name] = $value;
            }
        }
        if(!empty($values)){
            update_term_meta( $term_id, '_wpcom_metas', $values );
        }
    }

    function get_term_metas($term_id){
        $res = array('type' => 'taxonomy', 'tax' => $this->tax);
        if($term_id){
            $res['options'] = get_term_meta( $term_id, '_wpcom_metas', true );
        }
        $res['filters'] = apply_filters('wpcom_tax_metas', array());
        $res['ver'] = THEME_VERSION;
        $res['theme-id'] = THEME_ID;
        return json_encode($res);
    }
}

add_action('admin_init', 'wpcom_tax_meta');
function wpcom_tax_meta(){
    global $pagenow;
    if( ($pagenow == 'edit-tags.php' || $pagenow == 'term.php' || (isset($_POST['action']) && $_POST['action']=='add-tag'))  ) {
        $exclude_taxonomies = array('nav_menu', 'link_category', 'post_format');
        $taxonomies = get_taxonomies();
        foreach ($taxonomies as $key => $taxonomy) {
            if (!in_array($key, $exclude_taxonomies)) {
                new wpcom_term_meta($key);
            }
        }
    }
}