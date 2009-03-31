<?php
/*
Plugin Name: Easy Comment Uploads
Plugin URI: http://wiki.langtreeshout.org/plugins/commentuploads
Description: Allow your users to easily upload images in their comments.
Author: Tom Wright
Version: 0.05
Author URI: http://langtreeshout.org/
*/

// Replaces [img] tags in comments with linked images (with lightbox support)
// Accepts either [img]image.png[/img] or [img=image.png]
// Thanks to Trevor Fitzgerald (http://) for providing an invaluable example for
// this regualar expersions code.
function insert_images($content){
    $content = preg_replace('/\[img=?\]*(.*?)(\[\/img)?\]/e', '"<a href=\"$1\" /* rel=\"lightbox[comments]\" */> <img src=\"$1\" width=\"600px\" alt\"" . basename("$1") . "\" /></a>"', $content);
return $content;
}

// Inserts an iframe below the comment upload form which allows users to upload
// files and returns a [img] link.
function image_upload_form(){
    echo ("
        <br>
        Select and image to upload and paste the code produced into your comment:
        <iframe src='/wordpress/wp-content/plugins/comment-uploads/upload.html'
           width='500px' height='40' frameborder='0' scrolling='no'>
            <p>Your browser does not support iframes.</p>
        </iframe>
    ");
}

// Register code with wordpress
add_filter('comment_text', 'insert_images');
add_action('comment_form', 'image_upload_form');

?>
