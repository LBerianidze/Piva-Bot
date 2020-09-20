<?php
/**
 * Created by PhpStorm.
 * User: Лука
 * Date: 14.04.2020
 * Time: 18:38
 */
include "dbconfig.php";
$db_config = new DBConfig();
$json_str = file_get_contents("php://input");
$json = json_decode((string)$json_str, true);
$db_config->deleteAllBeer();
$max_id = 0;
foreach ($json as $item)
{
	$id = $item['ID'];
	$max_id++;
	$brewery = $item['Brewery'];
	$name = $item['Name'];
	$style = $item['Style'];
	$percent = $item['Percent'];
	$price = $item['Price'];
	$image = $item['ImageName'];
	$db_config->addBeer($brewery, $name, $style, $percent, $price, $image);
}
$db_config->deleteOldBeers($max_id);
$folder = 'images';

//Get a list of all of the file names in the folder.
$files = glob($folder . '/*');

//Loop through the file list.
foreach($files as $file){
	//Make sure that this is a file and not a directory.
	if(is_file($file)){
		//Use the unlink function to delete the file.
		unlink($file);
	}
}