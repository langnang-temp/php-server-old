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
