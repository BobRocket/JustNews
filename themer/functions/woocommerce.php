<?php
defined( 'ABSPATH' ) || exit;

add_filter( 'woocommerce_enqueue_styles', '__return_false' );
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );

add_filter( 'woocommerce_ship_to_different_address_checked', '__return_false' );

add_filter( 'woocommerce_get_myaccount_page_permalink', 'wpcom_woo_myaccount_page_permalink' );
add_action( 'woocommerce_before_edit_account_address_form', 'wc_print_notices', 10 );


add_action( 'wp_enqueue_scripts', 'wpcom_woo_scripts' );
function wpcom_woo_scripts(){
    // 载入主样式
    if (function_exists('WC')) {
        wp_enqueue_style('wpcom-woo', get_template_directory_uri() . '/css/woocommerce.css', array(), THEME_VERSION);
        wp_enqueue_style('wpcom-woo-smallscreen', get_template_directory_uri() . '/css/woocommerce-smallscreen.css', array(), THEME_VERSION, 'only screen and (max-width: 768px)');
    }
}

add_filter('woocommerce_format_sale_price', 'woo_format_sale_price', 10, 3);
function woo_format_sale_price($price, $regular_price, $sale_price ) {
    $price = '<ins>' . ( is_numeric( $sale_price ) ? wc_price( $sale_price ) : $sale_price ) . '</ins> <del>' . ( is_numeric( $regular_price ) ? wc_price( $regular_price ) : $regular_price ) . '</del>';
    return $price;
}

add_filter( 'woocommerce_product_get_rating_html', 'woo_product_get_rating_html', 10, 2 );
function woo_product_get_rating_html($rating_html, $rating){
    if($rating<=0){
        $rating_html  = '<div class="star-rating"></div>';
    }
    return $rating_html;
}

add_action( 'wpcom_woo_cart_icon', 'wpcom_woo_cart_icon' );
function wpcom_woo_cart_icon() {
    global $options;
    if ( isset($options['show_cart']) && $options['show_cart']=='1' && function_exists('WC') ) {
        $count = WC()->cart->cart_contents_count;
        $html = '<a class="cart-contents fa fa-shopping-cart" href="'.wc_get_cart_url().'">';
        if ( $count > 0 ) {
            $html .= '<span class="shopping-count">' . esc_html( $count ) . '</span>';
        }
        $html .= '</a>';
        $html = apply_filters( 'wpcom_woo_cart_icon_html', $html, $count );
        echo '<div class="shopping-cart woocommerce">' . $html . '</div>';
    }
}

/**
 * Ensure cart contents update when products are added to the cart via AJAX
 */
add_filter( 'woocommerce_add_to_cart_fragments', 'wpcom_woo_icon_add_to_cart_fragment' );
function wpcom_woo_icon_add_to_cart_fragment( $fragments ) {
    $count = WC()->cart->cart_contents_count;
    $html = '<a class="cart-contents fa fa-shopping-cart" href="'.wc_get_cart_url().'">';
    if ( $count > 0 ) {
        $html .= '<span class="shopping-count">' . esc_html( $count ) . '</span>';
    }
    $html .= '</a>';

    $fragments['a.cart-contents'] = apply_filters( 'wpcom_woo_cart_icon_html', $html, $count );
    return $fragments;
}

add_filter( 'woocommerce_product_reviews_tab_title', 'wpcom_reviews_tab_title' );
function wpcom_reviews_tab_title( ) {
    global $product;
    return sprintf( __( 'Reviews (%d)', 'wpcom' ), $product->get_review_count() );
}

add_filter( 'woocommerce_checkout_fields' , 'wpcom_override_checkout_fields', 10, 1 );
function wpcom_override_checkout_fields( $fields ) {
    unset( $fields['billing']['billing_last_name'] );
    unset( $fields['billing']['billing_company'] );
    unset( $fields['billing']['billing_address_2'] );
    unset( $fields['billing']['billing_postcode'] );
    unset($fields['billing']['billing_email']);

    // billing address order
    $billing_order = array(
        "billing_first_name",
        "billing_phone",
        "billing_country",
        "billing_state",
        "billing_city",
        "billing_address_1",
    );

    $i=1;
    $billing_ordered_fields = array();
    foreach($billing_order as $field) {
        $fields["billing"][$field]['priority'] = $i*10;
        $billing_ordered_fields[$field] = $fields["billing"][$field];
        $i++;
    }

    $fields["billing"] = $billing_ordered_fields;
    $fields['billing']['billing_first_name']['label'] = __('Name', 'wpcom');

    return $fields;
}

add_filter( 'woocommerce_billing_fields', 'wpcom_woo_address_to_edit', 10, 2);
function wpcom_woo_address_to_edit( $address, $country ) {
    if($country=='CN') {
        unset($address['billing_last_name']);
        unset($address['billing_company']);
        unset($address['billing_address_2']);
        unset($address['billing_postcode']);
        unset($address['billing_email']);

        // billing address order
        $billing_order = array(
            "billing_first_name",
            "billing_phone",
            "billing_country",
            "billing_state",
            "billing_city",
            "billing_address_1",
        );

        $i = 1;
        $billing_ordered_fields = array();
        foreach ($billing_order as $field) {
            $address[$field]['priority'] = $i * 10;
            $billing_ordered_fields[$field] = $address[$field];
            $i++;
        }

        $address = $billing_ordered_fields;
        $address['billing_first_name']['label'] = __('Name', 'wpcom');
        $address['billing_address_1']['placeholder'] = __('Address', 'wpcom');
    }
    return $address;
}

add_filter( 'woocommerce_get_country_locale', 'wpcom_woo_default_address_fields_reorder', 10, 1 );
function wpcom_woo_default_address_fields_reorder( $fields ) {
    $fields['CN']['state']['priority'] = 50;
    $fields['CN']['state']['label'] = __('Province', 'wpcom');
    $fields['CN']['city']['priority'] = 60;
    $fields['CN']['city']['label'] = __('City', 'wpcom');
    $fields['CN']['address_1']['priority'] = 70;
    $fields['CN']['address_2']['priority'] = 80;
    return $fields;
}

add_filter( 'woocommerce_form_field_args', 'wpcom_woo_form_field_args', 10, 3);
function wpcom_woo_form_field_args($args, $key, $value){
    if( $args['type']=='state' && $value=='' && $args['country']=='CN'){
        $args['default'] = 'CN2';
    }
    return $args;
}

add_filter( 'woocommerce_form_field_country', 'wpcom_woo_form_field_country', 10, 3);
function wpcom_woo_form_field_country($field, $key, $args){
    $countries = 'shipping_country' === $key ? WC()->countries->get_shipping_countries() : WC()->countries->get_allowed_countries();

    if ( 1 === count( $countries ) ) {
         $field = str_replace('<p class="form-row ', '<p class="form-row hide ', $field);
    }
    return $field;
}

add_filter('woocommerce_localisation_address_formats', 'wpcom_woo_address_formats');
function wpcom_woo_address_formats($format){
    if( is_cart() ){
        $format['CN'] = "{state} {city}";
        $format['default'] = "{city}, {state}, {postcode}, {country}";
    }else{
        $format['CN'] = "<span class='addr-name'>{name} {phone}</span><span class='addr-detial'>{state}-{city}-{address_1}</span>";
        $format['default'] = "{name} {phone}\n{address_1}, {address_2}\n{city}, {state}, {postcode}, {country}";
    }
    return $format;
}

add_filter( 'woocommerce_formatted_address_replacements', 'wpcom_woo_formatted_address_replacements', 10, 2 );
function wpcom_woo_formatted_address_replacements($formatted_address, $arg){
    $formatted_address['{phone}'] = isset($arg['phone'])?$arg['phone']:'';
    return $formatted_address;
}

add_filter( 'woocommerce_account_menu_items', 'wpcom_woo_account_menu_items' );
function wpcom_woo_account_menu_items( $items ){
    global $options;
    if( isset($options['member_enable']) && $options['member_enable']=='1' ){
        $items['orders'] = __('Orders', 'wpcom');
        $items['downloads'] = __('Downloads', 'wpcom');
        $items['edit-address'] = __('Addresses', 'wpcom');
        unset($items['dashboard']);
        unset($items['edit-account']);
        unset($items['customer-logout']);
    }
    return $items;
}

add_filter( 'woocommerce_get_cancel_order_url', 'wpcom_woo_cancel_order_url' );
function wpcom_woo_cancel_order_url( $url ){
    global $options;
    if( isset($options['member_enable']) && $options['member_enable']=='1' ) {
        preg_match('/order_id=([\d]+)/i', $url, $matches);
        if(isset($matches[1]) && $matches[1]){
            $order    = wc_get_order( $matches[1] );
            $url = wp_nonce_url(
                add_query_arg(
                    array(
                        'cancel_order' => 'true',
                        'order' => $order->get_order_key(),
                        'order_id' => $order->get_id(),
                        'redirect' => wpcom_subpage_url('orders'),
                    ), $order->get_cancel_endpoint()
                ), 'woocommerce-cancel_order'
            );
        }
    }
    return $url;
}

add_filter('loop_shop_columns', 'wpcom_woo_shop_columns');
function wpcom_woo_shop_columns(){
    global $options;
    return isset($options['shop_list_col']) && $options['shop_list_col'] ? $options['shop_list_col'] : 4;
}

add_filter( 'body_class', 'wpcom_woo_body_class' );
function wpcom_woo_body_class( $classes ){
    if(!function_exists('is_woocommerce')) return $classes;
    global $options;
    $classes = (array) $classes;
    $class = '';
    if(is_singular( 'product' )) {
        $class = isset($options['related_col']) && $options['related_col'] ? 'columns-'.$options['related_col'] : 'columns-4';
    }else if(is_post_type_archive( 'product' ) || is_woocommerce()){
        $class = isset($options['shop_list_col']) && $options['shop_list_col'] ? 'columns-'.$options['shop_list_col'] : 'columns-4';
    }
    $classes[] = $class;
    return $classes;
}

add_filter( 'woocommerce_output_related_products_args', 'wpcom_woo_related_products_args');
add_filter( 'woocommerce_upsell_display_args', 'wpcom_woo_related_products_args');
function wpcom_woo_related_products_args( $args ){
    global $options;
    $args['columns'] = isset($options['related_col']) ? $options['related_col'] : 4;
    $args['posts_per_page'] = isset($options['related_posts_per_page']) ? $options['related_posts_per_page'] : 4;
    return $args;
}

add_filter( 'loop_shop_per_page', 'wpcom_woo_shop_per_page');
function wpcom_woo_shop_per_page( $posts ){
    global $options;
    return isset($options['shop_posts_per_page']) ? $options['shop_posts_per_page'] : $posts;
}

remove_action( 'woocommerce_archive_description', 'woocommerce_product_archive_description', 10 );
add_action( 'woocommerce_archive_description', 'wpcom_woo_archive_description', 10 );
function wpcom_woo_archive_description(){
    if ( is_search() ) {
        return;
    }

    if ( is_post_type_archive( 'product' ) ) {
        $shop_page   = get_post( wc_get_page_id( 'shop' ) );
        if ( $shop_page ) {
            $description = wc_format_content( $shop_page->post_content );
            if ( $description ) {
                echo '<div class="page-description">' . $description . '</div>';
            }
        }
    }
}

add_filter( 'woocommerce_is_account_page', 'wpcom_wc_is_account_page' );
function wpcom_wc_is_account_page( $res ){
    global $options;
    if( isset($options['member_enable']) && $options['member_enable']=='1' ) {
        return is_wpcom_member_page();
    }else{
        return $res;
    }
}

add_filter( 'wpcom_account_tabs', 'wpcom_woo_add_tabs' );
function wpcom_woo_add_tabs( $tabs ){
    if( !function_exists('is_woocommerce') ) return $tabs;

    $tabs[14] = array(
        'slug' => 'orders',
        'title' => __('Orders', 'wpcom'),
        'icon' => 'shopping-cart'
    );

    $tabs[15] = array(
        'slug' => 'downloads',
        'title' => __('Downloads', 'wpcom'),
        'icon' => 'cloud-download'
    );
    $tabs[16] = array(
        'slug' => 'edit-address',
        'title' => __('Addresses', 'wpcom'),
        'icon' => 'map-marker'
    );
    $tabs[9999] = array(
        'slug' => 'view-order',
        'title' => __('Orders', 'wpcom'),
        'icon' => 'shopping-cart',
        'parent' => 'orders'
    );

    return $tabs;
}

add_action( 'wpcom_account_tabs_orders', 'wpcom_account_tabs_orders' );
function wpcom_account_tabs_orders() {
    $page = get_query_var('pageid') ? get_query_var('pageid') : 1;
    ?>
    <div class="woocommerce">
        <?php do_action( 'woocommerce_account_orders_endpoint', $page ); ?>
    </div>
<?php }

add_action( 'wpcom_account_tabs_downloads', 'wpcom_account_tabs_downloads' );
function wpcom_account_tabs_downloads() { ?>
    <div class="woocommerce">
        <?php do_action( 'woocommerce_account_downloads_endpoint' ); ?>
    </div>
<?php }

add_action( 'wpcom_account_tabs_edit-address', 'wpcom_account_tabs_address' );
function wpcom_account_tabs_address() { ?>
    <div class="woocommerce">
        <?php do_action( 'woocommerce_account_edit-address_endpoint', 'billing' ); ?>
    </div>
<?php }

add_action( 'wpcom_account_tabs_view-order', 'wpcom_account_tabs_view_order' );
function wpcom_account_tabs_view_order() {
    $order_id = get_query_var('pageid') ? get_query_var('pageid') : 0; ?>
    <div class="woocommerce">
        <?php woocommerce_order_details_table($order_id); ?>
    </div>
<?php }

function wpcom_woo_myaccount_page_permalink( $link ){
    global $options;
    if( isset($options['member_enable']) && $options['member_enable']=='1' && $options['member_page_account'] ) {
        return wpcom_account_url();
    }
    return $link;
}

add_action( 'woocommerce_after_save_address_validation', 'wpcom_edit_address_notice' );
function wpcom_edit_address_notice(){
    $count = wc_notice_count( 'error' );
    $notice = array(__( 'Address changed successfully.', 'woocommerce' ));
    if( $count > 0 ){
        $notice = wc_get_notices('error');
    }
    $GLOBALS['edit-notice'] = $notice;
    $GLOBALS['edit-error'] = $count;
    wc_add_notice( 'error','error' );
}

add_filter( 'woocommerce_get_image_size_gallery_thumbnail', 'wpcom_woo_get_image_thumbnail' );
function wpcom_woo_get_image_thumbnail(){
    return wc_get_image_size( 'thumbnail' );
}

add_filter( 'woocommerce_get_image_size_single', 'wpcom_woo_get_image_single' );
function wpcom_woo_get_image_single( $size ){
    global $_wp_additional_image_sizes;
    if( isset($_wp_additional_image_sizes['woocommerce_single']) ) return $_wp_additional_image_sizes['woocommerce_single'];

    $size['width'] = absint( wc_get_theme_support( 'single_image_width', get_option( 'woocommerce_single_image_width', 800 ) ) );
    $cropping = get_option( 'woocommerce_thumbnail_cropping', '1:1' );

    if ( 'uncropped' === $cropping ) {
        $size['height'] = '';
        $size['crop']   = 0;
    } elseif ( 'custom' === $cropping ) {
        $width          = max( 1, get_option( 'woocommerce_thumbnail_cropping_custom_width', '4' ) );
        $height         = max( 1, get_option( 'woocommerce_thumbnail_cropping_custom_height', '3' ) );
        $size['height'] = absint( round( ( $size['width'] / $width ) * $height ) );
        $size['crop']   = 1;
    } else {
        $cropping_split = explode( ':', $cropping );
        $width          = max( 1, current( $cropping_split ) );
        $height         = max( 1, end( $cropping_split ) );
        $size['height'] = absint( round( ( $size['width'] / $width ) * $height ) );
        $size['crop']   = 1;
    }
    return $size;
}

// Place the code below in your theme's functions.php file
add_filter( 'woocommerce_get_catalog_ordering_args', 'wpcom_get_catalog_ordering_args' );
function wpcom_get_catalog_ordering_args( $args ) {
    $orderby_value = isset( $_GET['orderby'] ) ? wc_clean( $_GET['orderby'] ) : apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby' ) );
    if ( 'sales' == $orderby_value ) {
        $args['orderby'] = 'meta_value_num';
        $args['order'] = 'DESC';
        $args['meta_key'] = 'total_sales';
    }
    return $args;
}

add_filter( 'woocommerce_default_catalog_orderby_options', 'wpcom_catalog_orderby' );
add_filter( 'woocommerce_catalog_orderby', 'wpcom_catalog_orderby' );
function wpcom_catalog_orderby( $sortby ) {
    $sortby['sales'] = __('Sort by sales', 'wpcom');
    return $sortby;
}

add_filter( 'wc_product_sku_enabled', 'wpcom_product_sku_enabled', 20);
function wpcom_product_sku_enabled($res){
    global $product;
    if(!is_admin() && !$product->get_sku()){
        $res = false;
    }
    return $res;
}

/**
 * yith wishlist 兼容
 */

add_filter( 'yith_wcwl_no_product_to_remove_message', 'wpcom_yith_empty' );
function wpcom_yith_empty(){
    return __( 'No products were added to the wishlist', 'wpcom' );
}

add_filter( 'yith_wcwl_button_icon', 'wpcom_yith_wcwl_button_icon' );
function wpcom_yith_wcwl_button_icon( $icon ){
    $icon = 'fa-heart-o';
    return $icon;
}

add_filter( 'yith_wcwl_wishlist_title', '__return_false' );