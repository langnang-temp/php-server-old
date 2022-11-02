<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/utils/main.php';
require_once __DIR__ . '/src/modules/main.php';


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
    require_once __DIR__ . '/src/apis/main.php';
    require_once __DIR__ . '/src/routes/main.php';
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
