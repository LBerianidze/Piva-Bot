<?php
/**
 * Created by PhpStorm.
 * User: Лука
 * Date: 06.05.2020
 * Time: 11:56
 */
include "dbconfig.php";
$db_config = new DBConfig();
echo json_encode($db_config->getAllUsers());