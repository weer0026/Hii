<?php 
//应用抽象类
/**
 *  作为web应用和console应用的父级抽象类，封装了前面2个的通用函数和属性
 */
abstract class CApplication extends CModule
{
	private $_basePath;

	public function __construct($config = null)
	{	
		//config为字符串的时候当作文件路径加载
		if (is_string($config)) {
			$config = require($config);
		} elseif (isset($config['basePath'])) {
			//设置程序所在目录
			$this->setBasePath($config['basePath']);
			unset($config['basePath']);
		} else {
			//没有指定程序路径的时候使用默认路径 protected
			$this->setBasePath('protected');
		}
		//开始设置路径别名
		//设置applicaition应用路径别名
		Hii::setPathOfAlias('application', $this->getBasePath());
		//设置当前执行脚本的绝对路径为webroot
		Hii::setPathOfAlias('webroot', dirname($_SERVER['SCRIPT_FILENAME']));
		//设置自定义扩展的路径
		if (isset($config['extensionPath'])) {
			$this->setExtensionPath($config['extensionPath']);
			unset($config['extensionPath']);
		} else {
			//默认路径为 protected/extensions
			Hii::setPathOfAlias('ext', $this->getBasePath().DIRECTORY_SEPARATOR.'extensions');
		}
		//如果配置文件里面设置了alias，添加到$_aliases
		if (isset($config['aliases'])) {
			$this->setAlias($config['aliases']);
			unset($config['aliases']);
		}
	}

	/**
	 *  设置application程序根路径
	 */
	public function setBasePath($path)
	{
		if ($this->_basePath = realpath($path) === false || !is_dir($path)) {
			//检测路径是否是合法的目录
			//TODO 多语言后面加
			throw new CException("应用程序{$path}的路径不是一个有效的目录!");
		}
	}

	/**
	 *  设置第三方扩展所在的路径，并设置别名ext
	 */
	public function setExtensionPath($path)
	{
		if ($extensionPath = realpath($path) === false || !is_dir($extensionPath)) {
			throw new CException('第三方的extension加载路径不存在！');
		}
		//设置别名ext
		Hii::setPathOfAlias('ext', $extensionPath);
	}

	//获取设置的应用程序路径
	public function getBasePath()
	{
		return $this->_basePath;
	}

	public function setAlias($mappings)
	{

	}
}