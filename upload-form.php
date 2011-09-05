<?php require ('../../../wp-load.php'); ?>
<!doctype html>
<head>
    <style>
        body {
            background : transparent;
            /* font-family : 'Lucida Grande', Verdana, Arial, sans-serif; */
            /* font-size : 10pt */
            text-align: center;
        }
    </style>
    
    <script type='javascript'>
        function upload_start() {
            ;
        }
    </script>
</head>
<body> 
    <form target='hiddenframe' enctype='multipart/form-data'
    action='<?php echo ecu_plugin_url() . 'upload.php' ?>'
    method='POST' name='uploadform' id='uploadform'>
        <?php wp_nonce_field('ecu_upload_form') ?>
        <label for='file' name='prompt'>
	    <?php _e('Select File', 'easy-comment-uploads') ?>:
	</label>
        <input type='file' name='file' id='file'
            onchange="document.getElementById('uploadform').style.display
	        = 'none';
            document.getElementById('loading').style.display = 'block';
            document.uploadform.submit();
            document.uploadform.file.value = ''" />
    </form>

    <div align='center'>
        <img src='loading.gif' style='display: none' id='loading' />
    </div>

    <iframe name='hiddenframe' style='display : none' frameborder='0'></iframe>
</body>
