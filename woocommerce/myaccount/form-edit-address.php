<?php
/**
 * Edit address form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-edit-address.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

$page_title = ( 'billing' === $load_address ) ? __( 'Billing address', 'woocommerce' ) : __( 'Shipping address', 'woocommerce' );

do_action( 'woocommerce_before_edit_account_address_form' ); ?>


<form method="post">
    <?php
    $notices = isset($GLOBALS['edit-notice']) ? $GLOBALS['edit-notice'] : '';
    $error = isset($GLOBALS['edit-error']) ? $GLOBALS['edit-error'] : 0;
    $notice_html = '';
    if($notices) {
        $i = 0;
        foreach ( $notices as $notice ){
            $notice_html .= ($i==0?'':'<li>') .wp_kses_post($notice).($i==(count($notices)-1)?'':'</li>');
            $i++;
        }
    }
    if($notice_html) wc_print_notice( $notice_html, $error?'error':'success' );
    ?>

    <div class="woocommerce-address-fields">
        <?php do_action( "woocommerce_before_edit_address_form_{$load_address}" ); ?>

        <div class="woocommerce-address-fields__field-wrapper">
            <?php
            foreach ( $address as $key => $field ) {
                woocommerce_form_field( $key, $field, wc_get_post_data_by_key( $key, $field['value'] ) );
            }
            ?>
        </div>

        <?php do_action( "woocommerce_after_edit_address_form_{$load_address}" ); ?>

        <p>
            <button type="submit" class="button" name="save_address" value="<?php esc_attr_e( 'Save address', 'woocommerce' ); ?>"><?php esc_html_e( 'Save address', 'woocommerce' ); ?></button>
            <?php wp_nonce_field( 'woocommerce-edit_address', 'woocommerce-edit-address-nonce' ); ?>
            <input type="hidden" name="action" value="edit_address" />
        </p>
    </div>
</form>

<?php do_action( 'woocommerce_after_edit_account_address_form' ); ?>
