<?php
/**
 * Created by PhpStorm.
 * User: Лука
 * Date: 16.04.2020
 * Time: 2:00
 */
include "dbconfig.php";
$db_config = new DBConfig();
if(!isset($_GET['MinID']))
{
	$orders = $db_config->getOrders(0);
	echo json_encode($orders);
}
else
{
	$orders = $db_config->getOrders($_GET['MinID']);
	echo json_encode($orders);
}