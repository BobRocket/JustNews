<?php
// TEMPLATE NAME: 文章投稿

$is_submit_page = 1;
$current_user = wp_get_current_user();
if(!$current_user->ID){
    wp_redirect( wp_login_url( get_permalink() ) );
    exit;
}

global $options;
$d = apply_filters('wpcom_update_post', array());
$post_id = isset($_GET['post_id'])?$_GET['post_id']:'';

if(!$post_id && !(isset($options['tougao_on']) && $options['tougao_on']=='1')) {
    wp_redirect(get_option('home'));
}

$item = $post_id ? get_post($post_id) : '';
$post_title = '';
$post_excerpt = '';
$post_content = '';
$post_category = array();
$post_tags = array();
$post_thumbnail_id = '';
$post_thumb = '';
if($item && isset($item->ID)){
    $post_title = $item->post_title;
    $post_excerpt = $item->post_excerpt;
    $post_content = $item->post_content;
    $tags = get_the_tags($item->ID);
    if($tags) {
        foreach ($tags as $tag) {
            $post_tags[] = $tag->name;
        }
    }
    $cats = get_the_category($item->ID);
    if($cats) {
        foreach ($cats as $cat) {
            $post_category[] = $cat->term_id;
        }
    }
    $post_thumbnail_id = get_post_thumbnail_id( $item->ID );
    $post_thumb = get_the_post_thumbnail($item->ID, 'full').'<div class="thumb-remove j-thumb-remove">×</div>';
}
get_header();?>
    <div class="main container" style="margin-bottom: 20px;">
        <?php if(!isset($_GET['post_id']) || ($post_id && $item && ($item->post_status=='draft' || $item->post_status=='pending' || ($item->post_status=='publish' && current_user_can('edit_published_posts')))) && $item->post_author==$current_user->ID){ ?>
        <form method="post" class="post-form" id="j-form">
            <?php if((isset($_POST['post-title']) || (isset($_GET['submit']) && $_GET['submit']=='true')) && $item && isset($item->ID)){ ?>
            <div style="margin: -10px 30px 20px;padding: 10px 20px 10px 15px;" class="alert alert-success alert-dismissible fade in" role="alert">
                <button style="right: -10px;top:0;" type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                提交成功<?php echo $item->post_status=='pending'?'，请等待审核':'';?>！您可以<a target="_blank" href="<?php echo get_permalink($item->ID);?>">点击此处</a>查看预览或者返回<a
                    target="_blank" href="<?php echo get_author_posts_url( $current_user->ID );?>">我的文章列表</a>。
            </div>
            <?php } wp_nonce_field( 'wpcom_update_post', 'wpcom_update_post_nonce' ); ?>
            <input type="hidden" name="ID" value="<?php echo $post_id;?>">
            <div class="post-form-main">
                    <div class="pf-item clearfix">
                        <div class="pf-item-label col-xs-12 col-sm-1"><label for="post-title">标题</label></div>
                        <div class="pf-item-input col-xs-12 col-sm-11">
                            <input type="text" class="form-control" maxlength="200" id="post-title" name="post-title" placeholder="在此输入标题" value="<?php echo $post_title;?>" autocomplete="off">
                        </div>
                    </div>
                    <div class="pf-item clearfix">
                        <div class="pf-item-label col-xs-12 col-sm-1"><label for="post-content">摘要</label></div>
                        <div class="pf-item-input col-xs-12 col-sm-11">
                            <textarea id="post-excerpt" name="post-excerpt" class="form-control" rows="3" placeholder="摘要可选填"><?php echo $post_excerpt;?></textarea>
                        </div>
                    </div>
                    <div class="pf-item clearfix">
                        <div class="pf-item-label col-xs-12 col-sm-1"><label for="post-content">正文</label></div>
                        <div class="pf-item-input col-xs-12 col-sm-11">
                            <?php wp_editor( $post_content, 'post-content', post_editor_settings(array('textarea_name'=>'post-content')) );?>
                        </div>
                    </div>
            </div>
            <div class="post-form-sidebar">
                <div class="pf-submit-wrap">
                    <input type="submit" value="<?php echo $post_id?'提交更新':'提交发布';?>" class="pf-submit">
                </div>
                <div class="pf-side-item">
                    <div class="pf-side-label"><h3>分类</h3></div>
                    <div class="pf-side-input">
                        <select multiple="multiple" size="5" id="post-category" name="post-category[]" class="form-control">
                            <?php
                            if(isset($options['tougao_cats']) && $options['tougao_cats']){
                                $cats = $options['tougao_cats'];
                            }else{
                                $cats = array();
                                foreach(WPCOM::category() as $cid=>$name){
                                    $cats[] = $cid;
                                }
                            }
                            foreach($cats as $cid){ ?>
                            <option value="<?php echo $cid;?>"<?php echo (in_array($cid,$post_category)?' selected':'')?>><?php echo get_cat_name($cid);?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="pf-side-item">
                    <div class="pf-side-label"><h3>标签</h3></div>
                    <div class="pf-side-input">
                        <ul id="tag-container"></ul>
                        <p class="pf-notice">即文章关键词，使用回车换行键确定，可选填</p>
                    </div>
                </div>
                <?php if(current_user_can('upload_files')){ ?>
                <div class="pf-side-item">
                    <div class="pf-side-label"><h3>缩略图</h3></div>
                    <div class="pf-side-input">
                        <div id="j-thumb-wrap" class="thumb-wrap"><?php echo $post_thumb;?></div>
                        <a class="thumb-selector j-thumb" href="javascript:;">设置缩略图片</a>
                        <p class="pf-notice">文章缩略图会显示在文章列表，建议设置一下缩略图</p>
                    </div>
                    <input type="hidden" name="_thumbnail_id" id="_thumbnail_id" value="<?php echo $post_thumbnail_id;?>">
                </div>
                <?php } ?>
            </div>
        </form>
        <?php }else{ ?>
            <div class="hentry">
                <p style="text-align:center;padding: 15px 0;font-size:16px;color:#999;">您无权限访问此页面！</p>
            </div>
        <?php } ?>
    </div>
    <script>
        var postTags = <?php echo json_encode($post_tags);?>;
    </script>
<?php get_footer();?>