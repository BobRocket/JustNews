/**
 * Created by Lomu on 17/3/9.
 */

(function($) {
    $(document).ready(function() {
        $('#j-form').on('click', '.j-thumb', function () {
            var uploader;
            if (uploader) {
                uploader.open();
            } else {
                uploader = wp.media.frames.file_frame = wp.media({
                    title: "选择图片",
                    button: {
                        text: "插入图片"
                    },
                    library: {
                        type: "image"
                    },
                    multiple: false
                });
                uploader.on("select", function () {
                    var attachment = uploader.state().get("selection").first().toJSON();
                    var img = "<img src=\"" + attachment.url + "\" width=\"" + attachment.width + "\" height=\"" + attachment.height + "\"><div class=\"thumb-remove j-thumb-remove\">×</div>";
                    $('#j-thumb-wrap').html(img);
                    $('#_thumbnail_id').val(attachment.id);
                });
                uploader.open();
            }
        }).on('click', '.j-thumb-remove', function () {
            $('#j-thumb-wrap').html('');
            $('#_thumbnail_id').val('');
        }).on('submit', function () {
            var error = 0;
            $('#post-tags').remove();
            $("<input>", {
                type: 'hidden',
                name: 'post-tags',
                id: 'post-tags',
                val: $("#tag-container").tagHandler("getSerializedTags")
            }).appendTo(this);
            if (typeof tinyMCE != "undefined") {
                tinyMCE.triggerSave();
                var ed = tinyMCE.activeEditor;
                ed.off('change').on('change',function (ed) {
                    $('.wp-editor-wrap').removeClass('error');
                });
            }
            var title = $.trim($('#post-title').val());
            var content = $.trim($('#post-content').val());
            var category = $('#post-category').val();

            if (title == '') {
                $('#post-title').addClass('error');
                error = 1;
            }
            if (content == '') {
                $('.wp-editor-wrap').addClass('error');
                error = 1;
            }
            if (!category) {
                $('#post-category').addClass('error');
                error = 1;
            }
            if (error) {
                return false;
            }
        }).on('input propertychange', '.form-control', function () {
            $(this).removeClass('error');
        });

        $("#tag-container").tagHandler({assignedTags: postTags});
    });
})(jQuery);