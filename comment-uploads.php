<?php
/*
Plugin Name: Easy Comment Uploads
Plugin URI: http://wiki.langtreeshout.org/plugins/commentuploads
Description: Allow your users to easily upload images in their comments.
Author: Tom Wright
Version: 0.10
Author URI: http://langtreeshout.org/
*/
//echo "test";
if( !defined('WP_CONTENT_DIR') )
    define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
//$upload_dir =  get_option('upload_path') . '/comments/';
$upload_dir =  WP_CONTENT_DIR . '/upload/';
//$upload_url = get_option('siteurl') . '/wp-content/uploads/comments/';
$upload_url = get_option('siteurl') . '/wp-content/upload/';
$upload_html = $upload_dir . 'upload.html';
$upload_php =  $upload_dir . 'upload.php';
//$upload_html_url = get_option('siteurl') . "/wp-content/uploads/comments/upload.html";
$upload_html_url = get_option('siteurl') . "/wp-content/upload/upload.html";

$plugin_dir = dirname(__FILE__) . '/';

if (!file_exists($upload_dir)){
	mkdir($upload_dir);
}

if ( !file_exists($upload_php))
copy($plugin_dir . "upload.php", $upload_php);

if ( !file_exists($upload_html))
copy($plugin_dir . "upload.html", $upload_html);

$fh = fopen($upload_dir . 'upload_url.txt', 'w') or die("can't open file");
fwrite($fh, $upload_url);

// Replaces [img] tags in comments with linked images (with lightbox support)
// Accepts either [img]image.png[/img] or [img=image.png]
// Thanks to Trevor Fitzgerald (http://) for providing an invaluable example for
// this regualar expersions code.
function insert_links($content){
$content = preg_replace('/\[img=?\]*(.*?)(\[\/img)?\]/e', '"<a href=\"$1\" rel=\"lightbox[comments]\"> <img src=\"$1\" style=\"max-width: 100%\" alt\"" . basename("$1") . "\" /></a>"', $content);
$content = preg_replace('/\[file=?\]*(.*?)(\[\/file)?\]/e', '"<a href=\"$1\">$1</a>"', $content);
return $content;
}

// Inserts an iframe below the comment upload form which allows users to upload
// files and returns a [img] link.
function comment_upload_form(){
		global $upload_html, $upload_html_url, $upload_php;
    echo ("
        <br />
        <strong>Upload files:</strong>
        <br />
        Select the file you want, click upload and paste the link produced into your comment
        <iframe src='" . $upload_html_url . "' width='500px' height='60px' scrolling='auto' frameborder='0'></iframe>
    ");
}

function comment_upload_deactivate() {
				global $upload_html, $upload_php;
        unlink($upload_html);
        unlink($upload_php);
//        unlink(WP_CONTENT_DIR . '/upload.html');
}

// Register code with wordpress
register_deactivation_hook( __FILE__, 'comment_upload_deactivate' );
add_filter('comment_text', 'insert_links');
add_action('comment_form', 'comment_upload_form');

?>
