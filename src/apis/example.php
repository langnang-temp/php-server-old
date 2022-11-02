<?php


$router->addRoute('GET', '/', function ($vars) {
  return "/";
});
$router->addRoute('GET', '/users', function ($vars) {
  return "users";
});
// {id} must be a number (\d+)
$router->addRoute('GET', '/user/{id:\d+}', function ($vars) {
  return $vars;
});
// The /{title} suffix is optional
$router->addRoute('GET', '/articles/{id:\d+}[/{title}]', function ($vars) {
  return $vars;
});
// logger
$router->addRoute('GET', '/logger', function ($vars) {
  $content = explode("\n", file_get_contents(__DIR__ . '/../../.log'));
  return $content;
});
// configs
$router->addRoute('GET', '/install/{host}/{dbname}/{user}/{password}', function ($vars) {
  $content = "<?php 

return array(
  'rewrite' => '/?'
  'db' => array(
    'host' => '{$vars['host']}',
    'dbname' => '{$vars['dbname']}',
    'user' => '{$vars['user']}',
    'password' => '{$vars['password']}',
    'driver' => 'pdo_mysql',
  ),
);      
";
  file_put_contents(__DIR__ . '/../../config.inc.php', $content);
  $_CONFIG = require_once(__DIR__ . '/../../config.inc.php');
  return $_CONFIG;
});
// mysql connection
$router->addRoute('GET', '/conn', function ($vars) {
  global $_CONNECTION;

  $rows = $_CONNECTION->fetchAllAssociative("SHOW TABLES");
  return $rows;
});
$router->addRoute('GET', '/monolog-mysql', function ($vars) {
  global $_CONFIG;
  global $_CONNECTION;

  $sql_create_table = "CREATE TABLE IF NOT EXISTS `log` (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, channel VARCHAR(255), level INTEGER, message LONGTEXT, time INTEGER UNSIGNED, INDEX(channel) USING HASH, INDEX(level) USING HASH, INDEX(time) USING BTREE
)";
  $_CONNECTION->executeQuery($sql_create_table);
  $pdo = new PDO("mysql" . ":dbname=" . $_CONFIG['db']["dbname"] . ";host=" . $_CONFIG['db']["host"], $_CONFIG['db']["user"], $_CONFIG['db']["password"]);

  $mySQLHandler = new MySQLHandler\MySQLHandler($pdo, "log", array('var', 'result', 'uuid', 'timestamp'), \Monolog\Logger::DEBUG);

  //Create logger
  $logger = new \Monolog\Logger("monolog-mysql");
  $logger->pushHandler($mySQLHandler);

  //Now you can use the logger, and further attach additional information
  $logger->warning("This is a great message, woohoo!", array('var'  => 'var', 'result'  => 'result', 'uuid'  => 'uuid', 'timestamp'  => 'timestamp'));

  $sql_select_list = "SELECT * FROM `log` ORDER BY `id` DESC LIMIT 10 ";
  $rows = $_CONNECTION->fetchAllAssociative($sql_select_list);

  return $rows;
});
// try-catch
$router->addRoute('GET', '/try-catch', function ($vars) {
  throw new Exception("test try-catch exception.");
});
// swagger-php
$router->addRoute('GET', '/swagger-php', function ($vars) {
  $openapi = \OpenApi\Generator::scan(['swagger/examples']);
  header('Content-Type: application/json');
  echo $openapi->toJson();
  exit;
});

// faker
$router->addRoute('GET', '/faker/{method}', function ($vars) {
  $_FAKER = Faker\Factory::create();
  $method = $vars['method'];

  return ["method" => $method, "value" => $_FAKER->{$vars['method']}()];
});

// request
$router->addRoute("GET", "/request", function ($vars) {
  $url = 'https://inshorts.deta.dev/news?category=science';
  $method = strtolower($vars['method']);
  $headers = isset($vars['headers']) ? (array)$vars['headers'] : [];
  $data = isset($vars['data']) ? (array)$vars['data'] : [];
  if (!in_array($method, ['get', 'post', 'put', 'delete'])) $method = 'get';
  $response = WpOrg\Requests\Requests::$method($url, $headers, $data);
  $body = json_decode($response->body, true);
  if (!is_null($body)) $response->body = $body;
  return $response;
});
