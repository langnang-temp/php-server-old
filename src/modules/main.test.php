<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../utils/main.php';
require_once __DIR__ . '/../modules/main.php';

$_CONFIG = require_once __DIR__ . '/../../config.inc.php';

/** 链接数据库 */
$_CONNECTION = \Doctrine\DBAL\DriverManager::getConnection($_CONFIG['db']);


$_FAKER = Faker\Factory::create();

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos(substr($uri, strlen($rewrite)), '?')) {
  $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

/** logger */
$_API_LOGGER = new Monolog\Logger(substr($uri, 2));

$pdo = new PDO("mysql" . ":dbname=" . $_CONFIG['db']["dbname"] . ";host=" . $_CONFIG['db']["host"], $_CONFIG['db']["user"], $_CONFIG['db']["password"]);

/** Create MysqlHandler */
$mySQLHandler = new MySQLHandler\MySQLHandler($pdo, "logs", array('var', 'value', 'uuid', 'timestamp'), \Monolog\Logger::DEBUG);

$_API_LOGGER->pushHandler($mySQLHandler);
$_API_LOGGER_UUID = md5(time() . mt_rand(1, 1000000));
