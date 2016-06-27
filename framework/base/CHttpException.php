<?php
/**
 *   这个类用来处理由用户导致的错误
 *   
 */
class CHttpException extends CHttpException
{
	//错误编码 比如 404,500等
	public $statusCode;

	public function __construct($status, $message = null, $code = 0)
	{
		$this->statusCode = $status;
		//调用 php内置异常类  Exception 的构造函数 
		parent::__construct($message, $code);
	}
}