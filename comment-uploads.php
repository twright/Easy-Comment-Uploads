<?php
/*
Plugin Name: Easy Comment Uploads
Plugin URI: http://wiki.langtreeshout.org/plugins/commentuploads
Description: Allow your users to easily upload images in their comments.
Author: Tom Wright
Version: 0.19
Author URI: http://twright.langtreeshout.org/
*/

// TODO: Find better way of sharing info between scripts
if( !defined('WP_CONTENT_DIR') )
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
//$upload_dir =  get_option('upload_path') . '/comments/';
$upload_dir =  WP_CONTENT_DIR . '/upload/';
//$upload_url = get_option('siteurl') . '/wp-content/uploads/comments/';
$upload_url = get_option('siteurl') . '/wp-content/upload/';
$plugin_dir = dirname(__FILE__) . '/';

// I/O TODO: Reduce I/O to increase performance
if (!file_exists($upload_dir))
	mkdir($upload_dir);

if (!file_exists($plugin_dir . 'upload_url.txt') ||  !file_exists($plugin_dir . 'upload_dir.txt')) {
	$fh = fopen($plugin_dir . 'upload_url.txt', 'w') or die("easy-comment-uploads: can't open file - please check the permissions of your wordpress install.");
	fwrite($fh, $upload_url);
	fclose($fh);
	$fhh = fopen($plugin_dir . 'upload_dir.txt', 'w') or die("easy-comment-uploads: can't open file - please check the permissions of your wordpress install.");
	fwrite($fhh, $upload_dir);
	fclose($fhh);
}

// Remove insecure files left over from old versions
if (file_exists($upload_dir . 'upload.php'))
	unlink ($upload_dir . 'upload.php');

// Replaces [img] tags in comments with linked images (with lightbox support)
// Accepts either [img]image.png[/img] or [img=image.png]
// Also accepts [file] for not images
// Thanks to Trevor Fitzgerald (http://www.trevorfitzgerald.com/) for providing an invaluable example for
// this regualar expersions code.
function insert_links($content){
	$content = preg_replace('/\[img=?\]*(.*?)(\[\/img)?\]/e', '"<a href=\"$1\" rel=\"lightbox[comments]\"> <img src=\"$1\" style=\"max-width: 360px\" alt=\"" . basename("$1") . "\" /></a>"', $content);
	$content = preg_replace('/\[file=?\]*(.*?)(\[\/file)?\]/e', '"<a href=\"$1\">$1</a>"', $content);
	return $content;
}

// Inserts an iframe below the comment upload form which allows
// users to upload files and returns a [img] or [file] link.
function comment_upload_form(){
	global $plugin_dir;
    echo "
		<!-- Easy Comment Uploads for Wordpress by Tom Wright: http://wordpress.org/extend/plugins/easy-comment-uploads/ -->

		</form>

		<h3 style='clear: both'>". __('Upload files', 'easy-comment-uploads') . ":</h3>
        <p style='margin-top: 4px'>" . __('You can include images or files in your comment by selecting them below. Once you select a file, it will be uploaded and a link to it added to your comment. You can upload as many images or files as you like and they will all be added to your comment.', 'easy-comment-uploads') . "</p>

        <form target='hiddenframe' enctype='multipart/form-data' action='" . get_option('siteurl') . "'/wp-content/plugins/easy-comment-uploads/upload.php' method='POST' name='uploadform' id='uploadform'>
        <p style='text-align: center; margin-bottom: -15px'>
    " . __('Select File', 'easy-comment-uploads') . ":
    <input type='file' name='file' id='fileField' onchange='document.uploadform.submit()' /></p>
    <p id='uploadedfile'>
    <label></label>
    </p>
    <iframe name='hiddenframe' style='display:none' >Loading...</iframe>

	<!-- End of Easy Comment Uploads -->";
}

// Set textdomain for translations (i18n)
function textdomain_easy_comment_uploads () {
    load_plugin_textdomain( 'easy-comment-uploads', 'wp-content/plugins/easy-comment-uploads/', 'easy-comment-uploads/' );
}

// Register code with wordpress
add_filter('comment_text', 'insert_links');
add_action('comment_form', 'comment_upload_form');
add_action('init', 'textdomain_easy_comment_uploads'); 

?>
