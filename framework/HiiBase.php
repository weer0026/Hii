<?php
//定义当前目录的路径
defined('HII_PATH') or define('HII_PATH', dirname(__FILE__));

class HiiBase
{
	/**
	*   自动加载方法 用于HiiBase中的spl_autoload_register
	*/
	public static function autoload($className, $classMapOnly = false) 
	{
		if (isset(self::$_coreClasses[$className])) {
			include(HII_PATH.self::$_coreClasses[$className]);
		}
	}

	//创建新的web实例
	public static function createWebApplication($config = null) 
	{
		return self::createApplication('CWebApplication', $config);
	}

	//根据class参数返回对应的实例
	public static function createApplication($class, $config = null)
	{
		return new $class($config);
	}

	/**
	 *  自动加载类中使用，用来自动加载核心代码
	 */
	private static $_coreClasses = [
		'CApplication' => '/base/CApplication.php',
		'CWebApplication' => '/web/CWebApplication.php',
		'CModule' => '/base/CModule.php',
		'CComponent' => '/base/CComponent.php'
	];
}
//注册自动加载方法
//调用自动加载方法 HiiBase::autoload()
spl_autoload_register(['HiiBase', 'autoload']);