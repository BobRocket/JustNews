<?php
// TEMPLATE NAME: 无标题
get_header();?>
    <div class="main container">
        <div class="content">
            <?php while( have_posts() ) : the_post();?>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?> style="padding: 0;">
                    <div class="entry">
                        <div class="entry-content clearfix">
                            <?php the_content();?>
                        </div>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
        <aside class="sidebar">
            <?php get_sidebar();?>
        </aside>
    </div>
<?php get_footer();?>