<?php

global $_SWAGGER;
$module = "example";
array_push($_SWAGGER, ["name" => "{$module}", "url" => "/?/api/swagger/{$module}", "path" => __FILE__]);

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


$router->addRoute('GET', '/', function ($vars) {
  print_r($vars);
});
$router->addRoute('GET', '/users', function ($vars) {
  print_r("users");
});
// {id} must be a number (\d+)
$router->addRoute('GET', '/user/{id:\d+}', function ($vars) {
  print_r($vars);
});
// The /{title} suffix is optional
$router->addRoute('GET', '/articles/{id:\d+}[/{title}]', function ($vars) {
  print_r($vars);
});
// logger
$router->addRoute('GET', '/logger', function ($vars) {
  $content = explode("\n", file_get_contents(__DIR__ . '/../../.log'));
  foreach ($content as $v) {
    echo $v . '<br/>';
  }
});
// configs
$router->addRoute('GET', '/install/{host}/{dbname}/{user}/{password}', function ($vars) {
  $content = "<?php 
return array(
  'rewrite' => '/?',
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
  var_dump($_CONFIG);
});
// mysql connection
$router->addRoute('GET', '/conn', function ($vars) {
  $_CONFIG = require_once(__DIR__ . '/../../config.inc.php');
  $conn = \Doctrine\DBAL\DriverManager::getConnection($_CONFIG);
  $rows = $conn->fetchAllAssociative("SHOW TABLES");
  var_dump($rows);
});
$router->addRoute('GET', '/monolog-mysql', function ($vars) {
  $_CONFIG = require_once(__DIR__ . '/../../config.inc.php');
  $conn = \Doctrine\DBAL\DriverManager::getConnection($_CONFIG);
  $sql_create_table = "CREATE TABLE IF NOT EXISTS `log` (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, channel VARCHAR(255), level INTEGER, message LONGTEXT, time INTEGER UNSIGNED, INDEX(channel) USING HASH, INDEX(level) USING HASH, INDEX(time) USING BTREE
)";
  $conn->executeQuery($sql_create_table);
  $pdo = new PDO("mysql" . ":dbname=" . $_CONFIG["dbname"] . ";host=" . $_CONFIG["host"], $_CONFIG["user"], $_CONFIG["password"]);

  $mySQLHandler = new MySQLHandler\MySQLHandler($pdo, "log", array('var', 'result', 'uuid', 'timestamp'), \Monolog\Logger::DEBUG);

  //Create logger
  $logger = new \Monolog\Logger("monolog-mysql");
  $logger->pushHandler($mySQLHandler);

  //Now you can use the logger, and further attach additional information
  $logger->warning("This is a great message, woohoo!", array('var'  => 'var', 'result'  => 'result', 'uuid'  => 'uuid', 'timestamp'  => 'timestamp'));

  $sql_select_list = "SELECT * FROM `log` ORDER BY `id` DESC LIMIT 10 ";
  $rows = $conn->fetchAllAssociative($sql_select_list);
  var_dump($rows);
});
// try-catch
$router->addRoute('GET', '/try-catch', function ($vars) {
  try {
    throw new Exception("test try-catch exception.");
    exit;
  } catch (Exception $error) {
    echo $error->getMessage();
  }
});
// swagger-php
$router->addRoute('GET', '/swagger-php', function ($vars) {
  $openapi = \OpenApi\Generator::scan([__FILE__]);
  header('Content-Type: application/json');
  echo $openapi->toJson();
});

// faker
$router->addRoute('GET', '/faker/{method}', function ($vars) {
  $_FAKER = Faker\Factory::create();
  $method = $vars['method'];
  print_r(["method" => $method, "value" => $_FAKER->{$vars['method']}()]);
});

$router->addRoute("GET", "/request", function ($vars) {
  // if (!isset($vars['url'])) return new Exception("no url specified.");
  // $url = $vars['url'];
  $url = 'https://inshorts.deta.dev/news?category=science';
  $method = strtolower($vars['method']);
  $headers = isset($vars['headers']) ? (array)$vars['headers'] : [];
  $data = isset($vars['data']) ? (array)$vars['data'] : [];
  if (!in_array($method, ['get', 'post', 'put', 'delete'])) $method = 'get';
  $response = WpOrg\Requests\Requests::$method($url, $headers, $data);
  $body = json_decode($response->body, true);
  if (!is_null($body)) $response->body = $body;
  var_dump($response);
});
