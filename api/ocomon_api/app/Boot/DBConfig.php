<?php

require_once (__DIR__ . "/" . "../../../../includes/config.inc.php");
define("DATA_LAYER_CONFIG", [
    "driver" => "mysql",
    "host" => SQL_SERVER,
    "port" => "3306",
    "dbname" => SQL_DB,
    "username" => SQL_USER,
    "passwd" => SQL_PASSWD,
    "options" => [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_CASE => PDO::CASE_NATURAL
    ]
]);
