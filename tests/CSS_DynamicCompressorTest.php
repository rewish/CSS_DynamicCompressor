<?php
require_once 'PHPUnit/Framework.php';
require_once dirname(dirname(__FILE__)) . '/src/CSS_DynamicCompressor.php';

class CSS_DynamicCompressorTest extends PHPUnit_Framework_TestCase
{
	protected
		$object,
		$expDirectory,
		$cssFiles = array('a.css', 'b.css', 'c.css', 'd.css', 'e.css'),
		$cache = 'cache.css';

	public function setUp() {
		$this->object = new CSS_DynamicCompressor_Mock;
		$this->object->setDirectory(dirname(__FILE__) . '/css');
		$this->expDirectory = dirname(__FILE__) . '/exp';
	}

	public function tearDown() {
	}

	public function testGetInstance() {
		$this->assertType('object', CSS_DynamicCompressor_Mock::getInstance());
	}

	public function testSetCharset() {
		$charset = 'Shift_JIS';
		$this->assertSame($this->object->setCharset($charset), $this->object);
		$this->assertSame($this->object->_charset, $charset);
	}

	public function testSetTarget() {
		$target = 'filename.css';
		$this->assertSame($this->object->setTarget($target), $this->object);
		$this->assertSame($this->object->_target, $target);
	}

	public function testSetCache() {
		$cache = 'cachename.css';
		$this->assertSame($this->object->setCache($cache), $this->object);
		$this->assertSame($this->object->_cache, $cache);
	}

	public function testSetExpireDay() {
		$this->assertSame($this->object->setExpireDay(1), $this->object);
		$this->assertSame($this->object->_expires, time() + 86400);
	}

	public function testSetExpires() {
		$expires = time() + 9000;
		$this->assertSame($this->object->setExpires($expires), $this->object);
		$this->assertSame($this->object->_expires, $expires);
	}

	public function testSetDirectory() {
		$this->assertSame($this->object->setDirectory(), $this->object);
		$this->assertSame($this->object->_directory, getcwd() . DIRECTORY_SEPARATOR);

		$directory = dirname(__FILE__);
		$this->assertSame($this->object->setDirectory($directory), $this->object);
		$this->assertSame($this->object->_directory, $directory . DIRECTORY_SEPARATOR);

		try {
			$this->object->setDirectory('./does_not_exists_directory');
			$this->fail();
		} catch (CSS_DynamicCompressor_Exception $e) {
			// Passed
		}
	}

	public function testSetCSSFiles() {
		$files = array('file1.css', 'file2.css');
		$this->assertSame($this->object->setCSSFiles($files), $this->object);
		$this->assertSame($this->object->_cssFiles, $files);
	}

	public function testSetCommentOptions()
	{
		$options = array(
			'file_list' => false,
			'copyright' => false
		);
		$this->object->setCommentOptions($options);
		$this->assertSame($options, $this->object->_commentOptions);
	}

	public function testSetBaseUrl() {
		$url = 'http://localhost/css';
		$this->assertSame($this->object->setBaseUrl($url), $this->object);
		$this->assertSame($this->object->_baseUrl, "$url/");

		$url = 'localhost/css';
		$this->assertSame($this->object->setBaseUrl($url), $this->object);
		$this->assertSame($this->object->_baseUrl, "http://$url/");

		$_SERVER['HTTPS'] = true;
		$this->assertSame($this->object->setBaseUrl($url), $this->object);
		$this->assertSame($this->object->_baseUrl, "https://$url/");
	}

	public function testSetCSS3Fix() {
		$this->assertSame($this->object->setCSS3Fix(true), $this->object);
		$this->assertFalse($this->object->setCSS3Fix(false)->_css3Fix);
		$this->assertTrue($this->object->setCSS3Fix(true)->_css3Fix);
	}

	public function testGetCSS() {
		$this->assertSame($this->object->_css, $this->object->getCSS());
	}

	/**
	 * @todo Implement testCompression().
	 */
	public function testCompression() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
				'This test has not been implemented yet.'
		);
	}

	public function testConcatenation() {
		$this->object->concatenation();
		$this->assertSame(
			$this->object->_css,
			file_get_contents("{$this->expDirectory}/concatnation.css")
		);
	}

	public function testExtractCSSFiles() {
		$this->object->extractCSSFiles();
		$this->assertSame($this->object->_cssFiles, $this->cssFiles);
	}

	public function testCompress() {
		try {
			$this->object->compress();
			$this->fail();
		} catch(CSS_DynamicCompressor_Exception $e) {
			// Passed
		}

		$this->object->concatenation()->compress();
		$this->assertSame(
			$this->object->_css,
			file_get_contents("{$this->expDirectory}/compress.css")
		);
	}

	public function testFixCSS3() {
		try {
			$this->object->fixCSS3();
			$this->fail();
		} catch(CSS_DynamicCompressor_Exception $e) {
			// Passed
		}

		$this->object->concatenation()->compress()->fixCSS3();
		$this->assertSame(
			$this->object->_css,
			file_get_contents("{$this->expDirectory}/fixcss3.css")
		);
	}

	public function testIsModified() {
		$this->object->_debug = true;
		$this->assertTrue($this->object->isModified());
		$this->object->_debug = false;

		$this->object->setCache($this->cache);
		$directory = $this->object->_directory;
		$this->cssFiles[] = $this->object->_target;
		$time = time();

		touch($directory . $this->cache, $time);

		foreach ($this->cssFiles as $file) {
			// Last modified
			$mtime = filemtime($directory . $file);
			// Is modified
			touch($directory . $file, $time + 1);
			$this->assertTrue($this->object->isModified());
			// Is not modified
			touch($directory . $file, $time - 1);
			$this->assertFalse($this->object->isModified());
			// Push back
			touch($directory . $file, $mtime);
		}

		array_pop($this->cssFiles);
		unlink($directory . $this->cache);
	}

	/**
	 * @todo Implement testAddHeader().
	 */
	public function testAddHeader() {
		$this->markTestIncomplete();
	}

	/**
	 * @todo Implement testAddCharset().
	 */
	public function testAddCharset() {
		$this->markTestIncomplete();
	}

	/**
	 * @todo Implement testAddComment().
	 */
	public function testAddComment() {
		$this->markTestIncomplete();
	}

	public function test_readFile() {
		try {
			$this->object->_readFile('./');
			$this->fail();
		} catch(CSS_DynamicCompressor_Exception $e) {
			// Passed
		}
		try {
			$this->object->_readFile('./does_not_exists');
			$this->fail();
		} catch(CSS_DynamicCompressor_Exception $e) {
			// Passed
		}
	}
}

class CSS_DynamicCompressor_Mock extends CSS_DynamicCompressor
{
	public function __get($name)
	{
		return $this->{$name};
	}

	public function __set($name, $value)
	{
		$this->{$name} = $value;
	}

	public function _readFile($file)
	{
		return parent::_readFile($file);
	}

	public function _writeFile($file, $data)
	{
		return parent::_writeFile($file, $data);
	}

	public function _addFileList()
	{
		return parent::_addFileList();
	}

	public function _addCopyright()
	{
		return parent::_addCopyright();
	}
}
