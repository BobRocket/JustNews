<?php get_header();?>
    <div class="main container">
        <div class="content">
            <div class="sec-panel archive-list">
                <div class="sec-panel-head">
                    <h1>
                        <?php
                        $kw = get_search_query();
                        $keword = $kw!='' ? $kw : __('None', 'wpcom');
                        echo sprintf( __('Search for: %s', 'wpcom'), $keword);
                        ?>
                    </h1>
                </div>
                <ul class="article-list">
                    <?php if( have_posts() && $kw!='' ) : ?>
                        <?php while( have_posts() ) : the_post();?>
                            <?php get_template_part( 'templates/list' , 'default' ); ?>
                        <?php endwhile; ?>
                        <?php wpcom_pagination(5);?>
                    <?php elseif( $kw!='' ): ?>
                        <p style="padding: 20px 0 30px;"><?php _e("Sorry, but nothing matched your search terms. Please try again with some different keywords.", 'wpcom');?></p>
                    <?php else : ?>
                        <p style="padding: 20px 0 30px;"><?php _e('Please type your keyword(s) to search.', 'wpcom'); ?></p>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <aside class="sidebar">
            <?php get_sidebar();?>
        </aside>
    </div>
<?php get_footer();?>