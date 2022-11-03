<?php

$router->addRoute('GET', '/', function ($vars) {
  echo file_get_contents(__DIR__ . '/../views/index.html');
});
