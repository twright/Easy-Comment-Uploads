<?php require('../../../wp-load.php'); ?>
<!doctype html5>
<html>
    <head>
        <script type="text/javascript">
        // Write txt to comment field
        function write_comment (text) {
            // Handle commentMCE
            if (parent.parent.tinyMCE
                && parent.parent.tinyMCE.get('comment')) {
                editor = parent.parent.tinyMCE.get('comment');
                editor.setContent(editor.getContent()
                    + '\n<p>' + text + '</p>');
                return;
            }

            // Handle nicEdit
            if (parent.parent.nicEditors
                && parent.parent.nicEditors.findEditor('comment')) {
                editor = parent.parent.nicEditors.findEditor('comment');
                editor.setContent((editor.getContent() != '<br>' ?
                    editor.getContent().replace(/(<(p|div)><br><\/(p|div)>)+$/,
                    '') : '') + '<p>' + text + '</p>');
                return;
            }

            // Handle standard comment forms
            comment = parent.parent.document.getElementById("comment")
                || parent.parent.document.getElementById("comment-p1")
                || parent.parent.document.forms["commentform"].comment;
            comment.value = comment.value.replace(/[\n]+$/, '')
                + (comment.value.length > 0 ? '\n' : '') + text + '\n';
        }
        
        function upload_end() {
            parent.document.getElementById('uploadform').style.display = 'block';
            parent.document.getElementById('loading').style.display = 'none';
        }
        </script>
    </head>

    <body>
        <?php
        // Get needed info
        $target_dir = ecu_upload_dir_path();
        $target_url = ecu_upload_dir_url();
        $images_only = get_option('ecu_images_only');
        $max_file_size = get_option('ecu_max_file_size');
        $max_post_size = (int)ini_get('post_max_size');
        $max_upload_size = (int)ini_get('upload_max_filesize');

        if (!file_exists($target_dir))
            mkdir ($target_dir);

        $target_path = find_unique_target($target_dir
            . basename($_FILES['file']['name']));
        $target_name = basename($target_path);

        /* Debugging info */
        // $error = (int) $_FILES['file']['error'];
        // write_js("alert('$error')");
        // sleep(2);

        // Default values
        $filecode = "";
        $filelink = "";

        // Detect whether the uploaded file is an image
        $is_image = preg_match ('/(jpeg|png|gif)/i', $_FILES['file']['type']);
        $type = ($is_image) ? "img" : "file";

        if (!is_writable($target_dir)) {
            $alert = "Files can not be written to $target_dir. Please make sure that the permissions are set correctly (mode 666)."; 
        } else if (!$is_image && $images_only) {
            $alert = "Sorry, you can only upload images.";
        } else if (filetype_blacklisted()) {
            $alert = "You are attempting to upload a file with a"
                . "disallowed/unsafe filetype!";
        } else if (!filetype_whitelisted() && ecu_get_whitelist()) {
            $alert = 'You may only upload files with the following extensions: '
                . implode(', ', ecu_get_whitelist());
        } else if ($max_file_size != 0
            && $_FILES['file']['size']/1024 > $max_file_size) {
            $alert = "The file you've uploaded is too large ("
                . round($_FILES['file']['size']/1024, 1)
                . "KiB).\nPlease choose a smaller file and try again (Uploads"
                . " are limited to $max_file_size KiB).";
        } else if (ecu_user_uploads_per_hour() != -1
            && ecu_user_uploads_in_last_hour()
            >= ecu_user_uploads_per_hour()) {
            $alert = "You are only permitted to upload "
                . (string)ecu_user_uploads_per_hour() . " files per hour.";
        } else if ($_FILES['file']['error'] == 1
            || $_FILES['file']['error'] == 2) {
            $alert = 'Your file has exceeded the php max_upload_size ('
                . "$max_upload_size MiB) or max_post_size ("
                . "$max_post_size MiB). Please choose a"
                . ' smaller file or ask the website administrator to'
                . ' increase the relevant settings in the php.ini file.';
        } else if (!wp_verify_nonce($_REQUEST['_wpnonce'],
            'ecu_upload_form')) {
            // Check referer
            $alert = 'Invalid Referrer';
        } else if (move_uploaded_file($_FILES['file']['tmp_name'],
            $target_path)) {
            $filelink = $target_url . $target_name;
            $filecode = "[$type]$filelink" . "[/$type]";

            // Add the filecode to the comment form
            write_js("write_comment('$filecode');");

            // Post info below upload form
            write_html_form("<div class='ecu_preview_file'>"
                . "<a href='$filelink'>$target_name</a><br />$filecode</div>");

            if ($is_image) {
                $thumbnail = ecu_thumbnail($filelink, 300);
                write_html_form("<a href='$filelink' rel='lightbox[new]'>"
                    . "<img class='ecu_preview_img' src='$thumbnail' /></a>"
                    . '<br />');
            }

            ecu_user_record_upload_time();
        } else {
            $alert = 'There was an error uploading the file, '
                . 'please try again!';
        }
        
        write_js('upload_end()');

        // Alert the user of any errors
        if (isset($alert))
            js_alert($alert);

        // Check upload against blacklist and return true unless it matches
        function filetype_blacklisted() {
            $blacklist = ecu_get_blacklist();
            return preg_match('/\\.((' . implode('|', $blacklist)
                . ')|~)([^a-z0-9]|$)/i', $_FILES['file']['name']);
        }

        // Check upload against whitelist and return true if it matches
        function filetype_whitelisted() {
            $whitelist = ecu_get_whitelist();
            return preg_match('/^[^\\.]+\\.(' . implode('|', $whitelist)
                . ')$/i', $_FILES['file']['name']);
        }

        // Write script as js to the page
        function write_js($script) {
            echo "<script type='text/javascript'>$script\n</script>\n";
        }

        // Send message to user in an alert
        function js_alert($msg) {
            write_js("alert('$msg');");
        }

        // Write html to the preview iframe
        function write_html_form ($html) {
            write_js("parent.parent.document.getElementById('ecu_preview')"
                . ".innerHTML = \"$html\""
                . "+ parent.parent.document.getElementById('ecu_preview')"
                . ".innerHTML");
        }

        // Find a unique filename similar to $prototype
        function find_unique_target ($prototype) {
            $prototype_parts = pathinfo ($prototype);
            $ext = $prototype_parts['extension'];
            $dir = $prototype_parts['dirname'];
            $name = sanitize_file_name(filter_var($prototype_parts['filename'], FILTER_SANITIZE_URL));
            $dot = $ext == '' ? '' : '.';
            if (!file_exists("$dir/$name.$ext")) {
                return "$dir/$name$dot$ext";
            } else {
                $i = 1;
                while (file_exists("$dir/$name-$i$dot$ext")) { ++$i; }
                return "$dir/$name-$i$dot$ext";
            }
        }

        ?>
    </body>
</html>
