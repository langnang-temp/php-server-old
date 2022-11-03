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
