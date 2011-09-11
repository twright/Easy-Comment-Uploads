<?php
/*
Plugin Name: Easy Comment Uploads
Plugin URI: http://wordpress.org/extend/plugins/easy-comment-uploads/
Description: Allow your users to easily upload images and files with their comments.
Author: Tom Wright
Version: 0.72
Author URI: http://gplus.to/twright/
License: GPLv3
*/

// Take a image url and return a url to a thumbnail for height $h, width $w
// and using zoom/crop mode $zc
function ecu_thumbnail($url, $h='null', $w='null', $zc=3) {
    return ecu_plugin_url() . "timthumb.php?src=$url&zc=$zc&h=$h&w=$w";
}

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
            preg_match('/[^\/]*$/', $match[2], $filename);
            $name = get_option('ecu_show_full_file_path') ? $match[2]
                : $filename[0];
            if ($match[1] == 'img') {
                $thumbnail = ecu_thumbnail($match[2], 300);
                $html = "<a href='$match[2]' rel='lightbox[comments]'>"
                    . (get_option('ecu_display_images_as_links')
                    ? __('Image', 'easy-comment-uploads') . ": $name"
                    : "<img class='ecu_images' src='$thumbnail' />")
                    . '</a>';
            } elseif ($match[1] == 'file') {
                $html = sprintf('<a href="%s">%s: %s', $match[2],
                    __('File', 'easy-comment-uploads'), $name);
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

// A list of file extensions which should not be harmful
function ecu_get_whitelist() {
    $default_whitelist = array('odt', 'ods', 'odp', 'doc', 'docx', 'xls',
        'xlsx', 'ppt', 'pptx', 'pdf', 'bmp', 'gif', 'jpg', 'jpeg', 'webp',
        'png', 'mp3', 'ogg', 'wav', 'webm', 'avi', 'mkv', 'mov', 'mp4',
        'txt', 'psd', 'xcf', 'rtf', 'zip', '7z', 'xz', 'tar', 'gz', 'bz2',
        'tgz', 'tbz', 'tbz2', 'txz', 'lzma');
    return get_option('ecu_file_extension_whitelist', $default_whitelist);
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

// Get the full path to the wordpress root directory
function ecu_wordpress_root_path() {
    $path = dirname(__FILE__);
    
    while (!file_exists($path . '/wp-config.php'))
        $path = dirname($path);

    return str_replace("\\", "/", $path) . '/';
}

// Placeholder for preview of uploaded files
function ecu_upload_form_preview($display=true) {
    ?>
    <p id='ecu_preview' <?php ($display ? "" : "style='display:none'") ?> />
    <?php
}

// An iframe containing the upload form
function ecu_upload_form_iframe() {
    ?>
    <iframe id='ecu_upload_frame' scrolling='no' frameborder='0'
        allowTransparency='true' height='0px'
        src='<?php echo ecu_plugin_url() ?>upload-form.php' name='upload_form'>
    </iframe>
    <?php
}

// Complete upload form
function ecu_upload_form($title, $msg, $check=true) {
    if ( !ecu_allow_upload() && $check ) return;

    ?>
    <!-- Easy Comment Uploads for Wordpress by Tom Wright: http://wordpress.org/extend/plugins/easy-comment-uploads/ -->

    <div id='ecu_uploadform'>
    <h3 class='title'><?php echo $title; ?></h3>
    <div class='message'><?php echo $msg; ?></div>
    
    <?php
    ecu_upload_form_iframe();
    ecu_upload_form_preview();
    ?>
    </div>

    <!-- End of Easy Comment Uploads -->
    <?php
}

// Default comment form
function ecu_upload_form_default($check=true) {
    ecu_upload_form (
        ecu_upload_form_heading(), // $title
        '<p>' . ecu_message_text() . '</p>', // $msg
        $check // $check
    );
}

// Upload form heading
function ecu_upload_form_heading() {
    if (get_option('ecu_upload_form_heading'))
        return get_option('ecu_upload_form_heading');
    else
        return __('Upload Files', 'easy-comment-uploads');
}

// Get message text
function ecu_message_text() {
    if (get_option('ecu_message_text'))
        return get_option('ecu_message_text');
    else
        return __('You can include images or files in your comment by selecting them below. Once you select a file, it will be uploaded and a link to it added to your comment. You can upload as many images or files as you like and they will all be added to your comment.', 'easy-comment-uploads');
}

// Add options menu item (restricted to level_10 users)
function ecu_options_menu() {
    if (current_user_can("level_10"))
        add_plugins_page('Easy Comment Uploads options',
            'Easy Comment Uploads', 8, __FILE__, 'ecu_options_page');
}
 
// Provide an options page in wp-admin
function ecu_options_page() {
    // Handle changed options
    if (isset($_POST['submitted'])) {
        check_admin_referer('easy-comment-uploads');

        // Update options
        update_option ('ecu_images_only', $_POST['images_only'] != null);
        if (isset($_POST['permission_required']))
            update_option ('ecu_permission_required',
                $_POST['permission_required']);
        update_option ('ecu_hide_comment_form',
            (int) ($_POST['hide_comment_form'] != null));
        update_option ('ecu_show_full_file_path',
            (int) ($_POST['show_full_file_path'] != null));
        update_option ('ecu_display_images_as_links',
            (int) ($_POST['display_images_as_links'] != null));
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
        if (isset($_POST['enabled_pages'])
            && preg_match('/^([1-9][0-9]*)?$/', $_POST['enabled_category']))
            update_option('ecu_enabled_category', $_POST['enabled_category']);
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
            if ($_POST['file_extension_whitelist'] == 'default')
                delete_option('ecu_file_extension_whitelist');
            else if ($_POST['file_extension_whitelist'] == 'ignore')
                update_option('ecu_file_extension_whitelist', array());
            else update_option('ecu_file_extension_whitelist',
                preg_split("/[, ][ ]*/", $_POST['file_extension_whitelist']));
        if (isset($_POST['upload_form_heading']))
            update_option('ecu_upload_form_heading',
                $_POST['upload_form_heading']);
        if (isset($_POST['upload_form_text']))
            update_option('ecu_message_text', $_POST['upload_form_text']);
        if (isset($_POST['upload_dir_path']))
            if ($_POST['upload_dir_path'] == '')
                delete_option('ecu_upload_dir_path');
            else
                update_option('ecu_upload_dir_path', $_POST['upload_dir_path']);

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
    $display_images_as_links = (get_option('ecu_display_images_as_links') ? 'checked' : '');
    $premission_required = array();
    foreach (array('none', 'read', 'edit_posts', 'upload_files') as $elem)
        $permission_required[] =
            ((get_option('ecu_permission_required') == $elem) ? 'checked' : '');
    $max_file_size = get_option('ecu_max_file_size');
    $enabled_pages = get_option('ecu_enabled_pages');
    $enabled_category = get_option('ecu_enabled_category');
    $file_extension_blacklist = ecu_get_blacklist() ?
        implode(', ', ecu_get_blacklist()) : 'none';
    $file_extension_whitelist = ecu_get_whitelist() ?
        implode(', ', ecu_get_whitelist()) : 'ignore';
    $uploads_per_hour = get_option('ecu_uploads_per_hour');
    $upload_form_heading = ecu_upload_form_heading();
    $upload_form_text = ecu_message_text();
    $upload_dir_path = get_option('ecu_upload_dir_path');

    // Info for form
    $actionurl = $_SERVER['REQUEST_URI'];

    ?>
    <div class="wrap" style="max-width:950px !important;">
    <h2>Easy Comment Uploads</h2>
    
    <a href='http://goo.gl/WFJP6' target='_blank'
        style='text-decoration: none'>
    <p id='ecu_donate' style='background-color: #757575; padding: 0.5em;
        color: white; font-weight: bold; text-align: center; font-size: 11pt;
        border-radius: 10px'>
        <?php
            _e('If you find this plugin useful and want to support '
                . 'its future development, please consider donating.',
                'easy-comment-uploads');
        ?>
        <input type="submit" class="button-primary"
        style='margin-left: 1em' name="donate"
        value="<?php _e('Donate', 'easy-comment-uploads'); ?>" />
    </p>
    </a>

    <form name="ecuform" action="<?php echo $action_url ?>"
        method="post">
        <input type="hidden" name="submitted" value="1" />
        <?php wp_nonce_field('easy-comment-uploads'); ?>

        <h3><?php _e('Files', 'easy-comment-uploads'); ?></h3>

        <ul>
        <li><input id="images_only" type="checkbox" name="images_only" 
            <?php echo $images_only ?> />
        <label for="images_only">
            <?php _e('Only allow images to be uploaded.',
                'easy-comment-uploads'); ?>
        </label>
        </li>
        </p>

        <li>
        <?php _e('Limit the size of uploaded files:',
            'easy-comment-uploads'); ?>
        <input id="max_file_size" type="text" name="max_file_size"
            value="<?php echo $max_file_size ?>" />
        <label for="max_file_size">
        (<?php _e('KiB, 0 = unlimited', 'easy-comment-uploads'); ?>)
        </label>
        </li>

        <li>
        <?php _e('Blacklist the following file extensions:',
            'easy-comment-uploads'); ?>
        <input id="file_extenstion_blacklist" type="text"
            name="file_extension_blacklist"
            value="<?php echo $file_extension_blacklist ?>" />
        <br />
        <label for="file_extenstion_blacklist">
            (<?php _e('extensions seperated with spaces, \'none\' to '
                . 'allow all (not recommended), or \'default\' to '
                . 'restore the default list',
                'easy-comment-uploads'); ?>)
        </label>
        </li>

        <li>
        <?php _e('Allow only the following file extensions:',
            'easy-comment-uploads'); ?>
        <input id="file_extenstion_whitelist" type="text"
            name="file_extension_whitelist"
            value="<?php echo $file_extension_whitelist; ?>" />
        <br />
        <label for="file_extension_whitelist">
        (<?php _e('extensions seperated with '
            . 'spaces, \'ignore\' to disable the whitelist, or '
            . '\'default\' to restore the default list',
            'easy-comment-uploads'); ?>)
        </label>
        </li>
        
        <li>
        <?php _e('Store uploads in folder',
            'easy-comment-uploads'); ?>:
        <input id="upload_dir_path" type="text" name="upload_dir_path"
            value="<?php echo $upload_dir_path ?>" />
        <br />
        <label for="file_extension_whitelist">
            (<?php _e('path relative to the Wordpress installation '
                . 'directory or leave blank for default location',
                'easy-comment-uploads'); ?>)
        </label>
        </li>
        </ul>

        <h3><?php _e('User Permissions', 'easy-comment-uploads'); ?></h3>
        <ul>
        <li>
        <input id="all_users" type="radio" name="permission_required"
            value="none" <?php echo $permission_required[0] ?> />
        <label for="all_users">
        <?php _e('Allow all users to upload files with their '
            . 'comments.', 'easy-comment-uploads'); ?>
        </label>
        </li>

        <li>
        <input id="registered_users_only" type="radio"
            name="permission_required"
            value="read" <?php echo $permission_required[1] ?> />
        <label for="registered_users_only">
        <?php _e('Only allow registered users to upload files.',
            'easy-comment-uploads'); ?>
        </label>
        </li>

        <li>
        <input id="edit_rights_only" type="radio"
            name="permission_required" value="edit_posts"
            <?php $permission_required[2]; ?> />
        <label for="edit_rights_only">
        <?php _e('Require "Contributor" rights to upload files.',
            'easy-comment-uploads'); ?>
        </label>
        </li>

        <li>
        <input id="upload_rights_only" type="radio" name="permission_required"
            value="upload_files" <?php $permission_required[3]; ?> />
        <label for="upload_rights_only">
            <?php _e('Require \'Upload\' rights to uploads files'
                . '(e.g. only admin, editors and authors).',
                'easy-comment-uploads'); ?>
        </label>
        </li>
            
        <br />

        <li><table class="widefat">
            <tr>
                <th></th>
                <th>
                <?php _e('Uploads allowed per hour',
                    'easy-comment-uploads'); ?>
                <br />
                <em>
                (-1 = <?php _e('unlimited',
                    'easy-comment-uploads'); ?>)</em>
                </th>
            </tr>
            <tr>
                <th>
                <?php _e('users with upload rights',
                    'easy-comment-uploads'); ?>
                <br />
                <em>(<?php _e('e.g. only admin, editors and authors',
                    'easy-comment-uploads'); ?>)</em>
                </th>
                <td>
                <input id="upload_files_uploads_per_hour" type="text"
                    name="upload_files_uploads_per_hour"
                    value="<?php echo $uploads_per_hour[upload_files]; ?>" />
                </td>
            </tr>
            <tr>
                <th>
                <?php _e('contributors', 'easy-comment-uploads'); ?>
                </th>
                <td>
                <input id="edit_posts_uploads_per_hour" type="text"
                    name="edit_posts_uploads_per_hour"
                    value="<?php echo $uploads_per_hour[edit_posts]; ?>" />
                </td>
            </tr>
            <tr>
                <th>
                <?php _e('registered users', 'easy-comment-uploads'); ?>
                </th>
                <td>
                <input id="read_uploads_per_hour" type="text"
                    name="read_uploads_per_hour"
                    value="<?php echo $uploads_per_hour[read]; ?>" />
                    </td>
            </tr>
            <tr>
                <th>
                <?php _e('unregistered users', 'easy-comment-uploads'); ?>
                </th>
                <td>
                <input id="none_uploads_per_hour" type="text"
                    name="none_uploads_per_hour"
                    value="<?php echo $uploads_per_hour[none]; ?>" />
                </td>
            </tr>
        </table></li>
        </ul>

        <h3><?php _e('Upload Form', 'easy-comment-uploads'); ?></h3>
        <ul>
        <li>
            <label for="upload_form_heading">
                <?php _e('Title of the upload form'
                    . '(leave blank for default title)',
                    'easy-comment-uploads'); ?>:
            </label>
            <input type="text" id="upload_form_heading"
                name="upload_form_heading"
                value="<?php echo $upload_form_heading; ?>" />
        </li>        
        
        <li>
            <label for="upload_form_text">
                <?php _e('Text explaining use of the upload form '
                    . '(leave blank for default text)',
                    'easy-comment-uploads'); ?>:
            </label>
            <br />
            <textarea id="upload_form_text" name="upload_form_text"
                style="width : 100%; height : 65pt"><?php echo $upload_form_text; ?></textarea>
        </li>

        <li>
            <input id="hide_comment_form" type="checkbox"
            name="hide_comment_form" <?php echo $hide_comment_form; ?> />
            <label for="hide_comment_form">
                <?php _e('Hide from comment forms',
                    'easy-comment-uploads'); ?>
            </label>
        </li>
        
        <li>
            <?php _e('Only allow uploads in this category',
                'easy-comment-uploads'); ?>:
            <input id="enabled_category" type="text"
                name="enabled_category"
                value="<?php echo $enabled_category; ?>" />
            <br />
            <label for="enabled_category">
            (<a href= "http://www.wprecipes.com/how-to-find-wordpress-category-id"
            ><?php _e('category id', 'easy-comment-uploads'); ?></a>
            <?php _e('or leave blank to enable globally',
            'easy-comment-uploads'); ?>)
            </label>
        </li>
        
        <li>
            <?php _e('Only allow uploads on these pages',
            'easy-comment-uploads'); ?>:
            <input id="enabled_pages" type="text" name="enabled_pages"
            value="<?php echo $enabled_pages; ?>" />
            <br />
            <label for="enabled_pages">
            (<a href="http://www.techtrot.com/wordpress-page-id/"
            ><?php _e('page ids', 'easy-comment-uploads'); ?></a>
            <?php _e('seperated with spaces or \'all\' to enable '
                . 'globally', 'easy-comment-uploads'); ?>)
            </label>
        </li>
        </ul>

        <h3><?php _e('Comments', 'easy-comment-uploads'); ?></h3>
        <ul>
        <li>
            <input id="show_full_file_path" type="checkbox"
                name="show_full_file_path"
                <?php echo $show_full_file_path; ?> />
            <label for="show_full_file_path">
                <?php _e('Show full url in links to files',
                'easy-comment-uploads'); ?>
            </label>
        </li>
        
        <li>
            <input id="display_images_as_links" type="checkbox"
                name="display_images_as_links"
                <?php echo $display_images_as_links; ?> />
            <label for="display_images_as_links">
                <?php _e('Replace images with links',
                    'easy-comment-uploads'); ?>
            </label>
        </li>
        </ul>

        <p class="submit">
            <input type="submit" class="button-primary"
                name="Submit" value="<?php _e('Save Changes',
                'easy-comment-uploads'); ?>" />
        </p>
    </form>
    <?php

    // Sample upload form
    ?>
    <div style='margin : auto auto auto 2em; width : 40em;
        background-color : ghostwhite; border : 1px dashed gray;
        padding : 0 1em 0em 1em'>
        <?php ecu_upload_form_default(false); ?>
    </div>
    <?php
}

function ecu_upload_dir_path() {
    if (get_option('ecu_upload_dir_path')) {
        return ecu_wordpress_root_path()
            . get_option('ecu_upload_dir_path')  . '/';
    } else {
        $upload_dir = wp_upload_dir();
        return $upload_dir['path'] . '/';                
    }
}

function ecu_upload_dir_url() {
    if (get_option('ecu_upload_dir_path')) {
        return get_option('siteurl') . '/'
            . get_option('ecu_upload_dir_path') . '/';
    } else {
        $upload_dir = wp_upload_dir();
        return $upload_dir['url'] . '/';        
    }
}

// Seperate function as closures were not supported before 5.3.0
function ecu_extract_cat_ID($category) {
    return $category->cat_ID;
}

// Are uploads allowed?
function ecu_allow_upload() {
    global $post;
    $permission_required = get_option('ecu_permission_required');
    $enabled_pages = get_option('ecu_enabled_pages');
    $enabled_category = get_option('ecu_enabled_category');
    $categories = array_map('ecu_extract_cat_ID', get_the_category());

    return ($permission_required == 'none'
        || current_user_can($permission_required))
        && (in_array($post->ID, explode(' ', $enabled_pages))
            || $enabled_pages == 'all')
        && (in_array($enabled_category, $categories)
            || $enabled_category == '');
}

// Set options to defaults, if not already set
function ecu_initial_options() {
    ecu_textdomain();
    
    wp_enqueue_style('ecu', ecu_plugin_url () . 'style.css');
    if (get_option('ecu_permission_required') === false)
        update_option('ecu_permission_required', 'none');
    if (get_option('ecu_show_full_file_path') === false)
        update_option('ecu_show_full_file_path', 0);
    if (get_option('ecu_display_images_as_links') === false)
        update_option('ecu_display_images_as_links', 0);
    if (get_option('ecu_hide_comment_form') === false)
        update_option('ecu_hide_comment_form', 0);
    if (get_option('ecu_images_only') === false)
        update_option('ecu_images_only', 0);
    if (get_option('ecu_max_file_size') === false)
        update_option('ecu_max_file_size', 0);
    if (get_option('ecu_enabled_pages') === false)
        update_option('ecu_enabled_pages', 'all');
    if (get_option('ecu_enabled_category') === false)
        update_option('ecu_enabled_category', '');
    if (get_option('ecu_ip_upload_times') === false)
        update_option('ecu_ip_upload_times', array());
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
