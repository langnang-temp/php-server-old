<?php
require_once __DIR__ . '/vendor/autoload.php';


$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
  $r->addRoute('GET', '/', function ($vars) {
    print_r($vars);
  });
  $r->addRoute('GET', '/users', function ($vars) {
    print_r("users");
  });
  // {id} must be a number (\d+)
  $r->addRoute('GET', '/user/{id:\d+}', function ($vars) {
    print_r($vars);
  });
  // The /{title} suffix is optional
  $r->addRoute('GET', '/articles/{id:\d+}[/{title}]', function ($vars) {
    print_r($vars);
  });
});

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
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
