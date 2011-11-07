<?php
/**
 * CSS_DynamicCompressor
 *
 * PHP versions >= 5.2
 *
 * @version      0.4.6
 * @author       Hiroshi Hoaki <rewish.org@gmail.com>
 * @copyright    (c) 2010-2011 rewish
 * @link         http://rewish.org/php_mysql/css_dynamic_compressor
 * @license      http://rewish.org/license/mit The MIT License
 */
class CSS_DynamicCompressor
{
	/**
	 * Version
	 */
	const VERSION = '0.4.6';

	/**
	 * String "TARGET"
	 */
	const STRING_DOUBLE = '__CSSDC_STRING_DOUBLE__';

	/**
	 * String 'TARGET'
	 */
	const STRING_SINGLE = '__CSSDC_STRING_SINGLE__';

	/**
	 * Debug flag
	 * @var boolean
	 */
	protected $_debug = false;

	/**
	 * Base directory
	 * @var string
	 */
	protected $_directory;

	/**
	 * CSS files
	 * @var array
	 */
	protected $_cssFiles;

	/**
	 * CSS content
	 * @var string
	 */
	protected $_css;

	/**
	 * Last modified time
	 * @var integer
	 */
	protected $_lastModified;

	/**
	 * Comment
	 * @var array
	 */
	protected $_comment = array();

	/**
	 * Comment options
	 * @var array
	 */
	protected $_commentOptions = array(
		'file_list' => true,
		'copyright' => true
	);

	/**
	 * Base URL
	 * @var string
	 */
	protected $_baseUrl = null;

	/**
	 * Charset
	 * @var string
	 */
	protected $_charset = 'UTF-8';

	/**
	 * Target file name
	 * @var string
	 */
	protected $_target = 'import.css';

	/**
	 * Cache file name
	 * @var string
	 */
	protected $_cache = '_cache.css';

	/**
	 * Expires day
	 * @var integer
	 */
	protected $_expires = 2592000;

	/**
	 * Fix CSS3 vendor prefixes
	 * @var boolean
	 */
	protected $_css3Fix = false;

	/**
	 * Fix CSS3 rules
	 * @var array
	 */
	protected $_css3Fixes = array(
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

	/**
	 * Constructor
	 */
	public function __construct()
	{
	}

	/**
	 * Get instance
	 *
	 * @staticvar object $obj
	 * @return CSS_DynamicCompressor
	 */
	public static function getInstance()
	{
		static $obj = null;
		return $obj ? $obj : $obj = new self;
	}

	/**
	 * Set debug flag
	 *
	 * @param boolean $debug
	 * @return CSS_DynamicCompressor
	 */
	public function setDebug($debug)
	{
		$this->_debug = $debug;
		return $this;
	}

	/**
	 * Set charset
	 *
	 * @param string $charset
	 * @return CSS_DynamicCompressor
	 */
	public function setCharset($charset)
	{
		$this->_charset = $charset;
		return $this;
	}

	/**
	 * Set target file name
	 *
	 * @param string $target
	 * @return CSS_DynamicCompressor
	 */
	public function setTarget($target)
	{
		$this->_target = $target;
		return $this;
	}

	/**
	 * Set cache file name
	 *
	 * @param string $cache
	 * @return CSS_DynamicCompressor
	 */
	public function setCache($cache)
	{
		$this->_cache = $cache;
		return $this;
	}

	/**
	 * Set expires day
	 *
	 * @param integer $day
	 * @return CSS_DynamicCompressor
	 */
	public function setExpireDay($day)
	{
		$this->_expires = strtotime("+{$day} day");
		return $this;
	}

	/**
	 * Set expires time
	 *
	 * @param integer $time
	 * @return CSS_DynamicCompressor
	 */
	public function setExpires($time)
	{
		$this->_expires = $time;
		return $this;
	}

	/**
	 * Set base directory
	 *
	 * @param string $directory
	 * @return CSS_DynamicCompressor
	 * @throws CSS_DynamicCompressor_Exception
	 */
	public function setDirectory($directory = null)
	{
		if (!$directory) {
			$directory = getcwd();
		}
		$this->_directory = realpath($directory);
		if (!$this->_directory) {
			throw new CSS_DynamicCompressor_Exception('Directory path is invalid');
		}
		$this->_directory .= DIRECTORY_SEPARATOR;
		return $this;
	}

	/**
	 * Set CSS files
	 *
	 * @param array $cssFiles
	 * @return CSS_DynamicCompressor
	 */
	public function setCSSFiles(Array $cssFiles = array())
	{
		foreach ($cssFiles as &$file) {
			if (substr($file, -4, 4) !== '.css') {
				$file .= '.css';
			}
		}
		$this->_cssFiles = $cssFiles;
		return $this;
	}

	/**
	 * Set comment options
	 *
	 * Usage:
	 * CSS_DynamicCompressor::getInstance()
	 *   ->setCommentOptions(array(
	 *     'file_list' => false,
	 *     'copyright' => false
	 *   ))
	 *
	 * @param array $options
	 * @return CSS_DynamicCompressor
	 */
	public function setCommentOptions(Array $options = array())
	{
		$this->_commentOptions = $options + $this->_commentOptions;
		return $this;
	}

	/**
	 * Set base URL
	 *
	 * @param string $url
	 * @return CSS_DynamicCompressor
	 */
	public function setBaseUrl($url = null)
	{
		if (!$url) {
			$url = $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
		}
		if (strpos($url, 'http://') === false) {
			$scheme = !empty($_SERVER['HTTPS']) ? 'https://' : 'http://';
			$url = "{$scheme}{$url}";
		}
		$this->_baseUrl = preg_replace('_/*$_', '', $url) . '/';
		return $this;
	}

	/**
	 * Set fix CSS3 vendor prefix
	 *
	 * @param boolean $use
	 * @return CSS_DynamicCompressor
	 */
	public function setCSS3Fix($use)
	{
		$this->_css3Fix = $use;
		return $this;
	}

	/**
	 * Get CSS content
	 *
	 * @return string
	 */
	public function getCSS()
	{
		return $this->_css;
	}

	/**
	 * Get last modified time
	 *
	 * @return integer
	 */
	public function getLastModified()
	{
		if (!$this->_lastModified) {
			$this->isModified();
		}
		return $this->_lastModified;
	}

	/**
	 * Display
	 *
	 * @return string
	 */
	public function display()
	{
		$this->compression();
		$this->addHeader();
		echo $this->_css;
	}

	/**
	 * Compression
	 *
	 * @return CSS_DynamicCompressor
	 */
	public function compression()
	{
		if (!$this->isModified()) {
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

	/**
	 * Concatenation
	 *
	 * @return CSS_DynamicCompressor
	 */
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

	/**
	 * Extract CSS files
	 *
	 * @return CSS_DynamicCompressor
	 */
	public function extractCSSFiles()
	{
		$target = $this->_readFile($this->_target);
		preg_match_all('/@import\s+(url\()?["\']?([^"\'\);]+)/',
			$target, $files, PREG_PATTERN_ORDER);
		$this->_cssFiles = $files[2];
		return $this;
	}

	/**
	 * Compress
	 *
	 * @return CSS_DynamicCompressor
	 * @throws CSS_DynamicCompressor_Exception
	 */
	public function compress()
	{
		if (!$this->_css) {
			throw new CSS_DynamicCompressor_Exception('$this->_css is empty');
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

	/**
	 * Fix CSS3 vendor prefix
	 *
	 * @return CSS_DynamicCompressor
	 * @throws CSS_DynamicCompressor_Exception
	 */
	public function fixCSS3()
	{
		if (!$this->_css) {
			throw new CSS_DynamicCompressor_Exception('$this->_css is empty');
		}
		foreach ($this->_css3Fixes as $exp => $fixes) {
			$this->_css = preg_replace($exp, implode(';', $fixes), $this->_css);
		}
		return $this;
	}

	/**
	 * Is modified
	 *
	 * @return boolean
	 */
	public function isModified()
	{
		if ($this->_debug) {
			return true;
		}
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

	/**
	 * Add header
	 *
	 * @return CSS_DynamicCompressor
	 */
	public function addHeader()
	{
		header('Content-Type: text/css');
		header('Last-Modified: '. gmdate('D, d M Y H:i:s', $this->_lastModified) .' GMT');
		header('Expires: '. gmdate('D, d M Y H:i:s', $this->_expires) .' GMT');
		ob_start('ob_gzhandler');
		return $this;
	}

	/**
	 * Add charset
	 *
	 * @param string $charset
	 * @return CSS_DynamicCompressor
	 */
	public function addCharset($charset = '')
	{
		if (!empty($charset)) $this->_charset = $charset;
		$this->_css = "@charset \"$this->_charset\";\n$this->_css";
		return $this;
	}

	/**
	 * Add comment
	 *
	 * @param array $comment
	 * @return CSS_DynamicCompressor
	 */
	public function addComment(Array $comment = array())
	{
		$this->_comment = $comment;
		if ($this->_commentOptions['file_list']) {
			$this->_addFileList();
		}
		if ($this->_commentOptions['copyright']) {
			$this->_addCopyright();
		}
		if (empty($this->_comment)) {
			return $this;
		}
		array_unshift($this->_comment, '/**');
		$this->_comment[] = ' */';
		$this->_comment[] = '';
		$this->_css = implode("\n", $this->_comment) . $this->_css;
		return $this;
	}

	/**
	 * Add file list
	 *
	 * @return void
	 */
	protected function _addFileList()
	{
		if (!$this->_baseUrl) {
			$this->setBaseUrl();
		}
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

	/**
	 * Add copyright
	 *
	 * @return void
	 */
	protected function _addCopyright()
	{
		$this->_comment[] = ' * Powered by '. __CLASS__ .' - v'. self::VERSION;
		$this->_comment[] = ' * http://rewish.org/php_mysql/css_dynamic_compressor';
		$this->_comment[] = ' * (c) 2010-'. date('Y') .' Hiroshi Hoaki <rewish.org@gmail.com>';
	}

	/**
	 * Read file
	 *
	 * @param string $file
	 * @return string
	 * @throws CSS_DynamicCompressor_Exception
	 */
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
			throw new CSS_DynamicCompressor_Exception("'$path' does not exist");
		}
		if (!is_file($path)) {
			throw new CSS_DynamicCompressor_Exception("'$path' is no file");
		}
		return file_get_contents($path);
	}

	/**
	 * Write file
	 *
	 * @param string $file
	 * @param string $data
	 * @return beelean
	 */
	protected function _writeFile($file, $data)
	{
		return file_put_contents($this->_directory . $file, $data, LOCK_EX);
	}
}

class CSS_DynamicCompressor_Exception extends Exception
{
}
