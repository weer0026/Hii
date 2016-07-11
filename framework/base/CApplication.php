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
		//存储实例
		Hii::setApplication($this);
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
		//定义在CModule里面，在init之前执行，可以在初始化之前做一些自定义处理
		$this->preinit();
		//设置错误和异常处理函数
		$this->initSystemHandlers();
		//注册框架核心组件
		$this->registerCoreComponents();
		//使用CComponent::__set方法设置配置
		$this->configure($config);
		//附加行为类
		$this->attachBehaviors($this->behaviors);
		//加载在配置文件中preload定义的组件
		$this->preloadComponents();
		//调用init 
		$this->init();
	}

	//初始化错误和异常处理函数
	protected function initSystemHandlers()
	{
		if (HII_ENABLE_EXCEPTION_HANDLER) {
			set_exception_handler([$this, 'handleException']);
		}
		if (HII_ENABLE_ERROR_HANDLER) {
			set_error_handler([$this,'handleError'], error_reporting());
		}
	}

	/**
	 * 处理错误event
	 * @param  [type]
	 * @return [type]
	 */
	public function onError($event)
	{
		$this->raiseEvent('onError', $event);
	}

	/**
	 *  自定义异常处理函数
	 */
	public function handleException($exception)
	{
		//还原错误和异常处理函数
		restore_error_handler();
		restore_exception_handler();
		//异常的类型名称
		$category = 'exception.'.get_class($exception);
		if ($exception instanceof CHttpException) {
			$category .= '.'.$exception->statusCode;
		}
		$message = $exception->__toString();
		//如果有请求地址附加上
		if (isset($_SERVER['REQUEST_URI'])) {
			$message .= "\nREQUEST_URI=".$_SERVER['REQUEST_URI'];
		}
		//如果有来源页的地址就附加上
		if (isset($_SERVER['HTTP_REFERER'])) {
			$message .= "\nHTTP_REFERER=".$_SERVER['HTTP_REFERER'];
		}
		$message .= "\n----";
		//调用log函数进行记录
		Hii::log($message, CLogger::LEVEL_ERROR, $category);

		try
		{
			$event=new CExceptionEvent($this,$exception);
			$this->onException($event);
			if(!$event->handled)
			{
				// try an error handler
				if(($handler=$this->getErrorHandler())!==null)
					$handler->handle($event);
				else
					$this->displayException($exception);
			}
		}
		catch(Exception $e)
		{
			$this->displayException($e);
		}

		try
		{
			$this->end(1);
		}
		catch(Exception $e)
		{
			// use the most primitive way to log error
			$msg = get_class($e).': '.$e->getMessage().' ('.$e->getFile().':'.$e->getLine().")\n";
			$msg .= $e->getTraceAsString()."\n";
			$msg .= "Previous exception:\n";
			$msg .= get_class($exception).': '.$exception->getMessage().' ('.$exception->getFile().':'.$exception->getLine().")\n";
			$msg .= $exception->getTraceAsString()."\n";
			$msg .= '$_SERVER='.var_export($_SERVER,true);
			error_log($msg);
			exit(1);
		}
	}

	/**
	 * Raised when an uncaught PHP exception occurs.
	 *
	 * An event handler can set the {@link CExceptionEvent::handled handled}
	 * property of the event parameter to be true to indicate no further error
	 * handling is needed. Otherwise, the {@link getErrorHandler errorHandler}
	 * application component will continue processing the error.
	 *
	 * @param CExceptionEvent $event event parameter
	 */
	public function onException($event)
	{
		$this->raiseEvent('onException',$event);
	}

	/**
	 *  自定义的错误处理函数
	 */
	public function handleError($code,$message,$file,$line)
	{
		if($code & error_reporting())
		{
			// disable error capturing to avoid recursive errors
			restore_error_handler();
			restore_exception_handler();

			$log="$message ($file:$line)\nStack trace:\n";
			$trace=debug_backtrace();
			// skip the first 3 stacks as they do not tell the error position
			if(count($trace)>3)
				$trace=array_slice($trace,3);
			foreach($trace as $i=>$t)
			{
				if(!isset($t['file']))
					$t['file']='unknown';
				if(!isset($t['line']))
					$t['line']=0;
				if(!isset($t['function']))
					$t['function']='unknown';
				$log.="#$i {$t['file']}({$t['line']}): ";
				if(isset($t['object']) && is_object($t['object']))
					$log.=get_class($t['object']).'->';
				$log.="{$t['function']}()\n";
			}
			if(isset($_SERVER['REQUEST_URI']))
				$log.='REQUEST_URI='.$_SERVER['REQUEST_URI'];
			Hii::log($log,CLogger::LEVEL_ERROR,'php');

			try
			{
				Hii::import('CErrorEvent',true);
				$event=new CErrorEvent($this,$code,$message,$file,$line);
				$this->onError($event);
				if(!$event->handled)
				{
					// try an error handler
					if(($handler=$this->getErrorHandler())!==null)
						$handler->handle($event);
					else
						$this->displayError($code,$message,$file,$line);
				}
			}
			catch(Exception $e)
			{
				$this->displayException($e);
			}

			try
			{
				$this->end(1);
			}
			catch(Exception $e)
			{
				// use the most primitive way to log error
				$msg = get_class($e).': '.$e->getMessage().' ('.$e->getFile().':'.$e->getLine().")\n";
				$msg .= $e->getTraceAsString()."\n";
				$msg .= "Previous error:\n";
				$msg .= $log."\n";
				$msg .= '$_SERVER='.var_export($_SERVER,true);
				error_log($msg);
				exit(1);
			}
		}
	}

	/**
	 * Terminates the application.
	 * This method replaces PHP's exit() function by calling
	 * {@link onEndRequest} before exiting.
	 * @param integer $status exit status (value 0 means normal exit while other values mean abnormal exit).
	 * @param boolean $exit whether to exit the current request. This parameter has been available since version 1.1.5.
	 * It defaults to true, meaning the PHP's exit() function will be called at the end of this method.
	 */
	public function end($status=0,$exit=true)
	{
		if($this->hasEventHandler('onEndRequest'))
			$this->onEndRequest(new CEvent($this));
		if($exit)
			exit($status);
	}

	/**
	 * 返回error handler组件
	 * @return CErrorHandler the error handler application component.
	 */
	public function getErrorHandler()
	{
		return $this->getComponent('errorHandler');
	}

	/**
	 * Displays the uncaught PHP exception.
	 * This method displays the exception in HTML when there is
	 * no active error handler.
	 * @param Exception $exception the uncaught exception
	 */
	public function displayException($exception)
	{
		if(HII_DEBUG)
		{
			echo '<h1>'.get_class($exception)."</h1>\n";
			echo '<p>'.$exception->getMessage().' ('.$exception->getFile().':'.$exception->getLine().')</p>';
			echo '<pre>'.$exception->getTraceAsString().'</pre>';
		}
		else
		{
			echo '<h1>'.get_class($exception)."</h1>\n";
			echo '<p>'.$exception->getMessage().'</p>';
		}
	}

	/**
	 * Displays the captured PHP error.
	 * This method displays the error in HTML when there is
	 * no active error handler.
	 * @param integer $code error code
	 * @param string $message error message
	 * @param string $file error file
	 * @param string $line error line
	 */
	public function displayError($code,$message,$file,$line)
	{
		if(HII_DEBUG)
		{
			echo "<h1>PHP Error [$code]</h1>\n";
			echo "<p>$message ($file:$line)</p>\n";
			echo '<pre>';

			$trace=debug_backtrace();
			// skip the first 3 stacks as they do not tell the error position
			if(count($trace)>3)
				$trace=array_slice($trace,3);
			foreach($trace as $i=>$t)
			{
				if(!isset($t['file']))
					$t['file']='unknown';
				if(!isset($t['line']))
					$t['line']=0;
				if(!isset($t['function']))
					$t['function']='unknown';
				echo "#$i {$t['file']}({$t['line']}): ";
				if(isset($t['object']) && is_object($t['object']))
					echo get_class($t['object']).'->';
				echo "{$t['function']}()\n";
			}

			echo '</pre>';
		}
		else
		{
			echo "<h1>PHP Error [$code]</h1>\n";
			echo "<p>$message</p>\n";
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

	protected function registerCoreComponents()
	{
		$components = [
			'coreMessages'=>array(
				'class'=>'CPhpMessageSource',
				'language'=>'en_us',
				'basePath'=>HII_PATH.DIRECTORY_SEPARATOR.'messages',
			),
			'db'=>array(
				'class'=>'CDbConnection',
			),
			'messages'=>array(
				'class'=>'CPhpMessageSource',
			),
			'errorHandler'=>array(
				'class'=>'CErrorHandler',
			),
			'securityManager'=>array(
				'class'=>'CSecurityManager',
			),
			'statePersister'=>array(
				'class'=>'CStatePersister',
			),
			'urlManager'=>array(
				'class'=>'CUrlManager',
			),
			'request'=>array(
				'class'=>'CHttpRequest',
			),
			'format'=>array(
				'class'=>'CFormatter',
			),
		];
		$this->setComponents($components);
	}
}