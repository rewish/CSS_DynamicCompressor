<?php
require dirname(__FILE__) . '/CSS_DynamicCompressor.php';

try {
	CSS_DynamicCompressor::getInstance()

		//->setCharset('UTF-8')
		//->setTarget('import.css')
		//->setCache('_cache.css')
		//->setExpireDay(30)
		//->setDirectory('/path/to/css/')
		//->setCSSFiles(array('hoge.css', 'fuga.css', 'piyo.css'))
		->setBaseUrl()

		//->compression();
		->display();

} catch(Exception $e) {
	echo $e->getMessage();
}
