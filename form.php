
<!-- Easy Comment Uploads for Wordpress by Tom Wright: http://wordpress.org/extend/plugins/easy-comment-uploads/ -->

</form>

<h3 style="clear: both"><?php _e('Upload files', 'easy-comment-uploads') ?>:</h3>
<p style="margin-top: 4px"><?php _e('You can include images or files in your comment by selecting them below. Once you select a file, it will be uploaded and a link to it added to your comment. You can upload as many images or files as you like and they will all be added to your comment.', 'easy-comment-uploads') ?></p>

<form target="hiddenframe" enctype="multipart/form-data" action="<?php echo get_option('siteurl') . '/wp-content/plugins/easy-comment-uploads/' ?>upload.php" method="POST" name="uploadform" id="uploadform">
    <p style="text-align: center; margin-bottom: -15px">
    <?php _e('Select File', 'easy-comment-uploads') ?>:
    <input type="file" name="file" id="fileField" onchange="document.uploadform.submit()" /></p>
    <p id="uploadedfile">
    <label></label>
    </p>
    <iframe name="hiddenframe" style="display:none" >Loading...</iframe>

<!-- End of Easy Comment Uploads -->
