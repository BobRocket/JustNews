<?php
// TEMPLATE NAME: 全宽模板-无标题
get_header();?>
    <div class="main container">
        <?php while( have_posts() ) : the_post();?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <div class="entry">
                    <div class="entry-content clearfix">
                        <?php the_content();?>
                    </div>
                </div>
            </article>
        <?php endwhile; ?>
    </div>
<?php get_footer();?>