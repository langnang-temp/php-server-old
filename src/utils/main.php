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

function array_find_index($array, $value, $key = null)
{
  if (sizeof($array) == 0) return -1;
  foreach ($array as $_key => $_value) {
    if (is_null($key)) {
      if ($_value == $value) {
        return $_key;
      }
    } else {
      if ($_value[$key] == $value) {
        return $_key;
      }
    }
  }
  return -1;
}
/**
 * 列表数据转树状
 * @param array $array
 * @param string $child_key
 * @param string $parent_key
 * @param mixed $parent_value = null
 * @param int $depth = 1
 */
function list_to_tree(array $array, string $child_key, string $parent_key, $parent_value = null, $depth = 1)
{
  $array = json_decode(json_encode($array), JSON_UNESCAPED_UNICODE);
  if ($depth === 1) {
    foreach ($array as $item) {
      if (!is_null($item[$parent_key]) || $item[$parent_key] != 0) {
        if (array_find_index($array, $item[$parent_key], $child_key) == -1) {
          array_push($array, array(
            $child_key => $item[$parent_key],
            $parent_key => $parent_value
          ));
        }
      }
    }
  }
  $children = [];
  foreach ($array as $item) {
    if ($item[$parent_key] === $parent_value && $item[$child_key] !== $parent_value) {
      array_push($children, $item);
    }
  }
  if (sizeof($children) == 0) {
    return $children;
  } else {
    return array_map(function ($item) use ($array, $child_key, $parent_key) {
      $item["children"] = list_to_tree($array, $child_key, $parent_key, $item[$child_key], 0);
      return $item;
    }, $children);
  }
}
/**
 * 树状数据转列表数据
 */
function tree_to_list($tree = [], $children = 'children')
{
  if (empty($tree) || !is_array($tree)) {
    return $tree;
  }
  $arrRes = [];
  foreach ($tree as $k => $v) {
    $arrTmp = $v;
    unset($arrTmp[$children]);
    $arrRes[] = $arrTmp;
    if (!empty($v[$children])) {
      $arrTmp = tree_to_list($v[$children]);
      $arrRes = array_merge($arrRes, $arrTmp);
    }
  }
  return $arrRes;
}

/**
 * 检测非零空值
 */
function empty_not_zero($value)
{
  return empty($value) && $value !== 0;
}


function read_excel()
{
}

function write_excel()
{
}
