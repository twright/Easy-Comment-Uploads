<?php

$target_dir = file_get_contents("upload_dir.txt");
$target_path = $target_dir . basename( $_FILES['file']['name']);
$target_url = file_get_contents("upload_url.txt");
$images_only = false;

if (eregi('jpg', $_FILES['file']['type']) || eregi('png', $_FILES['file']['type']) || eregi('gif', $_FILES['file']['type']))
    $type = "img";
else
		$type = "file";

if ($type == "img" || !$images_only) {
		if (file_exists($target_path) || false ) {
				$alert = "A file by the same name already exists, please try renaming.";
		} else if (move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
	    $filename = $_FILES["file"]["name"];
	    $filelink = $target_url . $filename;
	    $filecode = "[" . $type . "]" . $filelink . "[/" . $type . "]";
		} else {
			$alert = "There was an error uploading the file, please try again!";
		}
} else {
    $alert = "Sorry, you can only upload images.";
}
//$alert = "test";
?>
<script type="text/javascript">
alert_msg = "<?php echo $alert ?>";
filecode = "<?php echo $filecode ?>";
filelink = "<?php echo $filelink ?>";
filename = "<?php echo $filename ?>";


if (filename && filelink && filecode)
parent.document.getElementById('uploadedfile').innerHTML += '<br><a href="' + filelink + '">' + filename + '</a> : ' + filecode;

if (alert_msg)
alert(alert_msg);

if (!filecode)
document.write("Upload failed");
else if (parent.document.forms["commentform"]["comment"].value)
parent.document.forms["commentform"]["comment"].value += "\n" + filecode;
else
parent.document.forms["commentform"]["comment"].value += filecode;
</script>
