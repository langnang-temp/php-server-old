<?php

global $_SWAGGER;
$module = "main";
array_push($_SWAGGER, ["name" => "{$module}", "url" => "/?/api/swagger/{$module}", "path" => __FILE__]);

/**
 * @OA\Info(
 *     title="PhpServer APIs",
 *     version="1.0",
 * )
 */

$router->addGroup("/api", function (FastRoute\RouteCollector $router) {

  require_once __DIR__ . '/example.php';

  $router->addGroup("/swagger", function (FastRoute\RouteCollector $router) {

    $router->addRoute('GET', '', function ($vars) {
      global $_SWAGGER;

      header('Content-Type: application/json');
      echo json_encode(array_map(function ($item) {
        unset($item['path']);
        return $item;
      }, $_SWAGGER), JSON_UNESCAPED_UNICODE);
      exit;
    });

    $router->addRoute('GET', '/{module:.+}', function ($vars) {
      global $_SWAGGER;
      $index = false;
      foreach ($_SWAGGER as $key => $item) {
        if ($item['name'] == $vars['module']) $index = $key;
      }
      if ($index === false) throw new Exception("error find swagger.");

      $openapi = \OpenApi\Generator::scan([$_SWAGGER[$index]['path']]);

      $openapi = json_decode($openapi->toJson(), true);
      $paths = $openapi['paths'];
      $openapi['paths'] = [];
      foreach ($paths as $path => $value) {
        $openapi['paths']['/?' . $path] = $value;
      }
      header('Content-Type: application/json');
      echo json_encode($openapi, JSON_UNESCAPED_UNICODE);
      exit;
      // return false;
    });
  });

  // require swagger apis
});
