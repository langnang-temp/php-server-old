<?php


/**
 * 获取精确到毫秒的时间戳
 */
function timestamp(): int
{
  list($microsecond, $time) = explode(' ', microtime()); //' '中间是一个空格
  return (float)sprintf('%.0f', (floatval($microsecond) + floatval($time)) * 1000);
}


/**
 * 遍历加载文件夹下文件
 */
function require_path($path, $callback)
{
  if (is_dir($path)) {
    foreach (scandir($path) as $_path) {
      if (!in_array($_path, ['.', '..'])) {
        require_path($path . '/' . $_path, $callback);
      }
    }
  } else {
    if (pathinfo($path)['extension'] === 'php' && $callback(pathinfo($path))) {
      require_once $path;
    }
  }
}

/**
 * 过滤处理数据
 */
function filter($value, $program, $separator = "|")
{
  return  array_reduce(explode($separator, $program), function ($value, $exp) {
    $funcExpArray = preg_split("/(\(|\)|,)/", $exp);
    $last = array_pop($funcExpArray);
    $funcName = $funcExpArray[0];
    if (function_exists($funcName)) {
      if (in_array($funcName, ['explode'])) {
        $value = call_user_func($funcName, $funcExpArray[1], $value, ...array_slice($funcExpArray, 2));
      } else {
        $value = call_user_func($funcName, $value, ...array_slice($funcExpArray, 1));
      }
    } else {
      throw new Exception("not exist function({$funcName}).");
      return;
    }
    if ($last && $last[0] == '[' && $last[strlen($last) - 1] == ']') {
      $key = substr($last, 1, -1);
      $value = $value[$key];
    }
    return $value;
  }, $value);
}


function request($args)
{
  if (!isset($args['url'])) throw new Exception("no url specified.");
  $args = $args['url'];
  $method = strtolower(isset($args['method']) ? $args['method'] : "GET");
  $headers = isset($args['headers']) ? (array)$args['headers'] : [];
  $data = isset($args['data']) ? (array)$args['data'] : [];
  if (!in_array($method, ['get', 'post', 'put', 'delete'])) $method = 'get';
  $response = WpOrg\Requests\Requests::$method($args, $headers, $data);
  $result = (array)$response;
  $result['headers'] = $response->headers->getAll();
  foreach ($result['headers']  as $key => $value) {
    if (is_array($value) && sizeof($value) === 1) {
      $result['headers'][$key] = $value[0];
    }
  }
  $body = json_decode($response->body, true);
  if (!is_null($body)) $result['body'] = $body;
  return $result;
}
