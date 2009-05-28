<?php
/*
Plugin Name: Easy Comment Uploads
Plugin URI: http://wiki.langtreeshout.org/plugins/commentuploads
Description: Allow your users to easily upload images in their comments.
Author: Tom Wright
Version: 0.14
Author URI: http://twright.langtreeshout.org/
*/
if( !defined('WP_CONTENT_DIR') )
    define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
//$upload_dir =  get_option('upload_path') . '/comments/';
$upload_dir =  WP_CONTENT_DIR . '/upload/';
//$upload_url = get_option('siteurl') . '/wp-content/uploads/comments/';
$upload_url = get_option('siteurl') . '/wp-content/upload/';
$plugin_dir = dirname(__FILE__) . '/';

if (!file_exists($upload_dir))
	mkdir($upload_dir);
$fh = fopen($plugin_dir . 'upload_url.txt', 'w') or die("easy-comment-uploads: can't open file");
fwrite($fh, $upload_url);
fclose($fh);
$fhh = fopen($plugin_dir . 'upload_dir.txt', 'w') or die("easy-comment-uploads: can't open file");
fwrite($fhh, $upload_dir);
fclose($fhh);

// Replaces [img] tags in comments with linked images (with lightbox support)
// Accepts either [img]image.png[/img] or [img=image.png]
// Also accepts [file] for not images
// Thanks to Trevor Fitzgerald (http://www.trevorfitzgerald.com/) for providing an invaluable example for
// this regualar expersions code.
function insert_links($content){
    $content = preg_replace('/\[img=?\]*(.*?)(\[\/img)?\]/e', '"<a href=\"$1\" rel=\"lightbox[comments]\"> <img src=\"$1\" style=\"max-width: 100%\" alt=\"" . basename("$1") . "\" /></a>"', $content);
    $content = preg_replace('/\[file=?\]*(.*?)(\[\/file)?\]/e', '"<a href=\"$1\">$1</a>"', $content);
    return $content;
}

// Inserts an iframe below the comment upload form which allows users to upload
// files and returns a [img] or [file] link.
function comment_upload_form(){
    global $plugin_dir;
    @require ($plugin_dir . "form.php");
}

// Register code with wordpress
add_filter('comment_text', 'insert_links');
add_action('comment_form', 'comment_upload_form');

?>
