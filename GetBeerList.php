<?php
/**
 * Created by PhpStorm.
 * User: Лука
 * Date: 14.04.2020
 * Time: 17:43
 */
include "dbconfig.php";
$db_config = new DBConfig();
echo json_encode($db_config->getBeerList());