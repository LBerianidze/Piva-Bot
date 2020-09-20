<?php
/**
 * Created by PhpStorm.
 * User: Лука
 * Date: 16.04.2020
 * Time: 14:19
 */
$text = $_POST['text'];
file_put_contents('about.txt',file_get_contents('php://input'));