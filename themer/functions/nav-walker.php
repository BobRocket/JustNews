<?php
/**
 * Class Name: WPCOM_Nav_Walker
 * GitHub URI: https://github.com/twittem/wp-bootstrap-navwalker
 * Description: A custom WordPress nav walker class to implement the Bootstrap 3 navigation style in a custom theme using the WordPress built in menu manager.
 * Version: 2.0.4
 * Author: Edward McIntyre - @twittem
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */
defined( 'ABSPATH' ) || exit;

class WPCOM_Nav_Walker extends Walker_Nav_Menu {

	/**
	 * @see Walker::start_lvl()
	 * @since 3.0.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int $depth Depth of page. Used for padding.
	 */
	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat( "\t", $depth );
        $class_names = 'dropdown-menu';
        if ( class_exists('WPCOM_Nav_Walker_Edit') && $depth===0 ) {
            $class_names .= ' menu-item-wrap';
            if( isset($args->child_count) && $args->child_count > 1 ) $class_names .= ' menu-item-col-' . ($args->child_count<6?$args->child_count:5);
        }
		$output .= "\n$indent<ul class=\"".$class_names."\">\n";
	}

	/**
	 * @see Walker::start_el()
	 * @since 3.0.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $item Menu item data object.
	 * @param int $depth Depth of menu item. Used for padding.
	 * @param int $current_page Menu item ID.
	 * @param object $args
	 */
	public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';
        $classes = empty( $item->classes ) ? array() : (array) $item->classes;

        if ( in_array( 'current-menu-item', $classes ) ||  in_array( 'current-menu-ancestor', $classes ) ||  in_array( 'current-post-ancestor', $classes )) {
            $classes[] = 'active';
        }

        $unset_classes = array('current-menu-item', 'current-menu-ancestor', 'current_page_ancestor', 'current_page_item', 'current_page_parent', 'current-menu-parent');
        foreach( $classes as $k => $class ){
            if( in_array($class, $unset_classes) ) unset($classes[$k]);
        }

        $class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args ) );

        if( class_exists('WPCOM_Nav_Walker_Edit') ){
            if ( $depth===0 && ! empty( $item->style ) ) {
                $class_names .= ' menu-item-style menu-item-style' . esc_attr($item->style);
            }

            if ( ! empty( $item->image ) ) {
                $class_names .= ' menu-item-has-image';
            }
        }

        if ( $args->has_children )
            $class_names .= ' dropdown';

        $class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

        $output .= $indent . '<li' . $class_names .'>';

        $atts = array();
        $atts['target'] = ! empty( $item->target )	? $item->target	: '';
        $atts['rel']    = ! empty( $item->xfn )		? $item->xfn	: '';

        // If item has_children add atts to a.
        if ( $args->has_children && $depth === 0 ) {
            $atts['href'] = ! empty( $item->url ) ? $item->url : '';
            $atts['class']			= 'dropdown-toggle';
        } else {
            $atts['href'] = ! empty( $item->url ) ? $item->url : '';
        }

        $atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args );

        $attributes = '';
        foreach ( $atts as $attr => $value ) {
            if ( ! empty( $value ) ) {
                $value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }

        $item_output = $args->before;

        /*
         * Glyphicons
         * ===========
         * Since the the menu item is NOT a Divider or Header we check the see
         * if there is a value in the attr_title property. If the attr_title
         * property is NOT null we apply it as the class name for the glyphicon.
         */
        if( trim($item->title) != '0' ) {
            if (!empty($item->attr_title))
                $item_output .= '<a' . $attributes . '><span class="fa fa-' . esc_attr($item->attr_title) . '"></span>&nbsp;';
            else
                $item_output .= '<a' . $attributes . '>';

            if (class_exists('WPCOM_Nav_Walker_Edit') && !empty($item->image)) {
                $item_output .= wpcom_lazyimg($item->image, $item->title, '', '', 'menu-item-image');
            }


            $item_output .= $args->link_before . apply_filters('the_title', $item->title, $item->ID) . $args->link_after;
            $item_output .= '</a>';
        }

        $item_output .= $args->after;

        $output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}

	/**
	 * Traverse elements to create list from elements.
	 *
	 * Display one element if the element doesn't have any children otherwise,
	 * display the element and its children. Will only traverse up to the max
	 * depth and no ignore elements under that depth.
	 *
	 * This method shouldn't be called directly, use the walk() method instead.
	 *
	 * @see Walker::start_el()
	 * @since 2.5.0
	 *
	 * @param object $element Data object
	 * @param array $children_elements List of elements to continue traversing.
	 * @param int $max_depth Max depth to traverse.
	 * @param int $depth Depth of current element.
	 * @param array $args
	 * @param string $output Passed by reference. Used to append additional content.
	 * @return null Null on failure with no changes to parameters.
	 */
	public function display_element( $element, &$children_elements, $max_depth, $depth, $args, &$output ) {
        if ( ! $element )
            return;

        $id_field = $this->db_fields['id'];

        // Display this element.
        if ( is_object( $args[0] ) ) {
            $args[0]->has_children = !empty($children_elements[$element->$id_field]);
            if( $depth==0 && $args[0]->has_children ){
                $args[0]->child_count = count($children_elements[$element->$id_field ]);
            }
        }

        parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
    }

	/**
	 * Menu Fallback
	 * =============
	 * If this function is assigned to the wp_nav_menu's fallback_cb variable
	 * and a manu has not been assigned to the theme location in the WordPress
	 * menu manager the function with display nothing to a non-logged in user,
	 * and will add a link to the WordPress menu manager if logged in as an admin.
	 *
	 * @param array $args passed from the wp_nav_menu function.
	 *
	 */
	public static function fallback( $args ) {
		if ( current_user_can( 'manage_options' ) ) {

			extract( $args );

			$fb_output = null;

			if ( $container ) {
				$fb_output = '<' . $container;

				if ( $container_id )
					$fb_output .= ' id="' . $container_id . '"';

				if ( $container_class )
					$fb_output .= ' class="' . $container_class . '"';

				$fb_output .= '>';
			}

			$fb_output .= '<ul';

			if ( $menu_id )
				$fb_output .= ' id="' . $menu_id . '"';

			if ( $menu_class )
				$fb_output .= ' class="' . $menu_class . '"';

			$fb_output .= '>';
			$fb_output .= '<li><a href="' . admin_url( 'nav-menus.php' ) . '">设置导航</a></li>';
			$fb_output .= '</ul>';

			if ( $container )
				$fb_output .= '</' . $container . '>';

			echo $fb_output;
		}
	}
}


add_filter( 'nav_menu_css_class', 'wpcom_nav_menu_css_class' );
function wpcom_nav_menu_css_class( $classes ){
    if($classes){
        $unset_classes = array('menu-item-type-post_type', 'menu-item-object-page', 'menu-item-object-category', 'menu-item-type-taxonomy', 'menu-item-object-custom', 'menu-item-type-custom', 'menu-item-has-children', 'page_item', 'menu-item-home');
        foreach( $classes as $k => $class ){
            if( in_array($class, $unset_classes) ) unset($classes[$k]);
        }
    }
    return $classes;
}