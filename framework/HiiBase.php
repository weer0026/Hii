<?php
//定义核心代码的路径
defined('HII_PATH') or define('HII_PATH', dirname(__FILE__));
//是否调用框架定义的异常处理函数
defined('HII_ENABLE_EXCEPTION_HANDLER') or define('HII_ENABLE_EXCEPTION_HANDLER',true);
//是否调用框架定义的错误处理函数
defined('HII_ENABLE_ERROR_HANDLER') or define('HII_ENABLE_ERROR_HANDLER',true);

class HiiBase
{
	//存储路径别名的映射数组
	private static $_aliases = ['system' => HII_PATH];
	//存储实例化的应用，web或者console, Hii::_$app 相当于调用对应的应用实例
	private static $_app;
	//用来存储CLogger的实例
	private static $_logger;
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
	 *  设置路径别名
	 *  该方法不检测路径是否存在，也不格式化路径信息
	 *  @param string $alias 别名
	 *  @param string $path  路径信息
	 */
	public static function setPathOfAlias($alias, $path)
	{
		//路径为空就清除对应的别名
		if (empty($path)) {
			unset(self::$_aliases[$alias]);
		} else {
			self::$_aliases = rtrim($path, '\\/');//删除路径尾部的左右斜杠
		}
	}

	/**
	 *  把别名转换成真实的文件路径，但是这个函数不会去判断这个路径是否存在
	 */
	public static function getPAthOfAlias($alias)
	{
		if (isset(self::$_aliases[$alias])) {
			return self::$_aliases[$alias];
		} elseif (($pos = strpos($alias, '.')) !== false) {
			//路径里面带有别名
			$rootAlias = substr($alias,0,$pos);//取出第一个别名
			if (isset($_aliases[$rootAlias])) {
				//处理路径把别名替换成真实路径
				//处理细节：把 . 替换成目录分隔符，把 * 去掉
				return self::$_aliases[$rootAlias] = rtrim(self::$_aliases[$alias].DIRECTORY_SEPARATOR.str_replace('.', DIRECTORY_SEPARATOR, substr($alias, $pos+1)), '*'.DIRECTORY_SEPARATOR);
			}
		} elseif (self::$_app instanceof CWebApplication) {
			//TODO 这里没看懂为什么这么做
		}
		return false;
	}

	public static function log($msg, $level = CLogger::LEVEL_INFO, $category = 'application')
	{
		//存储日志类
		if (self::$_logger === null) {
			self::$_logger = new CLogger;
		}
	}

	/**
	 *  将传入的信息翻译成其他语言版本
	 */
	public static function t($category, $message, $params = [], $source = null, $language = null)
	{
		if (self::$_app !== null) {
			if ($source === null) {
				//不知定资源时候调用的类
				$source = ($category === 'yii' || $category === 'zii') ? 'coreMessage' : 'message';
			}
			//TODO 指定资源调用的类
		}
		//params参数用来替换掉message里面的占位符（{value}）
		if ($params = []) {
			return $message;
		}
		if (!is_array($params)) {
			$params = [$params];
		}
	}

	/**
	 *  自动加载类中使用，用来自动加载核心代码
	 */
	private static $_coreClasses = [
		'CApplication' => '/base/CApplication.php',
		'CWebApplication' => '/web/CWebApplication.php',
		'CModule' => '/base/CModule.php',
		'CComponent' => '/base/CComponent.php',
		'CException' => 'base/CException.php',
		'CLogger' => '/logging/CLogger.php',
		'CList' => '/collections/CList.php',
	];
}
//注册自动加载方法
//调用自动加载方法 HiiBase::autoload()
spl_autoload_register(['HiiBase', 'autoload']);
//包含接口文件
require(HII_PATH.'/base/interfaces.php');