<?php
//包含核心代码
$currentDir = dirname(__FILE__);
$hii = $currentDir.'/framework/hii.php';
//配置文件
$config = $currentDir.'/protected/config/main.php';
require($hii);
// Hii::createWebApplication($config)->run();
Hii::createWebApplication($config);
