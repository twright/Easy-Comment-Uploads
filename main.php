<?php
/*
Plugin Name: Easy Comment Uploads
Plugin URI: http://wordpress.org/extend/plugins/easy-comment-uploads/
Description: Allow your users to easily upload images and files in their comments.
Author: Tom Wright
Version: 0.61
Author URI: http://twright.langtreeshout.org/
*/

// Replaces [tags] with correct html
// Accepts either [img]image.png[/img] or [file]file.ext[/file] for other files
// Thanks to Trevor Fitzgerald's plugin (http://www.trevorfitzgerald.com/) for
// prompting the format used.
function ecu_insert_links($comment) {
    // Extract contents of tags
    preg_match_all('/\[(img|file)\]([^\]]*)\[\/\\1\]/i', $comment, $matches,
        PREG_SET_ORDER);
    foreach($matches as $match) {
        // Validate tags contain links of the correct format
        if (filter_var($match[2], FILTER_VALIDATE_URL)) {
            // Insert correct code based on tag
            if ($match[1] == 'img') {
                $html = "<a href='$match[2]' rel='lightbox[comments]'>"
                    . "<img class='ecu_images' src='$match[2]' /></a>";
            } elseif ($match[1] == 'file') {
                preg_match('/[^\/]*$/', $match[2], $filename);
                $name = get_option('ecu_show_full_file_path') ? $match[2]
                    : $filename[0];
                $html = "<a href='$match[2]'>File: $name</a>";
            }
            
            $comment = str_replace($match[0], $html, $comment);
        }
    }

    return $comment;
}

// Retrieve either a user created file extension blacklist or a default list of
// harmful extensions. This function allows the blacklist to be updated with
// the plugin if it has not been edited by the user.
function ecu_get_blacklist() {
    $default_blacklist = array('htm', 'html', 'shtml', 'mhtm', 'mhtml', 'js',
        'php', 'php3', 'php4', 'php5', 'php6', 'phtml',
        'cgi', 'fcgi', 'pl', 'perl', 'p6', 'asp', 'aspx',
        'htaccess',
        'py', 'python', 'exe', 'bat',  'sh', 'run', 'bin', 'vb', 'vbe', 'vbs');
    return get_option('ecu_file_extension_blacklist', $default_blacklist);
}

// Get user ip address
function ecu_user_ip_address() {
    if ($_SERVER['HTTP_X_FORWARD_FOR'])
        return $_SERVER['HTTP_X_FORWARD_FOR'];
    else
        return $_SERVER['REMOTE_ADDR'];
}

// Record upload time in user metadata or ip based array
function ecu_user_record_upload_time() {
    $time = time();
    if (is_user_logged_in()) {
        $times = get_user_meta(get_current_user_id(), 'ecu_upload_times',
            true);
        update_user_meta(get_current_user_id(), 'ecu_upload_times',
            ($times ? array_merge(array($time), $times) : array($time)));
    } else {
        $ip_upload_times = get_option('ecu_ip_upload_times');
        $ip = ecu_user_ip_address();

        if (array_key_exists($ip, $ip_upload_times)) {
            array_push($ip_upload_times[$ip], $time);
        } else {
            $ip_upload_times[$ip] = array($time);
        }
        update_option('ecu_ip_upload_times', $ip_upload_times);
    }
}

// Get the users hourly upload quota
function ecu_user_uploads_per_hour() {
    $uploads_per_hour = get_option('ecu_uploads_per_hour');
    foreach (get_option('ecu_uploads_per_hour') as $role => $x)
        if ($role == 'none' || current_user_can($role))
            return $x;
}

// Calculate the number of times which occured during the last hour
function ecu_user_uploads_in_last_hour() {
    // Get times either for current user or ip as available
    $ip_upload_times = get_option('ecu_ip_upload_times');
    $times = (is_user_logged_in()
        ? get_user_meta(get_current_user_id(), 'ecu_upload_times', true)
        : $ip_upload_times[ecu_user_ip_address()]);
    $i = 0; // Counter for uploads
    $now = time();
    foreach($times as $time)
        // If time passed less than or equal to 3600 s (1 hour), increment i
        if ($now - $time <= 3600) $i++;
    return $i;
}

// Get url of plugin
function ecu_plugin_url() {
    return plugins_url('easy-comment-uploads/');
}

// Core upload form
function ecu_upload_form_core($prompt='Select File: ') {
    echo "
    <form target='hiddenframe' enctype='multipart/form-data'
    action='" . ecu_plugin_url() . 'upload.php'
    .  "' method='POST' name='uploadform'
    id='uploadform' style='text-align : center'>
        " . wp_nonce_field('ecu_upload_form') . "
        <label for='file' name='prompt'>$prompt</label>
        <input type='file' name='file' id='file'
            onchange='document.uploadform.submit ();
            document.uploadform.file.value = \"\"' />
    </form>

    <iframe name='hiddenframe' style='display : none' frameborder='0'></iframe>
    ";
}

// Placeholder for preview of uploaded files
function ecu_upload_form_preview($display=true) {
    echo "<p id='ecu_preview' " . ($display ? "" : "style='display:none'")
        . "></p>";
}

// An iframe containing the upload form
function ecu_upload_form_iframe() {
    echo "<iframe style='width : 100%; height : 60px;"
        . " border: 0px solid #ffffff;' src='"
        . ecu_plugin_url () . "upload-form.php"
        . "' name='upload_form'></iframe>";
}

// Complete upload form
function ecu_upload_form($title, $msg, $prompt, $check=true) {
    if ( !ecu_allow_upload() && $check ) return;

    echo "
    <!-- Easy Comment Uploads for Wordpress by Tom Wright: http://wordpress.org/extend/plugins/easy-comment-uploads/ -->

    <div id='ecu_uploadform'>
    <h3 class='title'>$title</h3>
    <div class='message'>$msg</div>
    ";

    ecu_upload_form_iframe();

    ecu_upload_form_preview();

    echo "
    </div>

    <!-- End of Easy Comment Uploads -->
    ";
}

// Default comment form
function ecu_upload_form_default($prompt=true) {
    ecu_upload_form (
        __('Upload Files', 'easy-comment-uploads'), // $title
        '<p>' . ecu_message_text() . '</p>', // $msg
        __('Select File', 'easy-comment-uploads') . ': ', // $prompt
        $check, // $prompt
        true // $check
    );
}

// Get message text
function ecu_message_text() {
    if (get_option('ecu_message_text'))
        return get_option('ecu_message_text');
    else
        return __('You can include images or files in your comment by'
            . ' selecting them below. Once you select a file, it will be'
            . ' uploaded and a link to it added to your comment. You can'
            . ' upload as many images or files as you like and they will all'
            . ' be added to your comment.', 'easy-comment-uploads');
}

// Add options menu item (restricted to level_10 users)
function ecu_options_menu() {
    if (current_user_can("level_10"))
        add_options_page('Easy Comment Uploads options',
            'Easy Comment Uploads', 8, __FILE__, 'ecu_options_page');
}

// Provide an options page in wp-admin
function ecu_options_page() {
    // Handle changed options
    if (isset($_POST['submitted'])) {
        check_admin_referer ('easy-comment-uploads');

        // Update options
        update_option ('ecu_images_only', $_POST['images_only'] != null);
        if (isset($_POST['permission_required']))
            update_option ('ecu_permission_required',
                $_POST['permission_required']);
        update_option ('ecu_hide_comment_form',
            (int) ($_POST['hide_comment_form'] != null));
        update_option ('ecu_show_full_file_path',
            (int) ($_POST['show_full_file_path'] != null));
        if (isset($_POST['max_file_size'])
            && preg_match ('/[0-9]+/', $_POST['max_file_size'])
            && $_POST['max_file_size'] >= 0)
            update_option ('ecu_max_file_size', $_POST['max_file_size']);
        if (isset($_POST['upload_files_uploads_per_hour'])
            && preg_match ('/[-]?[0-9]+/', $_POST['upload_files_uploads_per_hour'])
            && $_POST['upload_files_uploads_per_hour'] >= -1)
            $uploads_per_hour = get_option('ecu_uploads_per_hour');
            $uploads_per_hour['upload_files'] = $_POST['upload_files_uploads_per_hour'];
            update_option('ecu_uploads_per_hour', $uploads_per_hour);
        if (isset($_POST['edit_posts_uploads_per_hour'])
            && preg_match ('/[-]?[0-9]+/', $_POST['edit_posts_uploads_per_hour'])
            && $_POST['edit_posts_uploads_per_hour'] >= -1)
            $uploads_per_hour = get_option('ecu_uploads_per_hour');
            $uploads_per_hour['edit_posts'] = $_POST['edit_posts_uploads_per_hour'];
            update_option('ecu_uploads_per_hour', $uploads_per_hour);
        if (isset($_POST['read_uploads_per_hour'])
            && preg_match ('/[-]?[0-9]+/', $_POST['read_uploads_per_hour'])
            && $_POST['read_uploads_per_hour'] >= -1)
            $uploads_per_hour = get_option('ecu_uploads_per_hour');
            $uploads_per_hour['read'] = $_POST['read_uploads_per_hour'];
            update_option('ecu_uploads_per_hour', $uploads_per_hour);
        if (isset($_POST['none_uploads_per_hour'])
            && preg_match ('/[-]?[0-9]+/', $_POST['none_uploads_per_hour'])
            && $_POST['none_uploads_per_hour'] >= -1)
            $uploads_per_hour = get_option('ecu_uploads_per_hour');
            $uploads_per_hour['none'] = $_POST['none_uploads_per_hour'];
            update_option('ecu_uploads_per_hour', $uploads_per_hour);
        if (isset($_POST['enabled_pages'])
            && preg_match('/^(all)|(([0-9]+ )*[0-9]+)$/', $_POST['enabled_pages']))
            update_option('ecu_enabled_pages', $_POST['enabled_pages']);
        if (isset($_POST['file_extension_blacklist'])
            && $_POST['file_extension_blacklist'] != implode(', ',
                ecu_get_blacklist())
            && preg_match('/^[a-z0-9]+([, ][ ]*[a-z0-9]+)*$/i',
            $_POST['file_extension_blacklist']))
            if ($_POST['file_extension_blacklist'] == 'default')
                delete_option('ecu_file_extension_blacklist');
            else if ($_POST['file_extension_blacklist'] == 'none')
                update_option('ecu_file_extension_blacklist', array());
            else update_option('ecu_file_extension_blacklist',
                preg_split("/[, ][ ]*/", $_POST['file_extension_blacklist']));
        if (isset($_POST['file_extension_whitelist'])
            && preg_match('/^[a-z0-9]+([, ][ ]*[a-z0-9]+)*$/i',
            $_POST['file_extension_whitelist']))
            if ($_POST['file_extension_whitelist'] == 'ignore')
                delete_option('ecu_file_extension_whitelist');
            else update_option('ecu_file_extension_whitelist',
                preg_split("/[, ][ ]*/", $_POST['file_extension_whitelist']));
        if (isset($_POST['upload_form_text']))
            update_option('ecu_message_text', $_POST['upload_form_text']);

        // Inform user
        echo '<div id="message" class="updated fade"><p>'
            . 'Easy Comment Uploads options saved.'
            . '</p></div>';
    }

    update_user_meta(get_current_user_id(), 'ecu_test', 'test');

    // Store current values for fields
    $images_only = (get_option('ecu_images_only')) ? 'checked' : '';
    $hide_comment_form = (get_option('ecu_hide_comment_form') ? 'checked' : '');
    $show_full_file_path = (get_option('ecu_show_full_file_path') ? 'checked' : '');
    $premission_required = array();
    foreach (array('none', 'read', 'edit_posts', 'upload_files') as $elem)
        $permission_required[] =
            ((get_option('ecu_permission_required') == $elem) ? 'checked' : '');
    $max_file_size = get_option('ecu_max_file_size');
    $enabled_pages = get_option('ecu_enabled_pages');
    $file_extension_blacklist = ecu_get_blacklist() ?
        implode(', ', ecu_get_blacklist()) : 'none';
    $file_extension_whitelist
        = get_option('ecu_file_extension_whitelist') === false
        ? 'ignore' : implode(', ', get_option('ecu_file_extension_whitelist'));
    $uploads_per_hour = get_option('ecu_uploads_per_hour');
    $upload_form_text = ecu_message_text();

    // Info for form
    $actionurl = $_SERVER['REQUEST_URI'];
    $nonce_field = wp_nonce_field('easy-comment-uploads');

    echo <<<END
        <div class="wrap" style="max-width:950px !important;">
        <h2>Easy Comment Uploads</h2>

        <form name="ecuform" action="$action_url" method="post">
            <input type="hidden" name="submitted" value="1" />
            $nonce_field

            <h3>Allowed Files</h3>

            <ul>
            <li><input id="images_only" type="checkbox" name="images_only" $images_only />
            <label for="images_only">Only allow images to be uploaded.</label></li>
            </p>

            <li>Limit the size of uploaded files:
            <input id="max_file_size" type="text" name="max_file_size" value="$max_file_size" />
            <label for="max_file_size">(KiB, 0 = unlimited)</label></li>

            <li>Blacklist the following file extensions:
            <input id="file_extenstion_blacklist" type="text" name="file_extension_blacklist" value="$file_extension_blacklist" />
            <br />
            <label for="file_extenstion_blacklist">(extensions seperated with spaces, 'none' to allow all (not recommended) or 'default' to restore the default list)</label>
            </li>

            <li>Allow only the following file extensions:
            <input id="file_extenstion_whitelist" type="text" name="file_extension_whitelist" value="$file_extension_whitelist" />
            <br />
            <label for="file_extension_whitelist">(extensions seperated with spaces or 'ignore' to disable the whitelist)</label>
            </li>
            </ul>

            <h3>User Permissions</h3>
            <ul>
            <li><input id="all_users" type="radio" name="permission_required" value="none" $permission_required[0] />
            <label for="all_users">Allow all users to upload files with their comments.</label></li>

            <li><input id="registered_users_only" type="radio" name="permission_required"
                value="read" $permission_required[1] />
            <label for="registered_users_only">Only allow registered users to upload files.</label></li>

            <li><input id="edit_rights_only" type="radio" name="permission_required"
                value="edit_posts" $permission_required[2] />
            <label for="edit_rights_only">Require "Contributor" rights to upload files.</label></li>

            <li><input id="upload_rights_only" type="radio" name="permission_required"
                value="upload_files" $permission_required[3] />
            <label for="upload_rights_only">Require "Upload" rights to uploads files
                (e.g. only admin, editors and authors).</label></li>
                
            <br />

            <li><table class="widefat">
                <tr>
                    <th></th>
                    <th>Uploads allowed per hour
                    <br /><em>(-1 = unlimited)</em></th>
                </tr>
                <tr>
                    <th>users with upload rights
                    <br /><em>(e.g. only admin, editors and authors)</em></th>
                    <td><input id="upload_files_uploads_per_hour" type="text" name="upload_files_uploads_per_hour" value="$uploads_per_hour[upload_files]" /></td>
                </tr>
                <tr>
                    <th>contributors</th>
                    <td><input id="edit_posts_uploads_per_hour" type="text" name="edit_posts_uploads_per_hour" value="$uploads_per_hour[edit_posts]" /></td>
                </tr>
                <tr>
                    <th>registered users</th>
                    <td><input id="read_uploads_per_hour" type="text" name="read_uploads_per_hour" value="$uploads_per_hour[read]" /></td>
                </tr>
                <tr>
                    <th>unregistered users</th>
                    <td><input id="none_uploads_per_hour" type="text"
                    name="none_uploads_per_hour"
                    value="$uploads_per_hour[none]" /></td>
                </tr>
            </table></li>
            </ul>

            <h3>Upload Form</h3>
            <ul>
            <li>
                <label for="upload_form_text">
                    Text explaining use of the upload form (leave blank for
                    default text):
                </label>
                <br />
                <textarea id="upload_form_text" name="upload_form_text"
                    style="width : 100%; height : 65pt"
                    >$upload_form_text</textarea>
            </li>

            <li>
                <input id="hide_comment_form" type="checkbox"
                name="hide_comment_form" $hide_comment_form />
                <label for="hide_comment_form">Hide from comment forms
            </li>

            <li>
                Only allow uploads on these pages:
                <input id="enabled_pages" type="text" name="enabled_pages"
                value="$enabled_pages" />
                <br />
                <label for="enabled_pages">
                (<a href="http://www.techtrot.com/wordpress-page-id/">page_ids</a>
                seperated with spaces or 'all' to enable globally)
                </label>
            </li>
            </ul>

            <h3>Comments</h3>
            <ul>
            <li>
                <input id="show_full_file_path" type="checkbox"
                name="show_full_file_path" $show_full_file_path />
                <label for="show_full_file_path">
                    Show full url in links to files
                </label>
            </li>
            </ul>

            <p class="submit"><input type="submit" class="button-primary"
            name="Submit" value="Save Changes" /></p>
        </form>
END;

    // Sample upload form
    echo "
    <div style='margin : auto auto auto 2em; width : 40em;
     background-color : ghostwhite; border : 1px dashed gray;
     padding : 0 1em 0 1em'>
    ";
    ecu_upload_form_default(false);
    echo "</div>";
}

function ecu_upload_dir_path() {
    $upload_dir = wp_upload_dir();
    return $upload_dir['path'] . '/'; // . '/comments/';
}

function ecu_upload_dir_url() {
    $upload_dir = wp_upload_dir();
    return $upload_dir['url'] . '/'; // . '/comments/';
}

// Are uploads allowed?
function ecu_allow_upload() {
    global $post;
    $permission_required = get_option('ecu_permission_required');
    $enabled_pages = get_option('ecu_enabled_pages');

    return ($permission_required == 'none'
        || current_user_can($permission_required))
        && (in_array ($post->ID, explode(' ', $enabled_pages))
            || $enabled_pages == "all");
}

// Set options to defaults, if not already set
function ecu_initial_options() {
    ecu_textdomain();
    wp_enqueue_style('ecu', ecu_plugin_url () . 'style.css');
    if (get_option('ecu_permission_required') === false)
        update_option('ecu_permission_required', 'none');
    if (get_option('ecu_show_full_file_path') === false)
        update_option('ecu_show_full_file_path', 0);
    if (get_option('ecu_hide_comment_form') === false)
        update_option('ecu_hide_comment_form', 0);
    if (get_option('ecu_images_only') === false)
        update_option('ecu_images_only', 0);
    if (get_option('ecu_max_file_size') === false)
        update_option('ecu_max_file_size', 0);
    if (get_option('ecu_enabled_pages') === false)
        update_option('ecu_enabled_pages', 'all');
    if (get_option('ecu_ip_upload_times') === false)
        update_option('ecu_ip_upload_times', array());
    if (get_option('ecu_message_text') === false)
        update_option('ecu_message_text', '');
    if (get_option('ecu_uploads_per_hour') === false)
        update_option('ecu_uploads_per_hour', array(
                'upload_files' => -1,
                'edit_posts' => 50,
                'read' => 10,
                'none' => 5,
            ));
}

// Set textdomain for translations (i18n)
function ecu_textdomain() {
    load_plugin_textdomain('easy-comment-uploads', false,
        basename(dirname(__FILE__)) . '/languages');
}

// Register code with wordpress
add_action('admin_menu', 'ecu_options_menu');
add_filter('comment_text', 'ecu_insert_links');
if (!get_option('ecu_hide_comment_form'))
    add_action('comment_form', 'ecu_upload_form_default');
add_action('init', 'ecu_initial_options');
