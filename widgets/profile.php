<?php
defined( 'ABSPATH' ) || exit;

class WPCOM_profile_widget extends WPCOM_Widget {
    public function __construct() {
        $this->widget_cssclass = 'widget_profile';
        $this->widget_description = '边栏用户信息简介，只在文章详情页显示';
        $this->widget_id = 'profile';
        $this->widget_name = '#用户信息';
        $this->settings = array(
            'number'      => array(
                'type'  => 'number',
                'step'  => 1,
                'min'   => 1,
                'max'   => '',
                'std'   => 5,
                'label' => '文章数量',
            ),
        );
        parent::__construct();
    }

    public function widget( $args, $instance ) {
        $num = empty( $instance['number'] ) ? $this->settings['number']['std'] : absint( $instance['number'] );

        if ( is_singular('post') ) :
            $author = get_the_author_meta( 'ID' );
            $author_url = get_author_posts_url( $author );
            $cover_photo = wpcom_get_cover_url( $author );
            $this->widget_start( $args, $instance );
            if($cover_photo) {
                echo '<div class="profile-cover">'.wpcom_lazyimg($cover_photo, get_the_author_meta('display_name')).'</div>';
            } else { ?>
                <div class="cover_photo"></div>
            <?php } ?>
            <div class="avatar-wrap">
                <a target="_blank" href="<?php echo $author_url; ?>" class="avatar-link"><?php echo get_avatar( $author, 120, '',  get_the_author());?></a></div>
            <div class="profile-info">
                <p><span class="author-name"><?php the_author_meta('display_name'); ?></span><?php $group = wpcom_get_user_group($author); if ( $group ) echo '<span class="author-group">'.$group->name.'</span>';?></p>
                <p class="author-description"><?php the_author_meta('description');?></p>
            </div>
            <div class="profile-posts">
                <h3 class="widget-title"><span>最近文章</span></h3>
                <?php
                global $post;
                $posts = get_posts( 'author='.$author.'&posts_per_page='.$num );
                if ($posts) : echo '<ul>'; foreach ( $posts as $post ) { setup_postdata( $post ); ?>
                    <li><a href="<?php echo esc_url( get_permalink() )?>" title="<?php echo esc_attr(get_the_title());?>"><?php the_title();?></a></li>
                <?php } echo '</ul>'; else :?>
                    <p style="color:#999;font-size: 12px;text-align: center;padding: 10px 0;margin:0;">暂无内容</p>
                <?php endif; wp_reset_postdata(); ?>
            </div>
            <?php $this->widget_end( $args );
        endif;
    }
}

// register widget
function register_wpcom_profile_widget() {
    register_widget( 'WPCOM_profile_widget' );
}
add_action( 'widgets_init', 'register_wpcom_profile_widget' );