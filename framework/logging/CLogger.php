<?php
/**
 *  CLogger 用来将日志记录在内存
 */
class CLogger extends CComponent
{
	const LEVEL_TRACE='trace';
	const LEVEL_WARNING='warning';
	const LEVEL_ERROR='error';
	const LEVEL_INFO='info';
	const LEVEL_PROFILE='profile';
	/**
	 * @var boolean this property will be passed as the parameter to {@link flush()} when it is
	 * called in {@link log()} due to the limit of {@link autoFlush} being reached.
	 * By default, this property is false, meaning the filtered messages are still kept in the memory
	 * by each log route after calling {@link flush()}. If this is true, the filtered messages
	 * will be written to the actual medium each time {@link flush()} is called within {@link log()}.
	 * @since 1.1.8
	 */
	public $autoDump=false;
	
	//需要清空存储在内存中的日志最大条数
	public $autoFlush=10000;
	/**
	*  标记目前是在处理日志还是需要继续接受日志
	*/
	private $_processing=false;
	/**
	 * 在内存中临时存放日志，根据autoFlush等配置项来控制什么时候清空
	 * 结构如下：
	 * array(
	 *   [0] => message (string)
	 *   [1] => level (string)
	 *   [2] => category (string)
	 *   [3] => timestamp (float, obtained by microtime(true));.
	 */
	private $_logs=array();
	//统计寸粗的日志数量
	private $_logCount = 0;

	public function log($message,$level='info',$category='application')
	{
		//把日志存储在内存中
		$this->_logs[]=array($message,$level,$category,microtime(true));
		//记录内存中的日志条数
		$this->_logCount++;
		//自动从内存中清理日志功能开启的时候调用
		if($this->autoFlush>0 && $this->_logCount>=$this->autoFlush && !$this->_processing)
		{
			//标记开始处理
			$this->_processing=true;
			//处理并清空内存中的日志
			$this->flush($this->autoDump);
			//结束处理，开始继续接受新的日志
			$this->_processing=false;
		}
	}

	/**
	 * Removes all recorded messages from the memory.
	 * This method will raise an {@link onFlush} event.
	 * The attached event handlers can process the log messages before they are removed.
	 * @param boolean $dumpLogs whether to process the logs immediately as they are passed to log route
	 * @since 1.1.0
	 */
	public function flush($dumpLogs=false)
	{
		$this->onFlush(new CEvent($this, array('dumpLogs'=>$dumpLogs)));
		//重置计数和存储日志的数组
		$this->_logs=array();
		$this->_logCount=0;
	}

	/**
	 * Raises an <code>onFlush</code> event.
	 * @param CEvent $event the event parameter
	 * @since 1.1.0
	 */
	public function onFlush($event)
	{
		$this->raiseEvent('onFlush', $event);
	}
}