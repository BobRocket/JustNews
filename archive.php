<?php get_header();?>
    <div class="main container">
        <div class="content">
            <div class="sec-panel archive-list">
                <div class="sec-panel-head">
                    <h1><?php if (is_category()) { ?><?php single_cat_title(); ?>
                        <?php } elseif( is_tag() ) { ?>
                            <?php echo sprintf( __( '%s' , 'wpcom' ), single_cat_title('', false) ) ?>
                        <?php } elseif( is_author() ) { ?><?php echo get_the_author(); ?>
                        <?php } elseif (is_day()) { ?><?php echo sprintf( __( 'Daily Archives: %s' , 'wpcom' ), get_the_date() ) ?>
                        <?php } elseif (is_month()) { ?><?php echo sprintf( __( 'Monthly Archives: %s' , 'wpcom' ), get_the_date(__( 'F Y', 'wpcom' )) ) ?>
                        <?php } elseif (is_year()) { ?><?php echo sprintf( __( 'Yearly Archives: %s' , 'wpcom' ), get_the_date(__( 'Y', 'wpcom' )) ) ?>
                        <?php } ?></h1>
                </div>
                <ul class="article-list">
                        <?php while( have_posts() ) : the_post();?>
                            <?php get_template_part( 'templates/list' , 'default' ); ?>
                        <?php endwhile; ?>
                </ul>
                <?php wpcom_pagination(5);?>
            </div>
        </div>
        <aside class="sidebar">
            <?php get_sidebar();?>
        </aside>
    </div>
<?php get_footer();?>