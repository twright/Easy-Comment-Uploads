<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title></title>
    </head>
    <body>
        <?php
            if ($_FILES["file"]["error"] > 0){
              echo "Error code: " . $_FILES["file"]["error"];
            } else {
              if (file_exists("./upload/"
                  . $_FILES["file"]["name"])){
                echo "<b>" . $_FILES["file"]["name"] . "</b> already exists";
              } else {
                move_uploaded_file($_FILES["file"]["tmp_name"],
                  "./upload/" .
                  $_FILES["file"]["name"]);
                echo "<b>[img]</b>" . "/upload/"
                  . $_FILES["file"]["name"] . "<b>[/img]</b>";
              }
            }

            echo "&nbsp;&nbsp;&nbsp; | ";
            echo "<a href='./upload.html'>back</a>";
        ?>
    </body>
</html>
