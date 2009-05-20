<!DOCTYPE HTML>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title></title>
        
        <script language="javascript" type="text/javascript">
        	function addtext(imglink) {
          	parent.document.forms["commentform"]["comment-p6"].value += '\n' + imglink ;
          	//parent.document.getElementByName("comment").value += '\n' + imglink ;
        	}
        </script>
    </head>
    <body>
        <?php
        		$upload_dir = "./";
        		$fh = fopen($upload_dir . './upload_url.txt', 'r');
        		$upload_url = fread($fh, filesize('./upload_url.txt'));
        		$only_images = false;

            if ($_FILES["file"]["error"] > 0){
              echo "Error code: " . $_FILES["file"]["error"];
            } else {
              if (file_exists("$upload_dir"
                  . $_FILES["file"]["name"])){
                echo "<b>" . $_FILES["file"]["name"] . "</b> already exists";
              } else if (eregi('image/', $_FILES['file']['type'])){
                move_uploaded_file($_FILES["file"]["tmp_name"],
                  $upload_dir .
                  $_FILES["file"]["name"]);
                $upload_file = "<b>[img]</b>" . $upload_url
                  . $_FILES["file"]["name"] . "<b>[/img]</b>";
              } else if (!$only_images) {
              	move_uploaded_file($_FILES["file"]["tmp_name"],
                  $upload_dir .
                  $_FILES["file"]["name"]);
                $upload_file = "<b>[file]</b>" . $upload_url
                  . $_FILES["file"]["name"] . "<b>[/file]</b>";
              } else {
								echo 'Sorry - your file could not be uploaded';
							}
            }
            
						echo "<a href=\"#\" onClick=\"addtext('test');\">Insert link<a>";
            echo "&nbsp;&nbsp;&nbsp; | ";
            echo "<a href='./upload.html'>" . 'Upload more files' . "</a>";
        ?>
    </body>
</html>
