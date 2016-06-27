<?php
/**
 *  这个类我目前的理解是用来把对象像数组那样操作而写的
 *  继承CComponent是为了使用__get和__set(暂定)
 *  三个接口分别实现：迭代出私有属性，可以用数组方式操作对象，可以使用count来返回数量
 */
class CList extends CComponent implements IteratorAggregate,ArrayAccess,Countable
{
	public function __construct($data = null, $readOnly = false)
	{
		
	}
}