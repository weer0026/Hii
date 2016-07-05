<?php
defined('HII_DEBUG') or define('HII_DEBUG', true);
defined('HII_TRACE_LEVEL') or define('HII_TRACE_LEVEL', 3);
//包含核心代码
$currentDir = dirname(__FILE__);
$hii = $currentDir.'/framework/hii.php';
//配置文件
$config = $currentDir.'/protected/config/main.php';
require($hii);
// Hii::createWebApplication($config)->run();
Hii::createWebApplication($config);
