<?php
require_once __DIR__ . '/vendor/autoload.php';

// 伪静态
$rewrite = "/?";

// create a log channel
$log = new Monolog\Logger('name');
$log->pushHandler(new Monolog\Handler\StreamHandler(__DIR__ . '/.log', Monolog\Logger::DEBUG));

// add records to the log
$log->warning('Foo');
$log->error('Bar');

$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $router) use ($rewrite) {
  $router->addGroup($rewrite, function (FastRoute\RouteCollector $router) {
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
      $content = explode("\n", file_get_contents(__DIR__ . '/.log'));
      foreach ($content as $v) {
        echo $v . '<br/>';
      }
    });
    // configs
    $router->addRoute('GET', '/install/{host}/{dbname}/{user}/{password}', function ($vars) {
      $content = "<?php 
return array(
  'host' => '{$vars['host']}',
  'dbname' => '{$vars['dbname']}',
  'user' => '{$vars['user']}',
  'password' => '{$vars['password']}',
  'driver' => 'pdo_mysql',
);      
";
      file_put_contents(__DIR__ . '/config.inc.php', $content);
      $_CONFIG = require_once(__DIR__ . '/config.inc.php');
      var_dump($_CONFIG);
    });
    // mysql connection
    $router->addRoute('GET', '/conn', function ($vars) {
      $_CONFIG = require_once(__DIR__ . '/config.inc.php');
      $conn = \Doctrine\DBAL\DriverManager::getConnection($_CONFIG);
      $rows = $conn->fetchAllAssociative("SHOW TABLES");
      var_dump($rows);
    });
    $router->addRoute('GET', '/monolog-mysql', function ($vars) {
      $_CONFIG = require_once(__DIR__ . '/config.inc.php');
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
      $openapi = \OpenApi\Generator::scan(['swagger/examples']);
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
  });
});


// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos(substr($uri, strlen($rewrite)), '?')) {
  $uri = substr($uri, 0, $pos);
}

$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
  case FastRoute\Dispatcher::NOT_FOUND:
    die("404 Not Found");
    // ... 404 Not Found
    break;
  case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
    $allowedMethods = $routeInfo[1];
    die("405 Method Not Allowed");
    // ... 405 Method Not Allowed
    break;
  case FastRoute\Dispatcher::FOUND:
    $handler = $routeInfo[1];
    $vars = $routeInfo[2];
    // ... call $handler with $vars
    $handler($vars);
    break;
}
