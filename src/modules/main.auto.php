<?php


// mt_rand(); // 提取随机任务

use Langnang\Module\Automate\Automate;
use Langnang\Module\Meta\Meta;
use Langnang\Module\Mock\Mock;

require_once __DIR__ . '/main.test.php';


/** 异常终止程序后，1秒后刷新页面 */
header("refresh: 1");
// 用户Ngix、Apache显示动态进度
header('X-Accel-Buffering: no');
//防止执行超时
set_time_limit(0);
//清空并关闭输出缓存
ob_end_clean();

$_AUTOMATE = new Automate();

$meta_controller = new Meta();
$mock_controller = new Mock();

require_path(__DIR__, function ($pathinfo) {
  return $pathinfo['filename'] === 'main.auto';
});

?>

<head>
  <title>Automate - 自动化任务</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <style>
    body,
    div input {
      font-family: Consolas;
      font-size: 9pt
    }
  </style>

</head>

<body>
  <ol id="app"> </ol>
  <script language="JavaScript">
    const app = document.getElementById('app');

    function updateProgress(sMsg, iWidth) {
      const item = document.createElement("li");
      item.innerHTML = sMsg;
      if (app.children[0]) {
        app.insertBefore(item, app.children[0]);
      } else {
        app.appendChild(item);
      }
    }
  </script>
  <?php

  flush(); //将输出发送给客户端浏览器 

  while (true) {
    $result = $_AUTOMATE->start();
  ?>
    <script language="JavaScript">
      updateProgress('<? echo $_AUTOMATE->active->name; ?>::<? echo addslashes(json_encode($result, JSON_UNESCAPED_UNICODE)); ?>');
    </script>
  <?php
    flush(); //将输出发送给客户端浏览器，使其可以立即执行服务器端输出的 JavaScript 程序。 

    ob_flush();
    usleep(0.5 * 1000 * 1000);
  }
  ?>
  <?php
  flush();
  ob_end_flush();
  ?>
</body>

</html>