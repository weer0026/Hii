<?php
/**
 * CModule 主要用来管理应用组件和子模块
 */
abstract class CModule extends CComponent
{
	/**
	 *  自定义根别名，并添加到Hii::$_alias里面
	 */
	public function setAliases($mappings)
	{
		foreach($mappings as $name => $alias) {
			if (($path = Hii::getPathOfAlias($alias)) !== false) {
				//alias里面含有别名的时候,先转化alias为正常路径
				Hii::setPathOfAlias($name, $path);
			} else {
				Hii::setPathOfAlias($name, $alias);
			}
		}
	}

	public function preinit()
	{

	}

	public function init()
	{
		
	}
}