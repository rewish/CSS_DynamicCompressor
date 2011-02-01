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
		$this->assertEquals($this->object->setCharset($charset), $this->object);
		$this->assertEquals($this->object->_charset, $charset);
	}

	public function testSetTarget() {
		$target = 'filename.css';
		$this->assertEquals($this->object->setTarget($target), $this->object);
		$this->assertEquals($this->object->_target, $target);
	}

	public function testSetCache() {
		$cache = 'cachename.css';
		$this->assertEquals($this->object->setCache($cache), $this->object);
		$this->assertEquals($this->object->_cache, $cache);
	}

	public function testSetExpireDay() {
		$expireDay = 9000;
		$this->assertEquals($this->object->setExpireDay($expireDay), $this->object);
		$this->assertEquals($this->object->_expireDay, $expireDay);
	}

	public function testSetDirectory() {
		$this->assertEquals($this->object->setDirectory(), $this->object);
		$this->assertEquals($this->object->_directory, getcwd() . DIRECTORY_SEPARATOR);

		$directory = dirname(__FILE__);
		$this->assertEquals($this->object->setDirectory($directory), $this->object);
		$this->assertEquals($this->object->_directory, $directory . DIRECTORY_SEPARATOR);
	}

	public function testSetCSSFiles() {
		$files = array('file1.css', 'file2.css');
		$this->assertEquals($this->object->setCSSFiles($files), $this->object);
		$this->assertEquals($this->object->_cssFiles, $files);
	}

	public function testSetBaseUrl() {
		$url = 'http://localhost/css';
		$this->assertEquals($this->object->setBaseUrl($url), $this->object);
		$this->assertEquals($this->object->_baseUrl, "$url/");

		$url = 'localhost/css';
		$this->assertEquals($this->object->setBaseUrl($url), $this->object);
		$this->assertEquals($this->object->_baseUrl, "http://$url/");

		$_SERVER['HTTPS'] = true;
		$this->assertEquals($this->object->setBaseUrl($url), $this->object);
		$this->assertEquals($this->object->_baseUrl, "https://$url/");
	}

	public function testSetCSS3Fix() {
		$this->assertEquals($this->object->setCSS3Fix(true), $this->object);
		$this->assertFalse($this->object->setCSS3Fix(false)->_css3Fix);
		$this->assertTrue($this->object->setCSS3Fix(true)->_css3Fix);
	}

	public function testGetCSS() {
		$this->assertEquals($this->object->_css, $this->object->getCSS());
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
		$this->assertEquals(
			$this->object->_css,
			file_get_contents("{$this->expDirectory}/concatnation.css")
		);
	}

	public function testExtractCSSFiles() {
		$this->object->extractCSSFiles();
		$this->assertEquals($this->object->_cssFiles, $this->cssFiles);
	}

	public function testCompress() {
		$this->object->concatenation()->compress();
		$this->assertEquals(
			$this->object->_css,
			file_get_contents("{$this->expDirectory}/compress.css")
		);
	}

	public function testFixCSS3() {
		$this->object->concatenation()->compress()->fixCSS3();
		$this->assertEquals(
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
