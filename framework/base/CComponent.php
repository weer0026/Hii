<?php
//所有组件化的类的基类
/*
 *   定义setter和getter方法
 *
 */
class CComponent
{
	public function __get($name)
	{
		$getter = 'get'.$name;
		//是否存在函数
		if (method_exists($this, $getter)) {
			return $this->$getter();
		} elseif(strncasecmp($name, 'on'.$getter, 2) === 0 && method_exists($this, $name)) {
			//是否存在event方法
		}
	}
}