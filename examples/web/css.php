<?php
require dirname(__FILE__) . '/CSS_DynamicCompressor.php';

try {
	CSS_DynamicCompressor::getInstance()

//		->setCharset('UTF-8')
//		->setTarget('import.css')
//		->setCache('_cache.css')
//		->setExpireDay(30)
//		->setDirectory('/path/to/css/')
//		->setCSSFiles(array('hoge', 'fuga', 'piyo'))
//		->setCSS3Fix(true)
//		->setBaseUrl('http://example.com/')
//		->setCommentOptions(array(
//			'file_list' => fasle,
//			'copyright' => false
//		))

		//->compression();
		->display();

} catch(Exception $e) {
	echo $e->getMessage();
}
