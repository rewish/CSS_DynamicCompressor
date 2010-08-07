<?php
/**
 * CSS_DynamicCompressor
 *
 * PHP versions 5
 *
 * @version      0.4.2
 * @author       rew <rewish.org@gmail.com>
 * @copyright    (c) 2010 rewish
 * @link         http://rewish.org/php_mysql/css_dynamic_compressor
 * @license      http://rewish.org/license/mit The MIT License
 */
class CSS_DynamicCompressor
{
	const
		VERSION = '0.4.2',
		STRING_DOUBLE = '__CSSDC_STRING_DOUBLE__',
		STRING_SINGLE = '__CSSDC_STRING_SINGLE__';

	protected
		$_directory,
		$_cssFiles,
		$_css,
		$_lastModified,
		$_comment = array(),
		$_baseUrl = null,
		$_charset = 'UTF-8',
		$_target = 'import.css',
		$_cache = '_cache.css',
		$_expireDay = 30,
		$_css3Fix = false,
		$_css3Fixes = array(
			// border-radius
			'/border-([a-z]+)?(-)?([a-z]+)?(-)?radius:([^;\}]+)/' => array(
				'border-$1$2$3$4radius:$5',
				'-webkit-border-$1$2$3$4radius:$5',
				'-moz-border-radius$2$1$3:$5'
			),
			// other properties
			'/(transform|box-|border-image|column-)([\-a-z]+)?:([^;\}]+)/' => array(
				'$1$2:$3',
				'-webkit-$1$2:$3',
				'-moz-$1$2:$3'
			)
		);

	public function __construct() {}

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

	public function setCSS3Fix($use)
	{
		$this->_css3Fix = $use;
		return $this;
	}

	public function getCSS()
	{
		return $this->_css;
	}

	public function display()
	{
		$this->compression();
		$this->addHeader();
		echo $this->_css;
	}

	public function compression()
	{
		if (false && !$this->isModified()) {
			$this->_css = $this->_readFile($this->_cache);
			return $this;
		}
		$this->concatenation();
		$this->compress();
		if ($this->_css3Fix) {
			$this->fixCSS3();
		}
		$this->addComment();
		$this->addCharset();
		$this->_writeFile($this->_cache, $this->_css);
		return $this;
	}

	public function concatenation()
	{
		if (empty($this->_cssFiles)) {
			$this->extractCSSFiles();
		}
		foreach ($this->_cssFiles as $file) {
			$css = $this->_readFile($file);
			$this->_css.= preg_replace('/@charset.+/', '', $css);
		}
		return $this;
	}

	public function extractCSSFiles()
	{
		$target = $this->_readFile($this->_target);
		preg_match_all('/@import\s+(url\()?["\']?([^"\'\);]+)/',
			$target, $files, PREG_PATTERN_ORDER);
		$this->_cssFiles = $files[2];
		return $this;
	}

	public function compress()
	{
		if (!$this->_css) {
			throw new Exception;
		}

		// Comment
		$this->_css = preg_replace('_/\*.*?\*/_s', '', $this->_css);

		// Double quotation
		$pattern = '/("[^"]*?")/s';
		preg_match_all($pattern, $this->_css, $stringDouble);
		$this->_css = preg_replace($pattern, self::STRING_DOUBLE, $this->_css);
		// Single quotation
		$pattern = '/(\'[^\']*?\')/s';
		preg_match_all($pattern, $this->_css, $stringSingle);
		$this->_css = preg_replace($pattern, self::STRING_SINGLE, $this->_css);

		// Compress
		$this->_css = preg_replace('_([\t\r\n]+| {2,})_s', '', $this->_css);
		$this->_css = str_replace(array(': ', ' :', ' {', ';}', ', '),
		                          array(':' , ':' , '{' , '}' , ','),
		                          trim($this->_css));
		$this->_css = preg_replace('/[^\}]+?\{\}/', '', $this->_css);

		// Double quotation
		foreach ($stringDouble[1] as $str) {
			$this->_css = preg_replace('/'. self::STRING_DOUBLE .'/', $str, $this->_css, 1);
		}
		// Single quotation
		foreach ($stringSingle[1] as $str) {
			$this->_css = preg_replace('/'. self::STRING_SINGLE .'/', $str, $this->_css, 1);
		}

		return $this;
	}

	public function fixCSS3()
	{
		if (!$this->_css) {
			throw new Exception;
		}
		foreach ($this->_css3Fixes as $exp => $fixes) {
			$this->_css = preg_replace($exp, implode(';', $fixes), $this->_css);
		}
		return $this;
	}

	public function isModified()
	{
		$cachePath = realpath($this->_directory . $this->_cache);
		if (!$cachePath || !file_exists($cachePath) || !empty($_GET['no_cache'])) {
			return true;
		}
		$modified = false;
		$this->_lastModified = filemtime($cachePath);
		if (empty($this->_cssFiles)) {
			$this->extractCSSFiles();
		}
		$this->_cssFiles[] = $this->_target;
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
		array_pop($this->_cssFiles);
		return $modified;
	}

	public function addHeader()
	{
		header('Content-Type: text/css');
		header('Last-Modified: '. gmdate('D, d M Y H:i:s', $this->_lastModified) .' GMT');
		header('Expires: '. gmdate('D, d M Y H:i:s', strtotime("+{$this->_expireDay} day")) .' GMT');
		ob_start('ob_gzhandler');
		return $this;
	}

	public function addCharset($charset = '')
	{
		if (!empty($charset)) $this->_charset = $charset;
		$this->_css = "@charset \"$this->_charset\";\n$this->_css";
		return $this;
	}

	public function addComment(Array $comment = array())
	{
		$this->_comment = $comment;
		if ($this->_baseUrl) {
			$this->_addFileList();
		}
		$this->_addCopyright();
		array_unshift($this->_comment, '/**');
		$this->_comment[] = ' */';
		$this->_comment[] = '';
		$this->_css = implode("\n", $this->_comment) . $this->_css;
		return $this;
	}

	protected function _addFileList()
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

	protected function _addCopyright()
	{
		$this->_comment[] = ' * Powered by ' . __CLASS__ . ' - v' . self::VERSION;
		$this->_comment[] = ' * http://rewish.org/php_mysql/css_dynamic_compressor';
		$this->_comment[] = ' * (c) 2010 rew <rewish.org@gmail.com>';
	}

	protected function _readFile($file)
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

	protected function _writeFile($file, $data)
	{
		return file_put_contents($this->_directory . $file, $data, LOCK_EX);
	}
}
