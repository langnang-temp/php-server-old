<?php


/**
 * 获取精确到毫秒的时间戳
 */
function timestamp(): int
{
  list($microsecond, $time) = explode(' ', microtime()); //' '中间是一个空格
  return (float)sprintf('%.0f', (floatval($microsecond) + floatval($time)) * 1000);
}
