<?php

global $_SWAGGER;
$module = "main";
array_push($_SWAGGER, ["name" => "{$module}", "url" => "/?/api/swagger/{$module}", "path" => __FILE__]);

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
  $openapi = \OpenApi\Generator::scan(['src/apis/example.php']);
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


/**
 * @OA\Info(
 *     version="1.0",
 *     title="Example for response examples value"
 * )
 */

/**
 * @OA\Put(
 *     path="/users/{id}",
 *     summary="Updates a user",
 *     @OA\Parameter(
 *         description="Parameter with mutliple examples",
 *         in="path",
 *         name="id",
 *         required=true,
 *         @OA\Schema(type="string"),
 *         @OA\Examples(example="int", value="1", summary="An int value."),
 *         @OA\Examples(example="uuid", value="0006faf6-7a61-426c-9034-579f2cfcfa83", summary="An UUID value."),
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="OK"
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/users",
 *     summary="Adds a new user",
 *     @OA\RequestBody(
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 @OA\Property(
 *                     property="id",
 *                     type="string"
 *                 ),
 *                 @OA\Property(
 *                     property="name",
 *                     type="string"
 *                 ),
 *                 example={"id": "a3fb6", "name": "Jessica Smith"}
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="OK"
 *     )
 * )
 */
