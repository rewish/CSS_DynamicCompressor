<?php
/**
 * CSS_DynamicCompressor
 *
 * PHP versions 5
 *
 * @version      0.1
 * @author       rew <rewish.org@gmail.com>
 * @copyright    (c) 2010 rewish
 * @link         http://rewish.org/php_mysql/css_dynamic_compressor
 * @license      http://rewish.org/license/mit The MIT License
 */
class CSS_DynamicCompressor
{
	private $_directory, $_cssFiles, $_css, $_lastModified;
	private $_comment = array();
	private $_baseUrl = null;
	private $_charset = 'UTF-8';
	private $_target  = 'import.css';
	private $_cache   = '_cache.css';
	private $_expireDay = 30;

	private function __construct() {}

	public static function getInstance()
	{
		static $obj = null;
		return $obj ? $obj : $obj = new self;
	}

	public function setCharset($charset)
	{
		$this->_charset = $charset;
		return $this;
	}

	public function setTarget($target)
	{
		$this->_target = $target;
		return $this;
	}

	public function setCache($cache)
	{
		$this->_cache = $cache;
		return $this;
	}

	public function setExpireDay($expireDay)
	{
		$this->_expireDay = $expireDay;
		return $this;
	}

	public function setDirectory($directory = null)
	{
		if (!$directory) $directory = getcwd();
		$this->_directory = realpath($directory);
		if (!$this->_directory) throw new Exception;
		$this->_directory .= DIRECTORY_SEPARATOR;
		return $this;
	}

	public function setCSSFiles(Array $cssFiles = array())
	{
		$this->_cssFiles = $cssFiles;
		return $this;
	}

	public function setBaseUrl($url = null)
	{
		if (!$url) {
			$url = $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
		}
		if (strpos($url, 'http') === false) {
			$scheme = !empty($_SERVER['HTTPS']) ? 'https://' : 'http://';
			$url = "{$scheme}{$url}";
		}
		$this->_baseUrl = preg_replace('_/*$_', '', $url) . '/';
		return $this;
	}

	public function display()
	{
		$this->compression();
		$this->_addHeader();
		echo $this->_css;
	}

	public function compression()
	{
		if (!$this->_isModified()) {
			$this->_css = $this->_readFile($this->_cache);
			return;
		}
		$this->_concatenation();
		$this->_compression();
		$this->_addComment();
		$this->_addCharset();
		$this->_writeFile($this->_cache, $this->_css);
	}

	private function _concatenation()
	{
		if (empty($this->_cssFiles)) {
			$this->_extractCSSFiles();
		}
		foreach ($this->_cssFiles as $file) {
			$css = $this->_readFile($file);
			$this->_css.= preg_replace('/@charset.+/', '', $css);
		}
	}

	private function _extractCSSFiles()
	{
		$target = $this->_readFile($this->_target);
		preg_match_all('/@import\s+(url\()?["\']?([^"\';]+)/',
			$target, $files, PREG_PATTERN_ORDER);
		$this->_cssFiles = $files[2];
	}

	private function _compression()
	{
		$css = preg_replace('_(/\*.*?\*/|\s{2,}|[\t\r\n]+)_s', '', $this->_css);
		$css = str_replace(array(': ', ' :', ' {', ';}', ', '),
		                   array(':' , ':' , '{' , '}' , ','),
		                   trim($css));
		$css = preg_replace('/[^\}]+?\{\}/', '', $css);
		$this->_css = $css;
	}

	private function _readFile($file)
	{
		if (!$this->_directory) {
			$this->setDirectory();
		}
		if (strpos($file, 'http://') === 0 || strpos($file, 'https://') === 0) {
			return file_get_contents($file);
		}
		$path = $this->_directory . $file;
		if (!file_exists($path)) {
			throw new Exception("'{$this->_directory}{$file}' is not found");
		}
		if (!is_file($path)) {
			throw new Exception("'$path' is not file");
		}
		return file_get_contents($path);
	}

	private function _writeFile($file, $data)
	{
		return file_put_contents($this->_directory . $file, $data, LOCK_EX);
	}

	private function _isModified()
	{
		$cachePath = realpath($this->_directory . $this->_cache);
		if (!$cachePath || !file_exists($cachePath)) {
			return true;
		}
		$modified = false;
		$this->_lastModified = filemtime($cachePath);
		if (empty($this->_cssFiles)) {
			$this->_extractCSSFiles();
			$this->_cssFiles[] = $this->_target;
		}
		foreach ($this->_cssFiles as $file) {
			$filePath = $this->_directory . $file;
			if (!file_exists($filePath)) {
				continue;
			}
			$mtime = filemtime($filePath);
			if ($this->_lastModified < $mtime) {
				$modified = true;
				$this->_lastModified = $mtime;
			}
		}
		return $modified;
	}

	private function _addHeader()
	{
		header('Content-Type: text/css');
		header('Last-Modified: '. gmdate('D, d M Y H:i:s', $this->_lastModified) .' GMT');
		header('Expires: '. gmdate('D, d M Y H:i:s', strtotime("+{$this->_expireDay} day")) .' GMT');
		ob_start('ob_gzhandler');
	}

	private function _addCharset()
	{
		$this->_css = "@charset \"$this->_charset\";\n$this->_css";
	}

	private function _addComment()
	{
		$this->_comment[] = '/**';
		if ($this->_baseUrl) {
			$this->_addFileList();
		}
		$this->_addCopyright();
		$this->_comment[] = ' */';
		$this->_comment[] = '';
		$this->_css = implode("\n", $this->_comment) . $this->_css;
	}

	private function _addFileList()
	{
		$this->_comment[] = ' * [File list]';
		foreach ($this->_cssFiles as $file) {
			if (strpos($file, 'http://') === 0 || strpos($file, 'https://') === 0) {
				$this->_comment[] = " * - {$file}";
			} else {
				$this->_comment[] = " * - {$this->_baseUrl}{$file}";
			}
		}
		$this->_comment[] = ' *';
	}

	private function _addCopyright()
	{
		// Jesus...would you really delete this?
		// Oh my god... X(
		$this->_comment[] = ' * Powered by ' . __CLASS__;
		$this->_comment[] = ' * http://rewish.org/php_mysql/css_dynamic_compressor';
		$this->_comment[] = ' * (c) 2010 rew <rewish.org@gmail.com>';
	}
}