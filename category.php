<?php
$tpl = get_term_meta( $cat, 'wpcom_tpl', true );

if ( $tpl && locate_template('cat-tpl-' . $tpl . '.php') != '' ) {
    get_template_part( 'cat-tpl', $tpl );
} else {
    get_template_part( 'cat-tpl', 'default' );
}