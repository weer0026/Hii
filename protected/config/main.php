<?php
return [
	//设定应用目录为当前目录的上级目录
	'basePath' => dirname(__FILE__). DIRECTORY_SEPARATOR . '..',
	//预加载log模块
	'preload' => array('log'),
	'components' => array(
		'log' => array(
			'class' => 'CLoggerRoute',
			'routes' => array(
				[
					'class' => 'CFileLogRoute',
					'levels' => 'error,info'
				]
			)
		)
	)
];