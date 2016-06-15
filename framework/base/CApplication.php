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
	}

	/**
	 *  设置程序根路径
	 */
	public function setBasePath($path)
	{
		if ($this->_basePath = realpath($path) === false || !is_dir($path)) {
			//检测路径是否是合法的目录
			//TODO 多语言后面加
			throw new CException("应用程序{$path}的路径不是一个有效的目录!");
		}
	}

	//获取设置的应用程序路径
	public function getBasePath()
	{
		return $this->_basePath;
	}
}