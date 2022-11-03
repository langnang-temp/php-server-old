<?php

// require module controllers
require_path(__DIR__, function ($pathinfo) {
  return $pathinfo['filename'] !== 'api' && $pathinfo['filename'] !== 'main.test';
});
