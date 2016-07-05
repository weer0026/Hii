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
	private static $_imports = [];
	private static $_includePaths;

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

	public static function setApplication($app)
	{
		if(self::$_app === null || $app === null) {
			self::$_app = $app;
		} else {
			throw new CException(Yii::t('hii','Hii application can only be created once.'));
		}
	}

	/**
	 * 引入类和目录文件
	 *
	 * 使用同名php文件来加载类
	 * Importing a class is like including the corresponding class file.
	 * The main difference is that importing a class is much lighter because it only
	 * includes the class file when the class is referenced the first time.
	 *
	 * 使用加载路径来引用目录
	 * Importing a directory is equivalent to adding a directory into the PHP include path.
	 * If multiple directories are imported, the directories imported later will take
	 * precedence in class file searching (i.e., they are added to the front of the PHP include path).
	 *
	 * 允许使用路径别名
	 * Path aliases are used to import a class or directory. For example,
	 * <ul>
	 *   <li><code>application.components.GoogleMap</code>: import the <code>GoogleMap</code> class.</li>
	 *   <li><code>application.components.*</code>: import the <code>components</code> directory.</li>
	 * </ul>
	 *
	 * 同名路径可以多次被加载，但是只有第一次有效
	 * The same path alias can be imported multiple times, but only the first time is effective.
	 * 引用目录不代表引入了其子目录
	 * Importing a directory does not import any of its subdirectories.
	 *
	 * Starting from version 1.1.5, this method can also be used to import a class in namespace format
	 * (available for PHP 5.3 or above only). It is similar to importing a class in path alias format,
	 * except that the dot separator is replaced by the backslash separator. For example, importing
	 * <code>application\components\GoogleMap</code> is similar to importing <code>application.components.GoogleMap</code>.
	 * The difference is that the former class is using qualified name, while the latter unqualified.
	 *
	 * Note, importing a class in namespace format requires that the namespace corresponds to
	 * a valid path alias once backslash characters are replaced with dot characters.
	 * For example, the namespace <code>application\components</code> must correspond to a valid
	 * path alias <code>application.components</code>.
	 *
	 * @param string $alias path alias to be imported
	 * @param boolean $forceInclude whether to include the class file immediately. If false, the class file
	 * will be included only when the class is being used. This parameter is used only when
	 * the path alias refers to a class.
	 * @return string the class name or the directory that this alias refers to
	 * @throws CException if the alias is invalid
	 */
	public static function import($alias,$forceInclude=false)
	{
		if(isset(self::$_imports[$alias]))  // previously imported //之前已经加载过了，会保存在内存中
			return self::$_imports[$alias];

		if(class_exists($alias,false) || interface_exists($alias,false))
			return self::$_imports[$alias]=$alias;

		if(($pos=strrpos($alias,'\\'))!==false) // a class name in PHP 5.3 namespace format
		{
			$namespace=str_replace('\\','.',ltrim(substr($alias,0,$pos),'\\'));
			if(($path=self::getPathOfAlias($namespace))!==false)
			{
				$classFile=$path.DIRECTORY_SEPARATOR.substr($alias,$pos+1).'.php';
				if($forceInclude)
				{
					if(is_file($classFile))
						require($classFile);
					else
						throw new CException(Yii::t('yii','Alias "{alias}" is invalid. Make sure it points to an existing PHP file and the file is readable.',array('{alias}'=>$alias)));
					self::$_imports[$alias]=$alias;
				}
				else
					self::$classMap[$alias]=$classFile;
				return $alias;
			}
			else
			{
				// try to autoload the class with an autoloader
				if (class_exists($alias,true))
					return self::$_imports[$alias]=$alias;
				else
					throw new CException(Yii::t('yii','Alias "{alias}" is invalid. Make sure it points to an existing directory or file.',
						array('{alias}'=>$namespace)));
			}
		}

		if(($pos=strrpos($alias,'.'))===false)  // a simple class name
		{
			if($forceInclude && self::autoload($alias))
				self::$_imports[$alias]=$alias;
			return $alias;
		}

		$className=(string)substr($alias,$pos+1);
		$isClass=$className!=='*';

		if($isClass && (class_exists($className,false) || interface_exists($className,false)))
			return self::$_imports[$alias]=$className;

		if(($path=self::getPathOfAlias($alias))!==false)
		{
			//加载类文件
			if($isClass)
			{
				if($forceInclude)
				{
					if(is_file($path.'.php'))
						require($path.'.php');
					else
						throw new CException(Yii::t('yii','Alias "{alias}" is invalid. Make sure it points to an existing PHP file and the file is readable.',array('{alias}'=>$alias)));
					self::$_imports[$alias]=$className;
				}
				else
					self::$classMap[$className]=$path.'.php';
				return $className;
			}
			else  // a directory 加载目录
			{
				if(self::$_includePaths===null)
				{
					self::$_includePaths=array_unique(explode(PATH_SEPARATOR,get_include_path()));
					if(($pos=array_search('.',self::$_includePaths,true))!==false)
						unset(self::$_includePaths[$pos]);
				}

				array_unshift(self::$_includePaths,$path);

				if(self::$enableIncludePath && set_include_path('.'.PATH_SEPARATOR.implode(PATH_SEPARATOR,self::$_includePaths))===false)
					self::$enableIncludePath=false;

				return self::$_imports[$alias]=$path;
			}
		}
		else
			throw new CException(Yii::t('yii','Alias "{alias}" is invalid. Make sure it points to an existing directory or file.',
				array('{alias}'=>$alias)));
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
		'CException' => '/base/CException.php',
		'CLogger' => '/logging/CLogger.php',
		'CLogRoute' => '/logging/CLogRoute.php',
		'CList' => '/collections/CList.php',
		'CErrorEvent' => '/base/CErrorEvent.php',
		'CErrorHandler' => '/base/CErrorHandler.php',
		'CExceptionEvent' => '/base/CExceptionEvent.php',
	];
}
//注册自动加载方法
//调用自动加载方法 HiiBase::autoload()
spl_autoload_register(['HiiBase', 'autoload']);
//包含接口文件
require(HII_PATH.'/base/interfaces.php');