<?php
/*
Plugin Name: Easy Comment Uploads
Plugin URI: http://wordpress.org/extend/plugins/easy-comment-uploads/
Description: Allow your users to easily upload images in their comments.
Author: Tom Wright
Version: 0.32
Author URI: http://twright.langtreeshout.org/
*/

// TODO: Find better way of sharing info between scripts
//$upload_dir =  get_option('upload_path') . '/comments/';
$upload_dir =  WP_CONTENT_DIR . '/upload/';
//$upload_url = get_option('siteurl') . '/wp-content/uploads/comments/';
$upload_url = get_option('siteurl') . '/wp-content/upload/';
$plugin_dir = dirname(__FILE__) . '/';
$images_only = (get_option ('ecu_images_only') || false);
$permission_required = get_option ('ecu_permission_required');
$hide_comment_form = get_option ('ecu_hide_comment_form') || false;
$settings_files_dir = (file_exists ("/tmp") ? "/tmp/" : "/temp") . "easy_comment_uploads/"; 

// Determine whether all settings files exist
$settings_files_exist =
	!file_exists ($settings_files_dir . 'upload_url.txt') 
	|| !file_exists ($settings_files_dir . 'upload_dir.txt')
	|| !file_exists ($settings_files_dir . 'upload_images.txt');

// Update any files that do not already exist
// TODO: Reduce I/O to increase performance
if (!file_exists ($upload_dir))
	mkdir ($upload_dir);
if (!file_exists ($settings_files_dir))
	mkdir ($settings_files_dir);
ecu_update_settings_files ();

// Remove insecure files left over from old versions
if (file_exists ($upload_dir . 'upload.php'))
	unlink ($upload_dir . 'upload.php');

// Replaces [img] tags in comments with linked images (with lightbox support)
// Accepts either [img]image.png[/img] or [img=image.png]
// Also accepts [file] for not images
// Thanks to Trevor Fitzgerald (http://www.trevorfitzgerald.com/) for providing
// an invaluable example for this regualar expersions code.
function ecu_insert_links ($content) {
	$content = preg_replace('/\[img=?\]*(.*?)(\[\/img)?\]/e', '"<a href=\"$1\" rel=\"lightbox[comments]\"> <img class=\"ecu_images\" src=\"$1\" style=\"max-height: 250px; max-width: 360px; padding: 5px 0 5px 0\" alt=\"" . basename("$1") . "\" /></a>"', $content);
	$content = preg_replace('/\[file=?\]*(.*?)(\[\/file)?\]/e', '"<a href=\"$1\">$1</a>"', $content);
	return $content;
}

function ecu_plugin_address () {
	return get_option ('siteurl') 
		. '/wp-content/plugins/easy-comment-uploads/upload.php';
}

// Core upload form
function ecu_upload_form_core (
		$prompt='Select File: '
	) {
	echo "
	<form target='hiddenframe' enctype='multipart/form-data'
	action='" . ecu_plugin_address () . "' method='POST' name='uploadform'
	id='uploadform' style='text-align : center'>
		<label for='file' name='prompt'>$prompt</label>
		<input type='file' name='file' id='file'
			onchange='document.uploadform.submit ();
			document.uploadform.file.value = \"\"' />
	</form>

	<iframe name='hiddenframe' style='display : none'></iframe>
	";
}

// Placeholder for preview of uploaded files
function ecu_upload_form_uploadedfiles ($display=true) {
	echo "<p id='uploadedfile' " . ($display ? "" : "style='display:none'") 
		. "></p>";
}

// Complete upload form
function ecu_upload_form_full ($title, $msg, $prompt,
		$pre, $post) {
	echo "
	
	<!-- Easy Comment Uploads for Wordpress by Tom Wright: http://wordpress.org/extend/plugins/easy-comment-uploads/ -->

	$pre

	<div id='ecu_uploadform'>

	<h3 name='title' style='clear : both'>$title</h3>

	<div name='message'>$msg</div>
	
	";

	ecu_upload_form_core ($prompt);

	ecu_upload_form_uploadedfiles ();

	echo "
	</div>

	$post
	
	<!-- End of Easy Comment Uploads -->
	";
}

// Default comment form
function ecu_upload_form_helper () {
	global $permission_required;

	if (!(
		$permission_required == 'none'
		|| !$permission_required
		|| current_user_can ("$permission_required")
	)) return;

	ecu_upload_form_full (
		__('Upload Files', 'easy-comment-uploads'), // $title
		'<p>' .  __('You can include images or files in your comment by selecting them below. Once you select a file, it will be uploaded and a link to it added to your comment. You can upload as many images or files as you like and they will all be added to your comment.', 'easy-comment-uploads') . '</p>', // $msg
		__('Select File', 'easy-comment-uploads') . ': ', // $prompt
		'</form>', // $pre -- work arround for nested forms
		'<form>' // $post - more work arround
	);
}

// Set textdomain for translations (i18n)
function textdomain_easy_comment_uploads () {
	load_plugin_textdomain ('easy-comment-uploads'
		,'wp-content/plugins/easy-comment-uploads/', 'easy-comment-uploads/');
}

// Add options menu item (restricted to level_10 users)
function ecu_options () {
    if (current_user_can ("level_10"))
	add_options_page('Easy Comment Uploads options', 'Easy Comment Uploads',
	    8, __FILE__, 'ecu_options_page');
}

// Options page
function ecu_options_page () {
	global $images_only;
	global $permission_required;
	global $hide_comment_form;

	// Handle post (if submitted)
	if (isset($_POST['submitted']))
	{
		// Check permissions
		check_admin_referer ('easy-comment-uploads');

		// Update $images_only
		$images_only = (int) ($_POST['images_only'] != null);
		update_option ('ecu_images_only', $images_only);

		// Update $permission_required
		if (isset ($_POST['permission_required']))
		    $permission_required = $_POST['permission_required'];
		update_option ('ecu_permission_required', $permission_required);

		// Update $hide_comment_form
		$hide_comment_form = (int) ($_POST['hide_comment_form'] != null);
		update_option ('ecu_hide_comment_form', $hide_comment_form);

		// Updated visuals
		$msg_status = __('Easy Comment Uploads options saved.');
		echo '<div id="message" class="updated fade"><p>' . $msg_status
			. '</p></div>';

		// Force update settings text files
		ecu_update_settings_files (true);
	}

	$actionurl = $_SERVER['REQUEST_URI'];
	$nonce = wp_create_nonce ('easy-comment-uploads');
	$images_only_html = ($images_only) ? 'checked' : '';
	$hide_comment_form_html = ($hide_comment_form ? 'checked' : '');
	$permission_required_html
		= array ((!$permission_required || ($permission_required == "none"))
		    ? "checked" : "",
		($permission_required == "read") ? "checked" : "",
		($permission_required == "edit_posts") ? "checked" : "",
		($permission_required == "upload_files") ? "checked" : "");
	
	echo <<<END
		<div class="wrap" style="max-width:950px !important;">
		<h2>Easy Comment Uploads</h2>

		<form name="ecuform" action="$action_url" method="post">
			<input type="hidden" name="submitted" value="1" />
			<input type="hidden" id="_wpnonce" name="_wpnonce" value="$nonce" />

			<p>
			<div><input id="images_only" type="checkbox" name="images_only" $images_only_html />
			<label for="images_only">Only allow images to be uploaded.</label></div>
			</p>
			
			<p>
			<div><input id="all_users" type="radio" name="permission_required" value="none" $permission_required_html[0] />
			<label for="all_users">Allow all users to upload files with their comments.</label></div>
		
			<div><input id="registered_users_only" type="radio" name="permission_required" value="read" $permission_required_html[1] />
			<label for="registered_users_only">Only allow registered users to upload files.</label></div>
		
			<div><input id="edit_rights_only" type="radio" name="permission_required" value="edit_posts" $permission_required_html[2] />
			<label for="edit_rights_only">Require "Contributor" rights to upload files.</label></div>
		
			<div><input id="upload_rights_only" type="radio" name="permission_required" value="upload_files" $permission_required_html[3] />
			<label for="upload_rights_only">Require "Upload" rights to uploads files (e.g. only admin, editors and authors).</label></div>
			</p>

			<p>
			<div><input id="hide_comment_form" type="checkbox" name="hide_comment_form" $hide_comment_form_html />
			<label for="hide_comment_form">Hide from comment forms</div>
			</p>

			<div class="submit"><input type="submit" name="Submit" value="Update options" /></div>
		</form>
END;
	echo "
	<div style='margin : auto auto auto 2em; width : 40em;
	 background-color : ghostwhite; border : 1px dashed gray;
	 padding : 0 1em 0 1em'>
	";
	ecu_upload_form_helper ();
	echo "</div>";
}

function ecu_update_settings_files ($force = false)
{
	global $settings_files_exist;
	global $images_only;
	global $upload_dir;
	global $settings_files_dir;
	global $upload_url;

	$error_string = "easy-comment-uploads: can't open file - please check the permissions of your wordpress install.";

	if ($settings_files_exist || $force) {
		$upload_url_file = fopen ($settings_files_dir . 'upload_url.txt', 'w')
		    or trigger_error ($error_string);
		fwrite ($upload_url_file, $upload_url);
		fclose ($upload_url_file);

		$upload_dir_file = fopen ($settings_files_dir . 'upload_dir.txt', 'w')
		    or trigger_error ($error_string);
		fwrite ($upload_dir_file, $upload_dir);
		fclose ($upload_dir_file);

		$images_only_file = fopen ($settings_files_dir . 'images_only.txt', 'w') 
		    or trigger_error ($error_string);
		fwrite ($images_only_file, (int) $images_only);
		fclose ($images_only_file);
	}
}

// Register code with wordpress
add_action('admin_menu', 'ecu_options');
add_filter('comment_text', 'ecu_insert_links');
if (! $hide_comment_form) add_action('comment_form', 'ecu_upload_form_helper');
add_action('init', 'textdomain_easy_comment_uploads'); 

?>
