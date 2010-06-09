<?php
/*
Plugin Name: Easy Comment Uploads
Plugin URI: http://wordpress.org/extend/plugins/easy-comment-uploads/
Description: Allow your users to easily upload images and files in their comments.
Author: Tom Wright
Version: 0.52
Author URI: http://twright.langtreeshout.org/
*/

// Replaces [img] tags in comments with linked images (with lightbox support)
// Accepts either [img]image.png[/img] or [img=image.png]
// Also accepts [file] for other files
// Thanks to Trevor Fitzgerald (http://www.trevorfitzgerald.com/) for providing an invaluable example for
// this regualar expersions code.
function ecu_insert_links ($s) {
	 $s = preg_replace('/\[img\](.*?)\[\/img\]/', '<a href="$1" rel="lightbox[comments]"> <img class="ecu_images" src="$1" /></a>', $s);
	$s = preg_replace('/\[file\](.*?)\[\/file\]/', '<a href="$1">$1</a>', $s);
	return $s;
}

// Get url of plugin
function ecu_plugin_url () {
	return plugins_url ('easy-comment-uploads/');
}

// Core upload form
function ecu_upload_form_core ($prompt='Select File: ') {
	echo "
	<form target='hiddenframe' enctype='multipart/form-data'
	action='" . ecu_plugin_url () . 'upload.php' 
	.  "' method='POST' name='uploadform'
	id='uploadform' style='text-align : center'>
		" . wp_nonce_field ('ecu_upload_form') . "
		<label for='file' name='prompt'>$prompt</label>
		<input type='file' name='file' id='file'
			onchange='document.uploadform.submit ();
			document.uploadform.file.value = \"\"' />
	</form>

	<iframe name='hiddenframe' style='display : none'></iframe>
	";
}

// Placeholder for preview of uploaded files
function ecu_upload_form_preview ($display=true) {
	echo "<p id='ecu_preview' " . ($display ? "" : "style='display:none'") 
		. "></p>";
}

// Complete upload form
function ecu_upload_form ($title, $msg, $prompt, $pre, $post, $check=true) {
	if ( !ecu_allow_upload () && $check ) return;

	echo "
	<!-- Easy Comment Uploads for Wordpress by Tom Wright: http://wordpress.org/extend/plugins/easy-comment-uploads/ -->
	$pre
	
	<div id='ecu_uploadform'>
	<h3 class='title'>$title</h3>
	<div class='message'>$msg</div>
	";

	ecu_upload_form_core ($prompt);

	ecu_upload_form_preview ();

	echo "
	</div>
	
	$post
	<!-- End of Easy Comment Uploads -->
	";
}

// Default comment form
function ecu_upload_form_default ($check=true) {
	ecu_upload_form (
		__('Upload Files', 'easy-comment-uploads'), // $title
		'<p>' .  __('You can include images or files in your comment by selecting them below. Once you select a file, it will be uploaded and a link to it added to your comment. You can upload as many images or files as you like and they will all be added to your comment.', 'easy-comment-uploads') . '</p>', // $msg
		__('Select File', 'easy-comment-uploads') . ': ', // $prompt
		'</form>', // $pre -- work arround for nested forms
		'<form>', // $post - more work arround
		$check
	);
}

// Add options menu item (restricted to level_10 users)
function ecu_options_menu () {
	if (current_user_can ("level_10"))
		add_options_page ('Easy Comment Uploads options',
			'Easy Comment Uploads', 8, __FILE__, 'ecu_options_page');
}

function ecu_options_page () {
	if (isset ($_POST['submitted'])) {
		check_admin_referer ('easy-comment-uploads');

		// Update options
		update_option ('ecu_images_only', $_POST['images_only'] != null);
		if (isset ($_POST['permission_required']))
			update_option ('ecu_permission_required', 
				$_POST['permission_required']);
		update_option ('ecu_hide_comment_form',
			(int) ($_POST['hide_comment_form'] != null));
		if (isset ($_POST ['max_file_size']) 
			&& preg_match ('/[0-9]+/', $_POST ['max_file_size'])
			&& $_POST ['max_file_size'] >= 0)
			update_option ('ecu_max_file_size', $_POST ['max_file_size']);
		if (isset ($_POST ['enabled_pages'])
			&& preg_match ('/^(all)|(([0-9]+ )*[0-9]+)$/', $_POST ['enabled_pages']))
			update_option ('ecu_enabled_pages', $_POST ['enabled_pages']);

		// Inform user
		echo '<div id="message" class="updated fade"><p>' 
			. __('Easy Comment Uploads options saved.')
			. '</p></div>';
	}

	// Info for form
	$actionurl = $_SERVER['REQUEST_URI'];
	$nonce_field = wp_nonce_field ('easy-comment-uploads');

	// Store current values for fields
	$images_only = (get_option ('ecu_images_only')) ? 'checked' : '';
	$hide_comment_form = (get_option ('ecu_hide_comment_form') ? 'checked' : '');
	$premission_required = array ();
	foreach (array ('none', 'read', 'edit_posts', 'upload_files') as $elem)
		$permission_required [] = ((get_option('ecu_permission_required') == $elem) ? 'checked' : '');
	$max_file_size = get_option ('ecu_max_file_size');
	$enabled_pages = get_option ('ecu_enabled_pages');

	echo <<<END
		<div class="wrap" style="max-width:950px !important;">
		<h2>Easy Comment Uploads</h2>

		<form name="ecuform" action="$action_url" method="post">
			<input type="hidden" name="submitted" value="1" />
			$nonce_field

			<p>
			<div><input id="images_only" type="checkbox" name="images_only" $images_only />
			<label for="images_only">Only allow images to be uploaded.</label></div>
			</p>
			
			<p>
			<div><input id="all_users" type="radio" name="permission_required" value="none" $permission_required[0] />
			<label for="all_users">Allow all users to upload files with their comments.</label></div>
		
			<div><input id="registered_users_only" type="radio" name="permission_required"
				value="read" $permission_required[1] />
			<label for="registered_users_only">Only allow registered users to upload files.</label></div>
		
			<div><input id="edit_rights_only" type="radio" name="permission_required"
				value="edit_posts" $permission_required[2] />
			<label for="edit_rights_only">Require "Contributor" rights to upload files.</label></div>
		
			<div><input id="upload_rights_only" type="radio" name="permission_required"
				value="upload_files" $permission_required[3] />
			<label for="upload_rights_only">Require "Upload" rights to uploads files 
				(e.g. only admin, editors and authors).</label></div>
			</p>

			<p>
			<div><input id="hide_comment_form" type="checkbox" name="hide_comment_form" $hide_comment_form />
			<label for="hide_comment_form">Hide from comment forms</div>
			</p>

			<p>
			Limit the size of uploaded files:
			<input id="max_file_size" type="text" name="max_file_size" value="$max_file_size" />
			<label for="max_file_size">(KiB, 0 = unlimited)</label></div>
			</p>
			
			<p>
			Only allow uploads on these pages:
			<input id="enabled_pages" type="text" name="enabled_pages" value="$enabled_pages" />
			<label for="enabled_pages">(<a href="http://www.techtrot.com/wordpress-page-id/">page_ids</a> seperated with spaces or 'all' to enable globally)</label>
			</p>

			<div class="submit"><input type="submit" name="Submit" value="Update options" /></div>
		</form>
END;
	echo "
	<div style='margin : auto auto auto 2em; width : 40em;
	 background-color : ghostwhite; border : 1px dashed gray;
	 padding : 0 1em 0 1em'>
	";
	ecu_upload_form_default (false);
	echo "</div>";
}

function ecu_upload_dir_path () {
	$upload_dir = wp_upload_dir ();
	return $upload_dir ['path'] . '/'; // . '/comments/';
}

function ecu_upload_dir_url () {
	$upload_dir = wp_upload_dir ();
	return $upload_dir ['url'] . '/'; // . '/comments/';
}

// Are uploads allowed?
function ecu_allow_upload () {
	global $post;
	$permission_required = get_option('ecu_permission_required');
	$enabled_pages = get_option('ecu_enabled_pages');

	return ($permission_required == 'none'
	    || current_user_can ($permission_required))
		&& (in_array ($post->ID, explode(' ', $enabled_pages))
			|| $enabled_pages == "all");
}

// Set options to defaults, if not already set
function ecu_initial_options () {
	ecu_textdomain ();
	wp_enqueue_style ('ecu', ecu_plugin_url () . 'style.css');
	(get_option ('ecu_permission_required') === true) || add_option ('ecu_permission_required', 'none');
	(get_option ('ecu_hide_comment_form') === true) || add_option ('ecu_hide_comment_form', 0);
	(get_option ('ecu_images_only') === true) || add_option ('ecu_images_only', 0);
	(get_option ('ecu_max_file_size') === true) || add_option ('ecu_max_file_size', 0);
	(get_option ('ecu_enabled_pages') === true) || add_option ('ecu_enabled_pages', 'all');
}

// Set textdomain for translations (i18n)
function ecu_textdomain () {
	load_plugin_textdomain ('easy-comment-uploads'
		,'wp-content/plugins/easy-comment-uploads/', 'easy-comment-uploads/');
}

// Register code with wordpress
add_action ('admin_menu', 'ecu_options_menu');
add_filter ('comment_text', 'ecu_insert_links');
if (! get_option ('ecu_hide_comment_form'))
	add_action('comment_form', 'ecu_upload_form_default');
// add_action('init', 'textdomain_easy_comment_uploads');
add_action('init', 'ecu_initial_options');
