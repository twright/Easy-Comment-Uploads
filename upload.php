<?php

// Load saved info from text files
$target_dir = file_get_contents("upload_dir.txt");
$target_url = file_get_contents("upload_url.txt");
// Calculate time and target for images
$prefix = time() . "-";
$target_path = $target_dir . $prefix . basename( $_FILES['file']['name']);
// Only allow images to be uploaded
// TODO: Add GUI for enabling option
$images_only = false;

// Detect whether the uploaded file is an image
if (eregi('jpeg', $_FILES['file']['type']) || eregi('png', $_FILES['file']['type']) || eregi('gif', $_FILES['file']['type']))
	$type = "img";
else
	$type = "file";

if ($type == "img" || !$images_only) {
	// Check filetypes against blacklist
	if(!check_uploaded_files())  {
		$alert = "You are attempting to upload a file with a disallowed/unsafe filetype!";
	// Move files from tmp and generate links + insert codes
	} else if (move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
        $filename = $prefix . $_FILES["file"]["name"];
        $filelink = $target_url . $filename;
        $filecode = "[" . $type . "]" . $filelink . "[/" . $type . "]";
	// Catchall for errors
	} else $alert = "There was an error uploading the file, please try again!";
// Block non-images if they are disabled
} else $alert = "Sorry, you can only upload images.";

// Check upload against blacklist and 
function check_uploaded_files() {
	$blacklist = array(".php", ".phtml", ".php3", ".php4", ".php5", ".php6", ".cgi", ".fcgi", ".htaccess", ".js", ".shtml", ".pl", ".py", ".exe", ".bat", ".sh", ".aspx", ".asp", ".sh");
	foreach ($blacklist as $file)
		if(preg_match("/$file$/i", $_FILES['file']['name']))
				return false;
	return true;
}

?>


<script type="text/javascript">
// Get variable values from php
alert_msg = "<?php echo $alert ?>";
filecode = "<?php echo $filecode ?>";
filelink = "<?php echo $filelink ?>";
filename = "<?php echo $filename ?>";

// Display info for debug

// Return true if there is any text in the comment field
function comment_length () {
	if (parent.document.getElementById("comment"))
		return parent.document.getElementById("comment").value.length;
	else if (parent.document.getElementById("comment-p1"))
		return parent.document.getElementById("comment-p1").value.length;
	else
		return parent.document.forms["commentform"].comment.value;
}

// Write txt to comment field
function write_comment (text) {
	// Prepend a linebreak code if it is not the first line 
	if (comment_length() > 0)
		text = "\n" + text;

    // Attempt to write text to comment field (wherever it may be)
	if (parent.document.getElementById("comment"))
		parent.document.getElementById("comment").value += text;
	else if (parent.document.getElementById("comment-p1"))
		parent.document.getElementById("comment-p1").value += text;
	else
		parent.document.forms["commentform"].comment.value += text;
}

// Display any alert set.
if (alert_msg)
	alert(alert_msg);

// Add the filecode to the comment form
write_comment(filecode);

// If successful post info below upload form
if (filename && filelink && filecode)	parent.document.getElementById('uploadedfile').innerHTML += '<br><a href="' + filelink + '">' + filename + '</a> : ' + filecode;
</script>
