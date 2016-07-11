<?php
//所有组件化的类的基类
/*
 *   定义setter和getter方法
 *
 */
class CComponent
{
	//存储event handler
	private $_e;
	private $_m;

	public function __get($name)
	{
		$getter='get'.$name;
		if(method_exists($this,$getter))
			return $this->$getter();
		elseif(strncasecmp($name,'on',2)===0 && method_exists($this,$name))
		{
			$name=strtolower($name);
			if(!isset($this->_e[$name]))
				$this->_e[$name]=new CList;
			return $this->_e[$name];
		}
		elseif(isset($this->_m[$name]))
			return $this->_m[$name];
		elseif(is_array($this->_m))
		{
			foreach($this->_m as $object)
			{
				if($object->getEnabled() && (property_exists($object,$name) || $object->canGetProperty($name)))
					return $object->$name;
			}
		}
		throw new CException(Hii::t('hii','Property "{class}.{property}" is not defined.',
			array('{class}'=>get_class($this), '{property}'=>$name)));	}


	public function __set($name,$value)
	{
		$setter='set'.$name;
		//调用set{Name}方法
		if(method_exists($this,$setter))
			return $this->$setter($value);
		elseif(strncasecmp($name,'on',2)===0 && method_exists($this,$name))
		{
			//构造event handler
			$name=strtolower($name);
			if(!isset($this->_e[$name]))
				$this->_e[$name]=new CList;
			return $this->_e[$name]->add($value);
		}
		elseif(is_array($this->_m))
		{
			foreach($this->_m as $object)
			{
				if($object->getEnabled() && (property_exists($object,$name) || $object->canSetProperty($name)))
					return $object->$name=$value;
			}
		}
		if(method_exists($this,'get'.$name))
			throw new CException(Hii::t('Hii','Property "{class}.{property}" is read only.',
				array('{class}'=>get_class($this), '{property}'=>$name)));
		else
			throw new CException(Hii::t('Hii','Property "{class}.{property}" is not defined.',
				array('{class}'=>get_class($this), '{property}'=>$name)));
	}

	public function attachBehaviors($behaviors)
	{
		foreach($behaviors as $name=>$behavior)
			$this->attachBehavior($name,$behavior);
	}

	/**
	 * Detaches all behaviors from the component.
	 */
	public function detachBehaviors()
	{
		if($this->_m!==null)
		{
			foreach($this->_m as $name=>$behavior)
				$this->detachBehavior($name);
			$this->_m=null;
		}
	}

	/**
	 * Attaches a behavior to this component.
	 * This method will create the behavior object based on the given
	 * configuration. After that, the behavior object will be initialized
	 * by calling its {@link IBehavior::attach} method.
	 * @param string $name the behavior's name. It should uniquely identify this behavior.
	 * @param mixed $behavior the behavior configuration. This is passed as the first
	 * parameter to {@link YiiBase::createComponent} to create the behavior object.
	 * You can also pass an already created behavior instance (the new behavior will replace an already created
	 * behavior with the same name, if it exists).
	 * @return IBehavior the behavior object
	 */
	public function attachBehavior($name,$behavior)
	{
		if(!($behavior instanceof IBehavior))
			$behavior=Yii::createComponent($behavior);
		$behavior->setEnabled(true);
		$behavior->attach($this);
		return $this->_m[$name]=$behavior;
	}

	/**
	 * Determines whether an event is defined.
	 * An event is defined if the class has a method named like 'onXXX'.
	 * Note, event name is case-insensitive.
	 * @param string $name the event name
	 * @return boolean whether an event is defined
	 */
	public function hasEvent($name)
	{
		return !strncasecmp($name,'on',2) && method_exists($this,$name);
	}

	/**
	 * Checks whether the named event has attached handlers.
	 * @param string $name the event name
	 * @return boolean whether an event has been attached one or several handlers
	 */
	public function hasEventHandler($name)
	{
		$name=strtolower($name);
		//检查event有没有对应的event handler
		return isset($this->_e[$name]) && $this->_e[$name]->getCount()>0;
	}

	/**
	 * Determines whether a property is defined.
	 * A property is defined if there is a getter or setter method
	 * defined in the class. Note, property names are case-insensitive.
	 * @param string $name the property name
	 * @return boolean whether the property is defined
	 * @see canGetProperty
	 * @see canSetProperty
	 */
	public function hasProperty($name)
	{
		return method_exists($this,'get'.$name) || method_exists($this,'set'.$name);
	}

	/**
	 * Determines whether a property can be read.
	 * A property can be read if the class has a getter method
	 * for the property name. Note, property name is case-insensitive.
	 * @param string $name the property name
	 * @return boolean whether the property can be read
	 * @see canSetProperty
	 */
	public function canGetProperty($name)
	{
		return method_exists($this,'get'.$name);
	}

	/**
	 * Determines whether a property can be set.
	 * A property can be written if the class has a setter method
	 * for the property name. Note, property name is case-insensitive.
	 * @param string $name the property name
	 * @return boolean whether the property can be written
	 * @see canGetProperty
	 */
	public function canSetProperty($name)
	{
		return method_exists($this,'set'.$name);
	}

	/**
	 * 获取被添加到event上的event handler
	 * Returns the list of attached event handlers for an event.
	 * @param string $name the event name
	 * @return CList list of attached event handlers for the event
	 * @throws CException if the event is not defined
	 */
	public function getEventHandlers($name)
	{
		if($this->hasEvent($name))
		{
			$name=strtolower($name);
			if(!isset($this->_e[$name]))
				$this->_e[$name]=new CList;
			return $this->_e[$name];
		}
		else
			throw new CException(Hii::t('hii','Event "{class}.{event}" is not defined.',
				array('{class}'=>get_class($this), '{event}'=>$name)));
	}

	/**
	 * 执行event
	 * 这个方法会调用被附加在event上的event handlers
	 * @param string $name the event name
	 * @param CEvent $event the event parameter
	 * @throws CException if the event is undefined or an event handler is invalid.
	 */
	public function raiseEvent($name,$event)
	{
		$name=strtolower($name);
		/**
		 * $_e的结构
		 * array
		 * [
		 *    'event' =>  array[
		 *    	 'function name',
		 *    	 array(object, 'function name')
		 *    ]
		 * ]
		 */
		if(isset($this->_e[$name]))
		{
			foreach($this->_e[$name] as $handler)
			{
				if(is_string($handler))
					call_user_func($handler,$event);
				elseif(is_callable($handler,true))
				{
					if(is_array($handler))
					{
						// an array: 0 - object, 1 - method name
						list($object,$method)=$handler;
						if(is_string($object))	// static method call 调用静态方法 array('class name', 'function name')
							call_user_func($handler,$event);
						elseif(method_exists($object,$method))
							$object->$method($event);
						else
							throw new CException(Hii::t('Hii','Event "{class}.{event}" is attached with an invalid handler "{handler}".',
								array('{class}'=>get_class($this), '{event}'=>$name, '{handler}'=>$handler[1])));
					}
					else // PHP 5.3: anonymous function
						call_user_func($handler,$event); //匿名函数
				}
				else
					throw new CException(Hii::t('Hii','Event "{class}.{event}" is attached with an invalid handler "{handler}".',
						array('{class}'=>get_class($this), '{event}'=>$name, '{handler}'=>gettype($handler))));
				// stop further handling if param.handled is set true
				//检查event是否已经被处理好了，处理完毕以后就不继续遍历event hanlder继续处理了
				if(($event instanceof CEvent) && $event->handled)
					return;
			}
		}
		elseif(HII_DEBUG && !$this->hasEvent($name))
			throw new CException(Hii::t('Hii','Event "{class}.{event}" is not defined.',
				array('{class}'=>get_class($this), '{event}'=>$name)));
	}
}

/**
 * CEvent is the base class for all event classes.
 *
 * It encapsulates the parameters associated with an event.
 * The {@link sender} property describes who raises the event.
 * And the {@link handled} property indicates if the event is handled.
 * If an event handler sets {@link handled} to true, those handlers
 * that are not invoked yet will not be invoked anymore.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.base
 * @since 1.0
 */
class CEvent extends CComponent
{
	/**
	 * @var object the sender of this event
	 */
	public $sender;
	/**
	 * @var boolean whether the event is handled. Defaults to false.
	 * When a handler sets this true, the rest of the uninvoked event handlers will not be invoked anymore.
	 */
	public $handled=false;
	/**
	 * @var mixed additional event parameters.
	 * @since 1.1.7
	 */
	public $params;

	/**
	 * Constructor.
	 * @param mixed $sender sender of the event
	 * @param mixed $params additional parameters for the event
	 */
	public function __construct($sender=null,$params=null)
	{
		$this->sender=$sender;
		$this->params=$params;
	}
}