<?php
/**
 * Created by PhpStorm.
 * User: Лука
 * Date: 16.04.2020
 * Time: 3:19
 */
include "dbconfig.php";
$db_config = new DBConfig();
$id = $_GET['id'];
$status = $_GET['status'];
$db_config->updateOrderStatus($id,$status);