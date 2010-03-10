<?php
/*
Plugin Name: Easy Comment Uploads
Plugin URI: http://wordpress.org/extend/plugins/easy-comment-uploads/
Description: Allow your users to easily upload images and files in their comments.
Author: Tom Wright
Version: 0.50
Author URI: http://twright.langtreeshout.org/
*/

ecu_initial_options ();

// Replaces [img] tags in comments with linked images (with lightbox support)
// Accepts either [img]image.png[/img] or [img=image.png]
// Also accepts [file] for other files
// Thanks to Trevor Fitzgerald (http://www.trevorfitzgerald.com/) for providing an invaluable example for
// this regualar expersions code.
function ecu_insert_links ($s) {
	 $s = preg_replace('/\[img=?\]*(.*?)(\[\/img)?\]/e', '"<a href=\"$1\" rel=\"lightbox[comments]\"> <img class=\"ecu_images\" src=\"$1\" style=\"max-height: 250px; max-width: 360px; padding: 5px 0 5px 0\" alt=\"" . basename("$1") . "\" /></a>"', $s);
	$s = preg_replace('/\[file=?\]*(.*?)(\[\/file)?\]/e', '"<a href=\"$1\">$1</a>"', $s);
	return $s;
}

function ecu_upload_url () {
	return get_option ('siteurl') 
		. '/wp-content/plugins/easy-comment-uploads/upload.php';
}

// Core upload form
function ecu_upload_form_core ($prompt='Select File: ') {
	echo "
	<form target='hiddenframe' enctype='multipart/form-data'
	action='" . ecu_upload_url () . "' method='POST' name='uploadform'
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
	echo "<p id='uploadedfile' " . ($display ? "" : "style='display:none'") 
		. "></p>";
}

// Complete upload form
function ecu_upload_form ($title, $msg, $prompt, $pre, $post, $check=true) {
	if ( !ecu_allow_upload () && $check ) return;

	echo "
	<!-- Easy Comment Uploads for Wordpress by Tom Wright: http://wordpress.org/extend/plugins/easy-comment-uploads/ -->
	$pre
	
	<div id='ecu_uploadform'>
	<h3 name='title' style='clear : both'>$title</h3>
	<div name='message'>$msg</div>
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
function ecu_upload_form_default () {
	if ( !ecu_allow_upload () ) return;

	ecu_upload_form (
		__('Upload Files', 'easy-comment-uploads'), // $title
		'<p>' .  __('You can include images or files in your comment by selecting them below. Once you select a file, it will be uploaded and a link to it added to your comment. You can upload as many images or files as you like and they will all be added to your comment.', 'easy-comment-uploads') . '</p>', // $msg
		__('Select File', 'easy-comment-uploads') . ': ', // $prompt
		'</form>', // $pre -- work arround for nested forms
		'<form>' // $post - more work arround
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
			<div>
			Limit the size of uploaded files:
			<input id="max_file_size" type="text" name="max_file_size" value="$max_file_size" />
			<label for="max_file_size">(KiB, 0 = unlimited)</label></div>
			</div>
			</p>

			<div class="submit"><input type="submit" name="Submit" value="Update options" /></div>
		</form>
END;
	echo "
	<div style='margin : auto auto auto 2em; width : 40em;
	 background-color : ghostwhite; border : 1px dashed gray;
	 padding : 0 1em 0 1em'>
	";
	ecu_upload_form_default ();
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
	return get_option ('ecu_permission_required') == 'none'
	    || current_user_can (get_option ('ecu_permission_required'));
}

// Set options to defaults, if not already set
function ecu_initial_options () {
	(get_option ('ecu_permission_required') === true) || add_option ('ecu_permission_required', 'none');
	(get_option ('ecu_hide_comment_form') === true) || add_option ('ecu_hide_comment_form', 0);
	(get_option ('ecu_images_only') === true) || add_option ('ecu_images_only', 0);
	(get_option ('ecu_max_file_size') === true) || add_option ('ecu_max_file_size', 0);
}

// Set textdomain for translations (i18n)
function textdomain_easy_comment_uploads () {
	load_plugin_textdomain ('easy-comment-uploads'
		,'wp-content/plugins/easy-comment-uploads/', 'easy-comment-uploads/');
}

// Register code with wordpress
add_action ('admin_menu', 'ecu_options_menu');
add_filter ('comment_text', 'ecu_insert_links');
if (! get_option ('ecu_hide_comment_form'))
	add_action('comment_form', 'ecu_upload_form_default');
add_action('init', 'textdomain_easy_comment_uploads');
