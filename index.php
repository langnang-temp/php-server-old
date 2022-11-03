<?php

/** 允许跨域请求 */
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, OPTIONS, PATCH, DELETE');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type, x-xsrf-token, x_csrftoken, Cache-Control, X-Requested-With');

/** 载入依赖 */
require_once __DIR__ . '/vendor/autoload.php';

/** 载入配置支持 */
if (!file_exists(__DIR__ . '/config.inc.php')) {
  file_exists('./install.php') ? header('Location: install.php') : print('Missing Config File');
  exit;
}
$_CONFIG = require_once __DIR__ . '/config.inc.php';

/** 链接数据库 */
$_CONNECTION = \Doctrine\DBAL\DriverManager::getConnection($_CONFIG['db']);

/** 伪静态 */
$rewrite = is_null($_CONFIG['rewrite']) ? '' : $_CONFIG['rewrite'];

require_once __DIR__ . '/src/utils/main.php';
require_once __DIR__ . '/src/modules/main.php';

/** faker */
$_FAKER = Faker\Factory::create();


/** swagger */
$_SWAGGER = [];

// Fetch method and URI from somewhere
// 请求方式: GET, POST, PUT, PATCH, DELETE, HEAD
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos(substr($uri, strlen($rewrite)), '?')) {
  $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

/** logger */
$_API_LOGGER = new Monolog\Logger(substr($uri, 2));

$pdo = new PDO("mysql" . ":dbname=" . $_CONFIG['db']["dbname"] . ";host=" . $_CONFIG['db']["host"], $_CONFIG['db']["user"], $_CONFIG['db']["password"]);

/** Create MysqlHandler */
$mySQLHandler = new MySQLHandler\MySQLHandler($pdo, "log", array('var', 'value', 'uuid', 'timestamp'), \Monolog\Logger::DEBUG);

$_API_LOGGER->pushHandler($mySQLHandler);
$_API_LOGGER_UUID = md5(time() . mt_rand(1, 1000000));

$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $router) use ($rewrite) {
  $router->addGroup($rewrite, function (FastRoute\RouteCollector $router) {
    require_once __DIR__ . '/src/apis/main.php';
    require_once __DIR__ . '/src/routes/main.php';
  });
});

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

$result = null;

switch ($routeInfo[0]) {
  case FastRoute\Dispatcher::NOT_FOUND:
    // ... 404 Not Found
    if (preg_match('/^\/api/i', substr($uri, strlen($rewrite)))) {
      $result = new Exception("404 Not Found", 404);
    } else {
      echo file_get_contents(__DIR__ . "/src/views/404.html");
      exit;
    }
    break;
  case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
    $allowedMethods = $routeInfo[1];
    $result = new Exception("405 Method Not Allowed", 405);
    // ... 405 Method Not Allowed
    break;
  case FastRoute\Dispatcher::FOUND:
    $handler = $routeInfo[1];

    // POST request
    if (!is_null(json_decode(file_get_contents('php://input'), true))) {
      $_POST = array_merge($_POST, json_decode(file_get_contents('php://input'), true));
    }
    $vars = array_merge([
      "_method" => $httpMethod,
      "_path" => $uri,
    ], $_GET, $_POST, $routeInfo[2]);

    // ... call $handler with $vars
    try {
      if (is_object($handler)) {
        $result = $handler($vars);
      } else if (is_string($handler) && function_exists($handler)) {
        $result = call_user_func($handler, $vars);
      } else if (is_array($handler) && method_exists($handler[0], $handler[1])) {
        $result = call_user_func($handler, $vars);
      } else {
        throw new Exception("error handler method.", 404);
      }
    } catch (Exception $e) {
      $result = $e;
    }
    break;
}

/** 返回API请求数据处理 */
if (!preg_match('/^\/api/', substr($uri, strlen($rewrite)))) exit;
/** 异常 */
if ($result instanceof Exception) {
  $result = [
    "status" => empty($result->getCode()) ? 400 : $result->getCode(),
    "statusText" => "Error",
    "message" => $result->getMessage(),
  ];
  $_API_LOGGER->error($result["message"], array("var" => "result", "value" => json_encode($result, true), "uuid" => $_API_LOGGER_UUID, "timestamp" => timestamp()));
} elseif (is_null($result)) {
  $result = array(
    "status" => 400,
    "statusText" => 'Error',
  );
} else {
  $result = array(
    "status" => 200,
    "statusText" => 'Success',
    "data" => $result,
  );
}
/** */
header('Content-Type: application/json');
/** 打印响应报文 */
echo json_encode(array_filter((array)$result), JSON_UNESCAPED_UNICODE);
exit;
