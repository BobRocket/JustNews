<form class="search-form" action="<?php echo get_bloginfo('url');?>" method="get" role="search">
    <input type="text" class="keyword" name="s" placeholder="<?php _e('Type your search here ...', 'wpcom');?>" value="<?php echo get_search_query(); ?>">
    <input type="submit" class="submit" value="&#xf002;">
</form>