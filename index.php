<?php
require_once __DIR__ . '/vendor/autoload.php';

$rewrite = "/?";

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
