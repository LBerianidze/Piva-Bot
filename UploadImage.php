<?php
/**
 * Created by PhpStorm.
 * User: Лука
 * Date: 14.04.2020
 * Time: 17:10
 */
$uploads_dir = './images'; //Directory to save the file that comes from client application.
if ($_FILES["file"]["error"] == UPLOAD_ERR_OK)
{
	$tmp_name = $_FILES["file"]["tmp_name"];
	$name = $_FILES["file"]["name"];
	ob_flush();
	ob_start();
	var_dump($_FILES);
	file_put_contents("dump.txt", ob_get_flush());
	move_uploaded_file($tmp_name, "$uploads_dir/$name");
}
?>