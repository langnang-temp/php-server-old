<?php

// require module controllers
require_path(__DIR__, function ($pathinfo) {
  return !in_array($pathinfo['filename'], ['api', 'main.test', 'main.auto']);
});
