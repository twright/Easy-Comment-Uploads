<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        		$upload_dir = "./";
        		$fh = fopen($upload_dir . './upload_url.txt', 'r');
        		$upload_url = fread($fh, filesize('./upload_url.txt'));

            if ($_FILES["file"]["error"] > 0){
              echo "Error code: " . $_FILES["file"]["error"];
            } else {
              if (file_exists("$upload_dir"
                  . $_FILES["file"]["name"])){
                echo "<b>" . $_FILES["file"]["name"] . "</b> already exists";
              } else if (eregi('image/', $_FILES['file']['type'])){
                move_uploaded_file($_FILES["file"]["tmp_name"],
                  $upload_dir . /* "/images/" . */
                  $_FILES["file"]["name"]);
                echo "<b>[img]</b>" . $upload_url /*. "images/" */
                  . $_FILES["file"]["name"] . "<b>[/img]</b>";
              } else {
              	move_uploaded_file($_FILES["file"]["tmp_name"],
                  $upload_dir .
                  $_FILES["file"]["name"]);
                echo "<b>[file]</b>" . $upload_url
                  . $_FILES["file"]["name"] . "<b>[/file]</b>";
              }
            }

            echo "&nbsp;&nbsp;&nbsp; | ";
            echo "<a href='./upload.html'>back</a>";
        ?>
    </body>
</html>
